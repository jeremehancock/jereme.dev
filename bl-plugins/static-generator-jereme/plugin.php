<?php defined('BLUDIT') or die('Bludit CMS.');

/**
 * Static Site Generator (jereme)
 *
 * Crawls the running Bludit instance and writes a fully static HTML mirror
 * to bl-content/static-build/. Pages are saved as path/index.html with all
 * absolute and root-relative URLs rewritten to document-relative form so the
 * output works on any host (and over file://).
 *
 * Security notes:
 *   - The form posts through the standard configure-plugin endpoint, so the
 *     kernel's checkRole(['admin']) + tokenCSRF rules already apply before
 *     post() runs.
 *   - The output directory is a hard-coded constant under PATH_CONTENT.
 *     Filesystem paths derived from URLs are validated to stay under it.
 *   - cURL self-requests intentionally do NOT forward cookies, so the crawl
 *     always runs as an anonymous visitor.
 *   - Admin URLs and bl-content/databases are always skipped, regardless of
 *     the exclude-paths setting.
 */
class pluginStaticGeneratorJereme extends Plugin
{
	const OUTPUT_DIRNAME = 'static-build';
	const LOCK_FILENAME = 'build.lock';
	const LOG_FILENAME = 'build.log';
	const MAX_LOG_LINES = 100;
	// Hard cap on URLs per build — purely a runaway-safety net, not a
	// user-facing setting. The build is expected to cover every page on
	// the site; this is just here so a pathological link loop can't make
	// PHP run forever.
	const URL_HARD_CAP = 10000;

	public function init()
	{
		$this->dbFields = array(
			'excludePaths' => '',
			'productionUrl' => '',

			// Written by runBuild() — not user-editable.
			'lastBuildTime' => '',
			'lastBuildResult' => '',
			'lastBuildMessage' => '',
			'lastBuildUrls' => 0,
			'lastBuildBytes' => 0,
			'lastBuildDuration' => 0,
		);
	}

	// ----------------------------------------------------------------------
	// Admin form
	// ----------------------------------------------------------------------
	public function form()
	{
		global $L;

		// The wrapper class scopes the theme-aware button/details CSS in
		// renderConfirmAndLoading() so we don't accidentally restyle the
		// "Save" / "Cancel" buttons the configure-plugin view emits
		// outside the plugin's own form contents.
		$html = '<div class="sgj-cfg">';

		$html .= '<div class="alert alert-primary mb-4" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		// ============ SECTION: Generate ===================================
		$html .= $this->openCard('sgj-section-action', 'hammer', 'sgj-section-action-subtitle');

		$buildLabel = htmlspecialchars($L->get('sgj-build-button'), ENT_QUOTES, 'UTF-8');
		$html .= '<button type="submit" id="sgj-build-btn" class="btn btn-primary"'
			. ' name="action" value="build">'
			. $buildLabel
			. '</button>';

		$html .= $this->closeCard();

		// ============ SECTION: Settings ===================================
		$html .= $this->openCard('sgj-section-settings', 'cog');
		$html .= $this->textField('productionUrl', $L->get('sgj-production-url-label'), $L->get('sgj-production-url-tip'));
		$html .= $this->textareaField('excludePaths', $L->get('sgj-exclude-paths-label'), $L->get('sgj-exclude-paths-tip'), 4);
		$html .= $this->closeCard();

		// ============ SECTION: Status =====================================
		$html .= $this->openCard('sgj-section-status', 'history');
		$html .= $this->renderStatus();
		$html .= $this->closeCard();

		// ============ SECTION: How it works ===============================
		$html .= '<div class="card mb-4 border-info">';
		$html .= '<div class="card-header bg-info text-white">';
		$html .= '<span class="fa fa-info-circle mr-2"></span>';
		$html .= '<strong>' . $L->get('sgj-section-howitworks') . '</strong>';
		$html .= '</div>';
		$html .= '<div class="card-body">';
		$html .= '<ul class="mb-0" style="padding-left: 1.2rem; line-height: 1.7;">';
		$html .= '<li class="mb-2">' . $L->get('sgj-howitworks-1') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('sgj-howitworks-2') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('sgj-howitworks-3') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('sgj-howitworks-4') . '</li>';
		$html .= '<li>' . $L->get('sgj-howitworks-5') . '</li>';
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		// Confirmation modal + full-page loading overlay + JS that wires the
		// "Generate static site" button to them. Self-contained styles so we
		// don't depend on Bootstrap modal JS being available.
		$html .= $this->renderConfirmAndLoading();

		$html .= '</div>'; // .sgj-cfg
		return $html;
	}

	private function renderConfirmAndLoading()
	{
		global $L;
		$confirmTitle = htmlspecialchars($L->get('sgj-confirm-title'), ENT_QUOTES, 'UTF-8');
		$confirmBody = htmlspecialchars($L->get('sgj-confirm-body'), ENT_QUOTES, 'UTF-8');
		$confirmYes = htmlspecialchars($L->get('sgj-confirm-yes'), ENT_QUOTES, 'UTF-8');
		$confirmCancel = htmlspecialchars($L->get('sgj-confirm-cancel'), ENT_QUOTES, 'UTF-8');
		$loadingTitle = htmlspecialchars($L->get('sgj-loading-title'), ENT_QUOTES, 'UTF-8');
		$loadingBody = htmlspecialchars($L->get('sgj-loading-body'), ENT_QUOTES, 'UTF-8');
		// JSON-encoded so the label is safe to drop straight into JS even
		// if it contains quotes or unicode.
		$buildingJson = json_encode($L->get('sgj-building-button'), JSON_UNESCAPED_UNICODE);

		return <<<HTML

<style>
	/* Inherit the admin theme's palette via CSS custom properties. When
	   the active theme is a dark one (booty-jereme-dev), --bg-card,
	   --text-primary, etc. resolve to dark values automatically. Each
	   var() has a light-mode fallback so the modal also looks sensible
	   under the stock booty admin theme or any theme that doesn't define
	   these vars. */
	.sgj-modal-overlay,
	.sgj-loading-overlay {
		position: fixed;
		inset: 0;
		display: none;
		align-items: center;
		justify-content: center;
		background: rgba(0, 0, 0, 0.72);
		z-index: 2147483000;
	}
	.sgj-modal-overlay.is-open,
	.sgj-loading-overlay.is-open { display: flex; }
	.sgj-modal,
	.sgj-loading {
		background: var(--bg-card, #ffffff);
		color: var(--text-primary, #212529);
		border: 1px solid var(--border-color, #dee2e6);
		border-radius: var(--radius-md, 8px);
		box-shadow: var(--shadow-md, 0 20px 60px rgba(0, 0, 0, 0.35));
		max-width: 460px;
		width: calc(100% - 2rem);
		font-family: inherit;
	}
	.sgj-modal { padding: 1.5rem 1.5rem 1.25rem; }
	.sgj-modal h3 {
		margin: 0 0 0.5rem;
		font-size: 1.15rem;
		font-weight: var(--font-weight-semibold, 600);
		color: var(--text-primary, #212529);
	}
	.sgj-modal p {
		margin: 0 0 1.25rem;
		color: var(--text-secondary, #495057);
		line-height: 1.5;
	}
	.sgj-modal-actions {
		display: flex;
		justify-content: flex-end;
		gap: 0.5rem;
	}
	.sgj-loading {
		padding: 2rem 2.5rem;
		text-align: center;
		max-width: 420px;
	}
	.sgj-spinner {
		width: 48px;
		height: 48px;
		margin: 0 auto 1rem;
		border: 4px solid var(--border-color, rgba(0, 0, 0, 0.1));
		border-top-color: var(--primary-blue, #007bff);
		border-radius: 50%;
		animation: sgj-spin 0.9s linear infinite;
	}
	@keyframes sgj-spin { to { transform: rotate(360deg); } }
	.sgj-loading h3 {
		margin: 0 0 0.35rem;
		font-size: 1.1rem;
		font-weight: var(--font-weight-semibold, 600);
		color: var(--text-primary, #212529);
	}
	.sgj-loading p {
		margin: 0;
		color: var(--text-secondary, #6c757d);
		font-size: 0.9rem;
		line-height: 1.45;
	}
	#sgj-build-btn[disabled] { cursor: not-allowed; opacity: 0.65; }

	/* Disabled state: Bootstrap's .btn-primary:disabled / .btn-primary.disabled
	   has higher specificity than our base .btn-primary rule, so we'd
	   otherwise lose the mint palette the moment any button is disabled
	   (which is exactly what happens to Save + Generate while the build
	   runs). Explicit rules with !important keep the theme colours and
	   just dim the button instead of flipping it back to Bootstrap blue. */
	.btn-primary:disabled,
	.btn-primary.disabled,
	.btn-primary[disabled] {
		background-color: var(--primary-blue, #007bff) !important;
		border-color: var(--primary-blue, #007bff) !important;
		color: var(--accent-ink, #fff) !important;
		opacity: 0.6 !important;
		cursor: not-allowed;
	}
	.btn-secondary:disabled,
	.btn-secondary.disabled,
	.btn-secondary[disabled] {
		background-color: var(--bg-light, #6c757d) !important;
		border-color: var(--border-color, #6c757d) !important;
		color: var(--text-primary, #fff) !important;
		opacity: 0.6 !important;
		cursor: not-allowed;
	}

	/* The stock admin theme styles .btn-light / .btn-form but leaves
	   .btn-primary and .btn-secondary at Bootstrap's hardcoded blues
	   and greys, which clash with the dark mint palette. This <style>
	   block is rendered only inside form() on the plugin's
	   configure-plugin page, so the rules are scoped to that one page
	   — including the Save / Cancel buttons the configure-plugin view
	   emits at the top, which we want themed the same way. */
	.btn-primary,
	.btn-primary:not(:disabled):not(.disabled) {
		background-color: var(--primary-blue, #007bff);
		border-color: var(--primary-blue, #007bff);
		color: var(--accent-ink, #fff);
	}
	.btn-primary:hover,
	.btn-primary:focus,
	.btn-primary:focus-visible,
	.btn-primary:not(:disabled):not(.disabled):active,
	.btn-primary:not(:disabled):not(.disabled).active {
		background-color: var(--primary-blue-darker, #0056b3) !important;
		border-color: var(--primary-blue-darker, #0056b3) !important;
		color: var(--accent-ink, #fff) !important;
		box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18) !important;
	}
	.btn-secondary,
	.btn-secondary:not(:disabled):not(.disabled) {
		background-color: var(--bg-light, #6c757d);
		border-color: var(--border-color, #6c757d);
		color: var(--text-primary, #fff);
	}
	.btn-secondary:hover,
	.btn-secondary:focus,
	.btn-secondary:focus-visible,
	.btn-secondary:not(:disabled):not(.disabled):active,
	.btn-secondary:not(:disabled):not(.disabled).active {
		background-color: var(--bg-warm-card, #5a6268) !important;
		border-color: var(--border-light, #5a6268) !important;
		color: var(--text-primary, #fff) !important;
		box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.12) !important;
	}

	/* The build-log preview lives inside a <details>; style its summary
	   so it picks up the theme's text colour and looks clickable. */
	.sgj-cfg details > summary {
		cursor: pointer;
		color: var(--text-primary, #212529);
		font-weight: var(--font-weight-medium, 500);
		margin-bottom: 0.5rem;
	}
	.sgj-cfg details > summary:hover {
		color: var(--primary-blue, #007bff);
	}
</style>

<div id="sgj-confirm" class="sgj-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="sgj-confirm-title" aria-hidden="true">
	<div class="sgj-modal">
		<h3 id="sgj-confirm-title">{$confirmTitle}</h3>
		<p>{$confirmBody}</p>
		<div class="sgj-modal-actions">
			<button type="button" class="btn btn-secondary btn-sm" id="sgj-confirm-cancel">{$confirmCancel}</button>
			<button type="button" class="btn btn-primary btn-sm" id="sgj-confirm-yes">{$confirmYes}</button>
		</div>
	</div>
</div>

<div id="sgj-loading" class="sgj-loading-overlay" role="alertdialog" aria-modal="true" aria-labelledby="sgj-loading-title" aria-hidden="true">
	<div class="sgj-loading">
		<div class="sgj-spinner" aria-hidden="true"></div>
		<h3 id="sgj-loading-title">{$loadingTitle}</h3>
		<p>{$loadingBody}</p>
	</div>
</div>

<script>
(function () {
	var btn = document.getElementById('sgj-build-btn');
	if (!btn) return;
	var form = btn.closest('form');
	var confirmEl = document.getElementById('sgj-confirm');
	var confirmYes = document.getElementById('sgj-confirm-yes');
	var confirmCancel = document.getElementById('sgj-confirm-cancel');
	var loadingEl = document.getElementById('sgj-loading');
	var BUILDING_LABEL = {$buildingJson};
	var armed = false;

	function openConfirm(e) {
		if (armed) { return; }
		e.preventDefault();
		confirmEl.classList.add('is-open');
		confirmEl.setAttribute('aria-hidden', 'false');
		confirmYes.focus();
	}

	function closeConfirm() {
		confirmEl.classList.remove('is-open');
		confirmEl.setAttribute('aria-hidden', 'true');
		btn.focus();
	}

	function startBuild() {
		closeConfirm();
		if (!form) { return; }
		armed = true;
		// IMPORTANT: inject the hidden action=build BEFORE disabling any
		// controls. Browsers omit a disabled submitter's name/value from
		// the form data, so if we instead relied on the Generate button
		// being the submitter after we'd disabled it, post() would see
		// no `action` field and silently fall through to "just save
		// settings" — the build would never actually run.
		var hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.name = 'action';
		hidden.value = 'build';
		form.appendChild(hidden);
		// Disable every submit-style control so the user cannot click
		// Save (or Generate) twice while the build runs.
		var controls = form.querySelectorAll('button, input[type=submit]');
		for (var i = 0; i < controls.length; i++) { controls[i].disabled = true; }
		btn.textContent = BUILDING_LABEL;
		loadingEl.classList.add('is-open');
		loadingEl.setAttribute('aria-hidden', 'false');
		form.submit();
	}

	btn.addEventListener('click', openConfirm);
	confirmCancel.addEventListener('click', closeConfirm);
	confirmEl.addEventListener('click', function (e) {
		if (e.target === confirmEl) { closeConfirm(); }
	});
	confirmYes.addEventListener('click', startBuild);
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && confirmEl.classList.contains('is-open')) { closeConfirm(); }
	});
})();
</script>

HTML;
	}

	private function renderStatus()
	{
		global $L;

		$lastTime = $this->getValue('lastBuildTime');
		if (empty($lastTime)) {
			return '<p class="text-muted mb-0">' . $L->get('sgj-status-never') . '</p>';
		}

		$result = $this->getValue('lastBuildResult');
		$resultLabel = $L->get('sgj-result-fail');
		$resultClass = 'danger';
		if ($result === 'ok') {
			$resultLabel = $L->get('sgj-result-ok');
			$resultClass = 'success';
		} elseif ($result === 'partial') {
			$resultLabel = $L->get('sgj-result-partial');
			$resultClass = 'warning';
		}

		$rows = array(
			array($L->get('sgj-status-result'), '<span class="badge badge-' . $resultClass . '">' . $resultLabel . '</span>'),
			array($L->get('sgj-status-time'), htmlspecialchars($lastTime, ENT_QUOTES, 'UTF-8')),
			array($L->get('sgj-status-urls'), (int) $this->getValue('lastBuildUrls')),
			array($L->get('sgj-status-bytes'), number_format((int) $this->getValue('lastBuildBytes'))),
			array($L->get('sgj-status-duration'), number_format((float) $this->getValue('lastBuildDuration'), 2) . 's'),
			array($L->get('sgj-status-output'), '<code>' . htmlspecialchars($this->outputDir(), ENT_QUOTES, 'UTF-8') . '</code>'),
		);
		$msg = trim((string) $this->getValue('lastBuildMessage'));
		if ($msg !== '') {
			$rows[] = array($L->get('sgj-status-message'), htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
		}

		$html = '<table class="table table-sm mb-3"><tbody>';
		foreach ($rows as $r) {
			$html .= '<tr><th style="width: 12rem;">' . $r[0] . '</th><td>' . $r[1] . '</td></tr>';
		}
		$html .= '</tbody></table>';

		$log = $this->readLogTail();
		if ($log !== '') {
			$html .= '<details open><summary>' . $L->get('sgj-status-log') . '</summary>';
			$html .= '<pre style="max-height: 320px; overflow: auto;'
				. ' background: var(--bg-warm, #f8f9fa);'
				. ' color: var(--text-primary, #212529);'
				. ' border: 1px solid var(--border-color, #dee2e6);'
				. ' padding: 0.75rem; font-size: 0.8rem;">'
				. htmlspecialchars($log, ENT_QUOTES, 'UTF-8')
				. '</pre></details>';
		}
		return $html;
	}

	// ----------------------------------------------------------------------
	// POST entry point
	//
	// CSRF + admin role are validated by the kernel before we get here, so we
	// only need to dispatch between "save settings" (default) and "build".
	// ----------------------------------------------------------------------
	public function post()
	{
		$action = isset($_POST['action']) ? Sanitize::html($_POST['action']) : '';
		// Always persist any edited settings first, even when the user
		// clicked "Generate static site" instead of "Save" — otherwise a
		// changed excludePaths value silently reverts.
		parent::post();
		if ($action === 'build') {
			$this->runBuild();
		}
		return true;
	}

	// ----------------------------------------------------------------------
	// Build orchestration
	// ----------------------------------------------------------------------
	private function runBuild()
	{
		global $L;

		@set_time_limit(0);
		$start = microtime(true);

		// Make sure the workspace exists for lock + log files.
		$workspace = $this->workspace();
		if (!is_dir($workspace)) {
			@mkdir($workspace, DIR_PERMISSIONS, true);
		}

		$lockPath = $workspace . self::LOCK_FILENAME;
		$lockFp = @fopen($lockPath, 'c');
		if (!$lockFp || !@flock($lockFp, LOCK_EX | LOCK_NB)) {
			$this->recordResult('fail', $L->get('sgj-build-locked'), 0, 0, microtime(true) - $start);
			if ($lockFp) {
				@fclose($lockFp);
			}
			Alert::set($L->get('sgj-build-locked'), ALERT_STATUS_FAIL);
			return;
		}
		@ftruncate($lockFp, 0);
		@fwrite($lockFp, (string) getmypid());

		// Fresh log.
		$this->logReset();
		$this->log('Build started at ' . date('c'));

		$outDir = $this->outputDir();
		if (!$this->ensureDir($outDir)) {
			$this->log('FATAL: could not create output directory: ' . $outDir);
			$this->recordResult('fail', $L->get('sgj-build-no-output-dir'), 0, 0, microtime(true) - $start);
			@flock($lockFp, LOCK_UN);
			@fclose($lockFp);
			Alert::set($L->get('sgj-build-no-output-dir'), ALERT_STATUS_FAIL);
			return;
		}

		// Clear previous build so deleted pages don't linger.
		$this->log('Clearing output directory: ' . $outDir);
		$this->clearDir($outDir);

		$state = array(
			'sitePathPrefix' => $this->sitePathPrefix(),
			'outDir' => $outDir,
			'queue' => array(),
			'enqueued' => array(),
			'urlsFetched' => 0,
			'bytesWritten' => 0,
			'errors' => 0,
			'maxUrls' => self::URL_HARD_CAP,
			'excludePaths' => $this->parseExcludePaths(),
		);

		// Seed.
		foreach ($this->seedPaths() as $path) {
			$this->enqueue($state, $path, 'page');
		}

		// BFS.
		while (!empty($state['queue']) && $state['urlsFetched'] < $state['maxUrls']) {
			$item = array_shift($state['queue']);
			$this->processItem($state, $item);
		}

		// 404 page. Written to <build>/404.html at the build root so that
		// directory-indexing webservers / static hosts (Apache via
		// ErrorDocument, nginx via error_page, GitHub Pages / Netlify /
		// Cloudflare Pages by convention) can serve it on any 404. A
		// <base href> tag is injected into the page so relative asset and
		// page URLs resolve correctly even when the browser address bar
		// shows the URL that triggered the 404 rather than /404.html.
		$this->writeNotFoundPage($state);

		$duration = microtime(true) - $start;
		$result = $state['errors'] === 0 ? 'ok' : 'partial';
		$message = 'Fetched ' . $state['urlsFetched'] . ' URLs (' . $state['errors'] . ' errors)';
		$this->log('Build finished: ' . $message . ' in ' . number_format($duration, 2) . 's');

		$this->recordResult($result, $message, $state['urlsFetched'], $state['bytesWritten'], $duration);

		@flock($lockFp, LOCK_UN);
		@fclose($lockFp);

		$alertStatus = $state['errors'] === 0 ? ALERT_STATUS_OK : ALERT_STATUS_FAIL;
		Alert::set($message, $alertStatus);
	}

	private function recordResult($result, $message, $urls, $bytes, $duration)
	{
		$this->db['lastBuildTime'] = date('Y-m-d H:i:s');
		$this->db['lastBuildResult'] = $result;
		$this->db['lastBuildMessage'] = Sanitize::html($message);
		$this->db['lastBuildUrls'] = (int) $urls;
		$this->db['lastBuildBytes'] = (int) $bytes;
		$this->db['lastBuildDuration'] = (float) $duration;
		$this->save();
	}

	// ----------------------------------------------------------------------
	// URL discovery
	// ----------------------------------------------------------------------
	private function seedPaths()
	{
		global $pages, $categories, $tags, $site;

		$paths = array('/');

		// Pagination of the home page.
		$itemsPerPage = method_exists($site, 'itemsPerPage') ? max(1, (int) $site->itemsPerPage()) : 6;
		try {
			$publishedCount = $pages->count(true);
		} catch (Exception $e) {
			$publishedCount = 0;
		}
		$totalPages = (int) ceil($publishedCount / $itemsPerPage);
		for ($i = 2; $i <= $totalPages; $i++) {
			$paths[] = '/?page=' . $i;
		}

		// All pages, regardless of pagination position. We construct Page
		// objects so we get the canonical permalink (handles parent/child
		// slugs and the static vs. published path differences).
		$keys = array_merge(
			(array) $pages->getDB(true),
			(array) $pages->getStaticDB(true),
			(array) $pages->getStickyDB(true)
		);
		foreach (array_unique($keys) as $key) {
			try {
				$page = new Page($key);
				$paths[] = $this->urlToPath($page->permalink(true));
			} catch (Exception $e) {
				// Skip pages that fail to construct.
			}
		}

		// Categories. CATEGORY_URI_FILTER is "category" (trimmed of slashes).
		if (is_object($categories) && isset($categories->db) && is_array($categories->db)) {
			foreach ($categories->db as $key => $fields) {
				if (!empty($fields['list'])) {
					$paths[] = '/' . CATEGORY_URI_FILTER . '/' . $key;
				}
			}
		}

		// Tags.
		if (is_object($tags) && isset($tags->db) && is_array($tags->db)) {
			foreach ($tags->db as $key => $fields) {
				if (!empty($fields['list'])) {
					$paths[] = '/' . TAG_URI_FILTER . '/' . $key;
				}
			}
		}

		// Optional well-known plugin endpoints — only seeded when the plugin
		// is enabled so we don't log spurious 404s.
		foreach (array('sitemap' => '/sitemap.xml', 'rss' => '/rss.xml') as $pluginName => $endpoint) {
			$pluginPath = PATH_PLUGINS . $pluginName;
			if (is_dir($pluginPath)) {
				$paths[] = $endpoint;
			}
		}
		if (is_file(PATH_ROOT . 'robots.txt')) {
			$paths[] = '/robots.txt';
		}

		return array_values(array_unique($paths));
	}

	private function urlToPath($url)
	{
		$parsed = parse_url($url);
		$path = isset($parsed['path']) ? $parsed['path'] : '/';
		if (isset($parsed['query']) && $parsed['query'] !== '') {
			$path .= '?' . $parsed['query'];
		}
		return $path === '' ? '/' : $path;
	}

	private function parseExcludePaths()
	{
		$raw = (string) $this->getValue('excludePaths', false);
		$out = array();
		foreach (preg_split('/\r?\n/', $raw) as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			if ($line[0] !== '/') {
				$line = '/' . $line;
			}
			$out[] = $line;
		}
		return $out;
	}

	private function shouldSkip($path, array $excludePaths)
	{
		// Always-skipped admin / private dirs. These run through the same
		// path-segment matcher as user excludes so /admin matches /admin
		// itself and /admin/* but never /admin-something-else.
		$alwaysSkip = array(
			'/' . ADMIN_URI_FILTER,
			'/bl-content/databases',
			'/bl-content/workspaces',
			'/bl-content/tmp',
		);
		foreach ($alwaysSkip as $prefix) {
			if ($this->pathMatchesPrefix($path, $prefix)) {
				return true;
			}
		}
		foreach ($excludePaths as $prefix) {
			if ($this->pathMatchesPrefix($path, $prefix)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Path-segment-aware prefix match.
	 *   /homelab           matches  /homelab and /homelab/anything
	 *   /homelab           does NOT match /homelab-md-offline-...
	 *   /admin             matches  /admin and /admin/users
	 *
	 * Both args must start with "/". Trailing slashes on $prefix are
	 * ignored. This is what every user-supplied exclude-path entry needs
	 * — plain string startsWith() would also match unrelated slugs that
	 * happen to share a textual prefix.
	 */
	private function pathMatchesPrefix($path, $prefix)
	{
		$prefix = rtrim($prefix, '/');
		if ($prefix === '') {
			return false;
		}
		// Drop the query string before comparing so /foo?x=1 matches /foo.
		$qPos = strpos($path, '?');
		if ($qPos !== false) {
			$path = substr($path, 0, $qPos);
		}
		if ($path === $prefix) {
			return true;
		}
		if (strncmp($path, $prefix . '/', strlen($prefix) + 1) === 0) {
			return true;
		}
		return false;
	}

	// ----------------------------------------------------------------------
	// Queue
	// ----------------------------------------------------------------------
	private function enqueue(array &$state, $path, $kind)
	{
		$path = $this->normalizePath($path, $state['sitePathPrefix']);
		if ($path === null) {
			return;
		}
		if ($this->shouldSkip($path, $state['excludePaths'])) {
			return;
		}
		// Dedupe by path; "page" wins over "asset" if both were enqueued.
		if (isset($state['enqueued'][$path])) {
			return;
		}
		$state['enqueued'][$path] = true;
		$state['queue'][] = array('path' => $path, 'kind' => $kind);
	}

	/**
	 * Normalize a candidate URL/path against the site's URL space.
	 * Returns the in-site path (with leading "/") or null if the URL is
	 * external, a fragment, an unsupported scheme, or otherwise out of scope.
	 */
	private function normalizePath($candidate, $sitePathPrefix)
	{
		$candidate = trim((string) $candidate);
		if ($candidate === '' || $candidate[0] === '#') {
			return null;
		}
		// Reject unsupported schemes (mailto:, tel:, javascript:, data:).
		if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $candidate)) {
			if (!preg_match('#^https?://#i', $candidate)) {
				return null;
			}
			$siteHost = parse_url(rtrim(DOMAIN_BASE, '/'), PHP_URL_HOST);
			$cHost = parse_url($candidate, PHP_URL_HOST);
			if ($siteHost && $cHost && strcasecmp($siteHost, $cHost) !== 0) {
				return null;
			}
			$path = parse_url($candidate, PHP_URL_PATH);
			$query = parse_url($candidate, PHP_URL_QUERY);
			if ($path === null) {
				$path = '/';
			}
			$out = $path . ($query ? '?' . $query : '');
		} else {
			$out = $candidate;
		}
		// Strip fragment.
		$hash = strpos($out, '#');
		if ($hash !== false) {
			$out = substr($out, 0, $hash);
		}
		if ($out === '') {
			$out = '/';
		}
		// Ensure leading slash.
		if ($out[0] !== '/') {
			return null;
		}
		// Reject any traversal in the URL component (the URL parser already
		// resolved most cases, but be paranoid).
		if (strpos($out, '/../') !== false || substr($out, -3) === '/..') {
			return null;
		}
		// Strip the site base path prefix if installed in a subdirectory.
		if ($sitePathPrefix !== '/' && strpos($out, $sitePathPrefix) === 0) {
			$out = substr($out, strlen(rtrim($sitePathPrefix, '/')));
			if ($out === '' || $out[0] !== '/') {
				$out = '/' . ltrim($out, '/');
			}
		}
		return $out;
	}

	// ----------------------------------------------------------------------
	// Per-item processing
	//
	// Two paths:
	//   - Pages are rendered in-process using Bludit's own theme dispatch.
	//     No HTTP is involved, so the build works inside Vagrant /
	//     containers where the PHP process cannot reach the public
	//     hostname.
	//   - Assets are copied straight from disk. mapToDisk() returns a real
	//     file path under PATH_ROOT only after path-traversal and
	//     allow-list checks, so we never expose anything outside the
	//     public webroot.
	// ----------------------------------------------------------------------
	private function processItem(array &$state, array $item)
	{
		// Catch-all so a single broken page (uncaught exception in the
		// theme, a Throwable from a plugin hook, etc.) never aborts the
		// whole build silently. The recorded error count then reflects
		// reality and the run is correctly reported as "Completed with
		// errors" rather than "Success".
		try {
			$this->processItemInner($state, $item);
		} catch (Throwable $e) {
			$state['errors']++;
			$this->log('ERROR exception processing ' . $item['path'] . ': ' . $e->getMessage());
		}
	}

	private function processItemInner(array &$state, array $item)
	{
		$path = $item['path'];
		$kind = $item['kind'];

		// Assets first — fastest path, no rendering needed.
		if ($kind !== 'page') {
			$diskPath = $this->mapToDisk($path);
			if ($diskPath !== null && is_file($diskPath)) {
				$this->saveAssetFromDisk($state, $path, $diskPath);
				return;
			}
			$state['errors']++;
			$this->log('ERROR asset not on disk: ' . $path);
			return;
		}

		// Page render via the kernel.
		$result = $this->renderInternal($path);
		if (!$result['ok']) {
			$state['errors']++;
			$this->log('ERROR render ' . $path . ' (' . $result['error'] . ')');
			return;
		}
		// Bludit's 404 page has the right HTML but the page-not-found
		// content. We still write it so /404 works in the build, but skip
		// any URL that resolved to "not found" via an actual missing slug.
		if ($result['notFound'] && $path !== '/404' && $path !== '/page-not-found') {
			$state['errors']++;
			$this->log('ERROR 404 ' . $path);
			return;
		}
		$state['urlsFetched']++;
		$this->log('OK  render ' . $path);

		$savePath = $this->htmlSavePath($state['outDir'], $path);
		if ($savePath === null) {
			$state['errors']++;
			$this->log('ERROR refusing unsafe save path for ' . $path);
			return;
		}
		$rewritten = $this->rewriteHtml($state, $path, $result['body']);
		$bytes = $this->writeFile($savePath, $rewritten);
		if ($bytes === false) {
			$state['errors']++;
			return;
		}
		$state['bytesWritten'] += $bytes;
	}

	/**
	 * Render Bludit's configured 404 page and write it to <build>/404.html.
	 *
	 * Two non-obvious bits:
	 *
	 *  1. We synthesise a request to a path that doesn't exist
	 *     (/__sgj_notfound__) so Bludit's URL parser sets the notFound
	 *     flag — that triggers renderInternal()'s buildErrorPage() path,
	 *     which loads $site->pageNotFound() and renders the theme around
	 *     it. Net effect: whichever page the admin has configured as
	 *     "page not found" in the Bludit settings is what gets baked into
	 *     404.html, with no hardcoding of slugs.
	 *
	 *  2. When Apache / nginx / a static host serves 404.html in response
	 *     to a missing URL, the browser address bar keeps the original
	 *     (missing) path. Any relative href in the rendered page would
	 *     resolve against that path and produce broken links. Injecting
	 *     <base href="<HTML_PATH_ROOT>"> right after <head> pins relative
	 *     URL resolution to the site root for this one page only.
	 */
	private function writeNotFoundPage(array &$state)
	{
		$probe = '/__sgj_notfound__';
		$result = $this->renderInternal($probe);
		if (!$result['ok']) {
			$state['errors']++;
			$this->log('ERROR rendering 404 page: ' . $result['error']);
			return;
		}
		// The 404's links/assets are rewritten as if the file sat at the
		// build root (depth 0). The injected <base> below makes that
		// produce correct URLs regardless of which path the browser
		// thinks it's on.
		$rewritten = $this->rewriteHtml($state, '/', $result['body']);

		$baseHref = htmlspecialchars(HTML_PATH_ROOT, ENT_QUOTES, 'UTF-8');
		$baseTag = '<base href="' . $baseHref . '">';
		$injected = preg_replace(
			'#(<head\b[^>]*>)#i',
			'$1' . "\n\t" . $baseTag,
			$rewritten,
			1,
			$count
		);
		if ($count > 0) {
			$rewritten = $injected;
		} else {
			// Page somehow has no <head>; prepend the base tag wrapped in
			// a minimal head so relative URLs still resolve from root.
			$rewritten = '<head>' . $baseTag . '</head>' . $rewritten;
		}

		$savePath = $this->resolveSafePath($state['outDir'], '404.html');
		if ($savePath === null) {
			$state['errors']++;
			$this->log('ERROR refusing unsafe save path for 404.html');
			return;
		}
		$bytes = $this->writeFile($savePath, $rewritten);
		if ($bytes === false) {
			$state['errors']++;
			return;
		}
		$state['bytesWritten'] += $bytes;
		$state['urlsFetched']++;
		$this->log('OK  render 404 -> 404.html');
	}

	private function saveAssetFromDisk(array &$state, $path, $diskPath)
	{
		$savePath = $this->fileSavePath($state['outDir'], $path);
		if ($savePath === null) {
			$state['errors']++;
			return;
		}
		if (preg_match('#\.css($|\?)#i', $path)) {
			$body = @file_get_contents($diskPath);
			if ($body === false) {
				$state['errors']++;
				$this->log('ERROR reading ' . $diskPath);
				return;
			}
			$rewritten = $this->rewriteCss($state, $path, $body);
			$bytes = $this->writeFile($savePath, $rewritten);
			if ($bytes === false) {
				$state['errors']++;
				return;
			}
			$state['urlsFetched']++;
			$state['bytesWritten'] += $bytes;
			$this->log('OK  disk  ' . $path . ' [text/css]');
			return;
		}
		if (!$this->ensureDir(dirname($savePath))) {
			$state['errors']++;
			$this->log('ERROR mkdir ' . dirname($savePath));
			return;
		}
		if (!@copy($diskPath, $savePath)) {
			$state['errors']++;
			$this->log('ERROR copy ' . $diskPath . ' -> ' . $savePath);
			return;
		}
		$state['urlsFetched']++;
		$state['bytesWritten'] += @filesize($savePath);
		$this->log('OK  disk  ' . $path);
	}

	// ----------------------------------------------------------------------
	// Internal renderer
	//
	// Renders a Bludit page in-process by manipulating the globals the
	// kernel's site dispatch sets up — $url, $page, $content, $WHERE_AM_I,
	// $staticContent — and including the active theme's index.php inside
	// an output buffer. No HTTP request is made.
	//
	// We intentionally do NOT re-run the boot rules ($pages->scheduler()
	// in 69.pages.php, the buildPlugins() in 60.plugins.php) so that
	// rendering many URLs back-to-back has no compounding side effects.
	// Plugin hooks the theme itself calls (siteHead, siteBodyBegin,
	// siteBodyEnd, siteSidebar, pageBegin, pageEnd) still fire normally.
	// ----------------------------------------------------------------------
	private function renderInternal($path)
	{
		global $site, $url, $page, $content, $pages, $categories, $tags, $L, $plugins, $WHERE_AM_I;
		global $staticContent, $staticPages;
		// Themes commonly stash $helper / $login / $searchJson in their
		// init.php and then guard subsequent re-inits with isset() checks.
		// Promoting those to globals lets the guards work across the many
		// renderInternal() calls in one build, which is what prevents the
		// "Cannot redeclare class Helper" fatal on the second page.
		global $helper, $login, $searchJson;

		// Snapshot everything we touch, so we restore cleanly even on error.
		$saved = array(
			'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/',
			'QUERY_STRING' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '',
			'SERVER_PORT' => isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null,
			'HTTPS' => isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null,
			'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null,
			'SERVER_NAME' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null,
			'GET' => $_GET,
			'url' => $url,
			'page' => isset($GLOBALS['page']) ? $GLOBALS['page'] : null,
			'content' => isset($GLOBALS['content']) ? $GLOBALS['content'] : null,
			'staticContent' => isset($GLOBALS['staticContent']) ? $GLOBALS['staticContent'] : null,
			'staticPages' => isset($GLOBALS['staticPages']) ? $GLOBALS['staticPages'] : null,
			'WAI' => isset($GLOBALS['WHERE_AM_I']) ? $GLOBALS['WHERE_AM_I'] : null,
		);

		$bufLevel = ob_get_level();
		try {
			// Parse path + query into request shape.
			$queryStr = '';
			$qPos = strpos($path, '?');
			if ($qPos !== false) {
				$queryStr = substr($path, $qPos + 1);
				$path = substr($path, 0, $qPos);
			}
			$_SERVER['REQUEST_URI'] = HTML_PATH_ROOT . ltrim($path, '/') . ($queryStr !== '' ? '?' . $queryStr : '');
			$_SERVER['QUERY_STRING'] = $queryStr;
			$_GET = array();
			if ($queryStr !== '') {
				parse_str($queryStr, $_GET);
			}

			// Make $_SERVER reflect the production site URL during this render
			// so plugin hooks see a "production" frontend request rather than
			// the dev port / hostname the admin happens to be served on.
			// Without this, the companion plugin's webStatsDevport check
			// suppresses web stats on every page in the build when the admin
			// runs on the dev port, and any other plugin that branches on
			// SERVER_PORT / HTTPS / HTTP_HOST gets the wrong context too.
			// The "Production URL" plugin setting takes precedence over
			// $site->url() so a local install at http://localhost:8080 can
			// still produce a build targeting the public domain.
			$buildUrl = $this->effectiveBuildUrl();
			$siteUrlParts = parse_url($buildUrl);
			if (is_array($siteUrlParts) && isset($siteUrlParts['host'])) {
				$scheme = isset($siteUrlParts['scheme']) ? $siteUrlParts['scheme'] : 'http';
				$port = isset($siteUrlParts['port'])
					? (int) $siteUrlParts['port']
					: ($scheme === 'https' ? 443 : 80);
				$_SERVER['SERVER_PORT'] = (string) $port;
				$_SERVER['HTTPS'] = ($scheme === 'https') ? 'on' : '';
				$_SERVER['HTTP_HOST'] = $siteUrlParts['host'] . (isset($siteUrlParts['port']) ? ':' . $siteUrlParts['port'] : '');
				$_SERVER['SERVER_NAME'] = $siteUrlParts['host'];
			}

			// Re-derive Url state and replace the global.
			$newUrl = new Url();
			$newUrl->checkFilters($site->uriFilters());
			$url = $GLOBALS['url'] = $newUrl;

			// Replicate the page/content setup from 69.pages.php WITHOUT
			// running $pages->scheduler() (we don't want scheduled-publish
			// side effects during a build).
			$page = false;
			$content = array();
			$staticContent = $staticPages = buildStaticPages();
			$GLOBALS['staticContent'] = $staticContent;
			$GLOBALS['staticPages'] = $staticPages;
			$wai = $url->whereAmI();

			if ($site->homepage() && $wai === 'home') {
				$pageKey = $site->homepage();
				if ($pages->exists($pageKey)) {
					$url->setSlug($pageKey);
					$content[0] = $page = buildThePage();
				}
			} elseif ($wai === 'page') {
				$content[0] = $page = buildThePage();
			} elseif ($wai === 'tag') {
				$content = buildPagesByTag();
			} elseif ($wai === 'category') {
				$content = buildPagesByCategory();
			} elseif ($wai === 'home' || $wai === 'blog') {
				$content = buildPagesForHome();
			}
			if (isset($content[0])) {
				$page = $content[0];
			}
			if ($url->notFound()) {
				$content[0] = $page = buildErrorPage();
			}
			$GLOBALS['page'] = $page;
			$GLOBALS['content'] = $content;

			// Paginator state for this URL (mirrors 99.paginator.php).
			$this->setupPaginator($url);

			// Render the theme. The theme's init.php typically loads helper
			// classes; since $helper / $login / $searchJson are now globals,
			// the theme's `if (!isset($helper))` guards work across renders
			// and we can include init.php every time (it's idempotent).
			// index.php is the actual HTML template.
			ob_start();
			$themeDir = PATH_THEMES . $site->theme() . DS;
			if (is_file($themeDir . 'init.php')) {
				include $themeDir . 'init.php';
			}
			if (is_file($themeDir . 'index.php')) {
				include $themeDir . 'index.php';
			} else {
				ob_end_clean();
				return array('ok' => false, 'body' => '', 'notFound' => false, 'error' => 'theme index.php missing');
			}
			$body = ob_get_clean();

			return array(
				'ok' => true,
				'body' => $body,
				'notFound' => (bool) $url->notFound(),
				'error' => '',
			);
		} catch (Exception $e) {
			while (ob_get_level() > $bufLevel) {
				ob_end_clean();
			}
			return array('ok' => false, 'body' => '', 'notFound' => false, 'error' => $e->getMessage());
		} catch (Throwable $e) {
			while (ob_get_level() > $bufLevel) {
				ob_end_clean();
			}
			return array('ok' => false, 'body' => '', 'notFound' => false, 'error' => $e->getMessage());
		} finally {
			// Restore every snapshotted value so the surrounding admin
			// request continues uninterrupted.
			$_SERVER['REQUEST_URI'] = $saved['REQUEST_URI'];
			$_SERVER['QUERY_STRING'] = $saved['QUERY_STRING'];
			foreach (array('SERVER_PORT', 'HTTPS', 'HTTP_HOST', 'SERVER_NAME') as $k) {
				if ($saved[$k] === null) {
					unset($_SERVER[$k]);
				} else {
					$_SERVER[$k] = $saved[$k];
				}
			}
			$_GET = $saved['GET'];
			$GLOBALS['url'] = $saved['url'];
			$GLOBALS['page'] = $saved['page'];
			$GLOBALS['content'] = $saved['content'];
			$GLOBALS['staticContent'] = $saved['staticContent'];
			$GLOBALS['staticPages'] = $saved['staticPages'];
			if ($saved['WAI'] !== null) {
				$GLOBALS['WHERE_AM_I'] = $saved['WAI'];
			}
		}
	}

	private function setupPaginator($url)
	{
		global $site, $pages, $tags, $categories;
		$wai = $url->whereAmI();
		$currentPage = $url->pageNumber();
		Paginator::set('currentPage', $currentPage);

		if ($wai === 'tag') {
			$itemsPerPage = $site->itemsPerPage();
			$numberOfItems = $tags->numberOfPages($url->slug());
		} elseif ($wai === 'category') {
			$itemsPerPage = $site->itemsPerPage();
			$numberOfItems = $categories->numberOfPages($url->slug());
		} else {
			$itemsPerPage = $site->itemsPerPage();
			$numberOfItems = $pages->count(true);
		}
		Paginator::set('numberOfItems', $numberOfItems);
		Paginator::set('itemsPerPage', $itemsPerPage);
		$numberOfPages = ($itemsPerPage > 0) ? (int) ceil($numberOfItems / $itemsPerPage) : 1;
		Paginator::set('numberOfPages', $numberOfPages);
		Paginator::set('showItems', $itemsPerPage);
	}

	private function sitePathPrefix()
	{
		// HTML_PATH_ROOT is the URL prefix the site is mounted at (e.g. "/"
		// or "/bludit/"). Pages may render either DOMAIN_BASE-prefixed or
		// HTML_PATH_ROOT-prefixed URLs.
		return HTML_PATH_ROOT;
	}

	// ----------------------------------------------------------------------
	// HTML rewriting
	//
	// We rewrite three things in order:
	//   1. Strip the absolute site origin from all URLs.
	//   2. Strip the site's base path (HTML_PATH_ROOT) from root-relative
	//      URLs.
	//   3. Prepend the document-relative prefix for the current saved page.
	//
	// Pages are saved as path/index.html, so internal page links get
	// "/foo/bar" -> "prefix + foo/bar/index.html". Anything that looks like
	// a file (has a recognized extension) is kept as a file reference and
	// queued for fetching.
	// ----------------------------------------------------------------------
	private $fileExtRegex = '~\.(css|js|mjs|json|xml|txt|map|png|jpe?g|gif|webp|avif|svg|ico|bmp|tiff?|woff2?|ttf|otf|eot|mp4|webm|ogg|mp3|wav|flac|m4a|pdf|zip|gz|tar|br)([?#].*)?$~i';

	private function rewriteHtml(array &$state, $path, $html)
	{
		$prefix = $this->depthPrefix($path);
		$crawlOrigin = '';
		$siteOrigin = $this->originOf(DOMAIN_BASE);
		$basePath = rtrim($state['sitePathPrefix'], '/');

		$plugin = $this;
		$rewrite = function ($url) use ($plugin, &$state, $prefix, $crawlOrigin, $siteOrigin, $basePath) {
			return $plugin->rewriteRef($state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
		};

		// href / src / action / formaction / poster / data-* attributes that
		// hold URLs. The home/category cards use `data-background-image`
		// (read by JS to set the card background); lozad lazy-load uses
		// `data-src` / `data-background` / `data-iesrc`. We treat double-
		// and single-quoted values separately so we never have to worry
		// about embedded quotes.
		$attrs = array(
			'href', 'src', 'action', 'formaction', 'poster',
			'data-src', 'data-href', 'data-background', 'data-background-image',
			'data-bg', 'data-iesrc', 'data-poster',
		);
		foreach ($attrs as $attr) {
			$html = preg_replace_callback(
				'#(' . preg_quote($attr, '#') . '\s*=\s*)"([^"]*)"#i',
				function ($m) use ($rewrite) { return $m[1] . '"' . htmlspecialchars($rewrite(htmlspecialchars_decode($m[2], ENT_QUOTES)), ENT_QUOTES, 'UTF-8') . '"'; },
				$html
			);
			$html = preg_replace_callback(
				"#(" . preg_quote($attr, '#') . "\s*=\s*)'([^']*)'#i",
				function ($m) use ($rewrite) { return $m[1] . "'" . htmlspecialchars($rewrite(htmlspecialchars_decode($m[2], ENT_QUOTES)), ENT_QUOTES, 'UTF-8') . "'"; },
				$html
			);
		}

		// srcset: "url1 1x, url2 2x" or "url1 100w, url2 200w".
		$html = preg_replace_callback(
			'#(srcset\s*=\s*)"([^"]*)"#i',
			function ($m) use ($rewrite) { return $m[1] . '"' . $this->rewriteSrcset($m[2], $rewrite) . '"'; },
			$html
		);
		$html = preg_replace_callback(
			"#(srcset\s*=\s*)'([^']*)'#i",
			function ($m) use ($rewrite) { return $m[1] . "'" . $this->rewriteSrcset($m[2], $rewrite) . "'"; },
			$html
		);

		// meta http-equiv refresh (rare but possible).
		// CSS inside <style> tags + inline style="" attributes.
		$html = preg_replace_callback(
			'#(<style[^>]*>)(.*?)(</style>)#is',
			function ($m) use ($plugin, &$state, $path) { return $m[1] . $plugin->rewriteCss($state, $path, $m[2]) . $m[3]; },
			$html
		);
		$html = preg_replace_callback(
			'#(style\s*=\s*)"([^"]*)"#i',
			function ($m) use ($plugin, &$state, $path) { return $m[1] . '"' . htmlspecialchars($plugin->rewriteCss($state, $path, htmlspecialchars_decode($m[2], ENT_QUOTES)), ENT_QUOTES, 'UTF-8') . '"'; },
			$html
		);
		$html = preg_replace_callback(
			"#(style\s*=\s*)'([^']*)'#i",
			function ($m) use ($plugin, &$state, $path) { return $m[1] . "'" . htmlspecialchars($plugin->rewriteCss($state, $path, htmlspecialchars_decode($m[2], ENT_QUOTES)), ENT_QUOTES, 'UTF-8') . "'"; },
			$html
		);

		// Inline <script> bodies often contain string literals that hold
		// in-site URLs — e.g. theme code that does
		//     var uploadsFolder = '/bl-content/uploads/';
		//     var siteRoot = 'https://example.com/';
		// followed later by `fetch(uploadsFolder + 'index.json')` or
		// `siteRoot + slug`. The attribute-rewriting pass above can't see
		// those strings, so they survive into the static build as either
		// root-relative paths (broken when the build isn't hosted at the
		// webroot) or as the live site's absolute URL (which the
		// origin-strip pass below would then turn into bare root-relative
		// paths anyway). The rewriteJsUrl() helper handles two cases the
		// attribute path does not:
		//   1. Trailing-slash URLs are kept as directory prefixes (so
		//      JS concat keeps working) and the disk directory's
		//      contents are walked + enqueued so referenced JSON / data
		//      files get copied even though no HTML element links them.
		//   2. The crawl/site origins are stripped before relativising,
		//      same as for attributes.
		// Conservative match: only strings that look like in-site URLs
		// (absolute on this origin, or starting with "/"), no whitespace.
		$urlPattern = '(?:https?://[^\s"\'<>]+|/[^\s"\'<>]*)';
		$jsRewrite = function ($url) use ($plugin, &$state, $prefix, $crawlOrigin, $siteOrigin, $basePath) {
			return $plugin->rewriteJsUrl($state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath);
		};
		$html = preg_replace_callback(
			'~(<script\b(?![^>]*\bsrc\s*=)[^>]*>)(.*?)(</script>)~is',
			function ($m) use ($jsRewrite, $urlPattern) {
				$body = $m[2];
				$body = preg_replace_callback(
					'~(")(' . $urlPattern . ')(")~',
					function ($mm) use ($jsRewrite) { return $mm[1] . $jsRewrite($mm[2]) . $mm[3]; },
					$body
				);
				$body = preg_replace_callback(
					"~(')(" . $urlPattern . ")(')~",
					function ($mm) use ($jsRewrite) { return $mm[1] . $jsRewrite($mm[2]) . $mm[3]; },
					$body
				);
				return $m[1] . $body . $m[3];
			},
			$html
		);

		// Strip any remaining absolute-origin occurrences (e.g. inline JSON,
		// JS-LD blocks, OG meta tag content already handled above but be
		// safe). We only strip the origin, not paths, so JSON-LD URLs become
		// root-relative which is usually acceptable.
		if ($crawlOrigin !== '') {
			$html = str_replace($crawlOrigin, '', $html);
		}
		if ($siteOrigin !== '' && $siteOrigin !== $crawlOrigin) {
			$html = str_replace($siteOrigin, '', $html);
		}

		// Social cards (Open Graph, Twitter) and <link rel="canonical") all
		// REQUIRE absolute URLs — relative paths cause crawlers / link
		// previewers to either ignore the value or resolve it against the
		// wrong base. The origin-strip above turns those into bare paths
		// (or, in the case of og:url that was just the bare origin, into
		// an empty string). Re-absolutize them here using the effective
		// build URL (the "Production URL" plugin setting if set, else
		// $site->url()) so the static build's social previews work no
		// matter what hostname the local install is running on.
		$buildUrl = rtrim($this->effectiveBuildUrl(), '/');
		$buildOrigin = $this->originOf($buildUrl);
		if ($buildOrigin !== '' && $buildOrigin !== $siteOrigin) {
			$html = str_replace($buildOrigin, '', $html);
		}
		$absOrigin = $buildOrigin !== '' ? $buildOrigin : rtrim($siteOrigin, '/');
		$metaProps = '(?:og:url|og:image|og:image:url|og:image:secure_url|og:video|og:video:url|og:video:secure_url|og:audio|og:audio:url|og:audio:secure_url)';
		$metaNames = '(?:twitter:url|twitter:image|twitter:image:src|twitter:player|twitter:player:stream)';
		$imageProps = '(?:og:image|og:image:url|og:image:secure_url|og:video|og:video:url|og:video:secure_url|og:audio|og:audio:url|og:audio:secure_url)';
		$imageNames = '(?:twitter:image|twitter:image:src|twitter:player|twitter:player:stream)';

		// The attribute-rewriting pass above only enqueues URLs found in
		// href/src/etc. The companion plugin's "Open Graph default image"
		// and "Twitter default image" settings reach the page as
		// <meta property="og:image" content="…"> — so without this pass
		// the image file itself never gets copied into the static build
		// and previews end up broken. Scan the image-bearing OG / Twitter
		// meta tags and enqueue any in-site URL as an asset.
		$enqueueFromMeta = function ($m) use (&$state) {
			$url = htmlspecialchars_decode($m[1], ENT_QUOTES);
			$this->enqueue($state, $url, 'asset');
			return $m[0];
		};
		preg_replace_callback(
			'#<meta\s+property\s*=\s*"' . $imageProps . '"\s+content\s*=\s*"([^"]*)"#i',
			$enqueueFromMeta,
			$html
		);
		preg_replace_callback(
			'#<meta\s+content\s*=\s*"([^"]*)"\s+property\s*=\s*"' . $imageProps . '"#i',
			$enqueueFromMeta,
			$html
		);
		preg_replace_callback(
			'#<meta\s+name\s*=\s*"' . $imageNames . '"\s+content\s*=\s*"([^"]*)"#i',
			$enqueueFromMeta,
			$html
		);
		preg_replace_callback(
			'#<meta\s+content\s*=\s*"([^"]*)"\s+name\s*=\s*"' . $imageNames . '"#i',
			$enqueueFromMeta,
			$html
		);

		if ($absOrigin !== '') {
			$siteUrl = $buildUrl;
			$absolutize = function ($val) use ($absOrigin, $siteUrl) {
				$decoded = htmlspecialchars_decode($val, ENT_QUOTES);
				if ($decoded === '') {
					$decoded = $siteUrl . '/';
				} elseif ($decoded[0] === '/') {
					$decoded = $absOrigin . $decoded;
				} elseif (!preg_match('#^https?://#i', $decoded)) {
					return $val;
				}
				return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
			};
			$html = preg_replace_callback(
				'#(<meta\s+property\s*=\s*"' . $metaProps . '"\s+content\s*=\s*")([^"]*)(")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
			$html = preg_replace_callback(
				'#(<meta\s+content\s*=\s*")([^"]*)("\s+property\s*=\s*"' . $metaProps . '")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
			$html = preg_replace_callback(
				'#(<meta\s+name\s*=\s*"' . $metaNames . '"\s+content\s*=\s*")([^"]*)(")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
			$html = preg_replace_callback(
				'#(<meta\s+content\s*=\s*")([^"]*)("\s+name\s*=\s*"' . $metaNames . '")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
			$html = preg_replace_callback(
				'#(<link\s+rel\s*=\s*"canonical"\s+href\s*=\s*")([^"]*)(")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
			$html = preg_replace_callback(
				'#(<link\s+href\s*=\s*")([^"]*)("\s+rel\s*=\s*"canonical")#i',
				function ($m) use ($absolutize) { return $m[1] . $absolutize($m[2]) . $m[3]; },
				$html
			);
		}

		return $html;
	}

	private function rewriteSrcset($value, callable $rewrite)
	{
		$parts = explode(',', $value);
		$out = array();
		foreach ($parts as $part) {
			$part = trim($part);
			if ($part === '') {
				continue;
			}
			$bits = preg_split('/\s+/', $part, 2);
			$url = $bits[0];
			$descriptor = isset($bits[1]) ? ' ' . $bits[1] : '';
			$out[] = htmlspecialchars($rewrite(htmlspecialchars_decode($url, ENT_QUOTES)), ENT_QUOTES, 'UTF-8') . $descriptor;
		}
		return implode(', ', $out);
	}

	/**
	 * Rewrite a single URL reference (href/src/etc.) AND enqueue it for the
	 * crawler if it's an in-site resource.
	 *
	 * `$queue` controls whether we add to the BFS queue. Always true from
	 * HTML; CSS sub-references also queue so background images get copied.
	 */
	public function rewriteRef(array &$state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath, $queue)
	{
		$original = $url;
		$url = trim($url);
		if ($url === '' || $url[0] === '#') {
			return $original;
		}
		// Skip unsupported schemes outright.
		if (preg_match('#^(mailto|tel|javascript|data):#i', $url)) {
			return $original;
		}
		// Resolve absolute URLs.
		if (preg_match('#^https?://#i', $url)) {
			$urlOrigin = $this->originOf($url);
			if (strcasecmp($urlOrigin, $crawlOrigin) !== 0 && strcasecmp($urlOrigin, $siteOrigin) !== 0) {
				// External — leave as-is.
				return $original;
			}
			$path = parse_url($url, PHP_URL_PATH);
			$query = parse_url($url, PHP_URL_QUERY);
			$frag = parse_url($url, PHP_URL_FRAGMENT);
			$path = $path === null || $path === '' ? '/' : $path;
			$rebuilt = $path . ($query ? '?' . $query : '') . ($frag ? '#' . $frag : '');
			$url = $rebuilt;
		} elseif ($url[0] !== '/') {
			// Already document-relative — leave as-is.
			return $original;
		}

		// Strip site base path if present.
		if ($basePath !== '' && $basePath !== '/' && strpos($url, $basePath . '/') === 0) {
			$url = substr($url, strlen($basePath));
		} elseif ($basePath !== '' && $basePath !== '/' && $url === $basePath) {
			$url = '/';
		}

		// Split path/query/fragment.
		$frag = '';
		$hashPos = strpos($url, '#');
		if ($hashPos !== false) {
			$frag = substr($url, $hashPos);
			$url = substr($url, 0, $hashPos);
		}
		$query = '';
		$qPos = strpos($url, '?');
		if ($qPos !== false) {
			$query = substr($url, $qPos);
			$url = substr($url, 0, $qPos);
		}

		if ($url === '' || $url === '/') {
			$url = '/';
		}

		// Classify as file vs. page.
		$isFile = (bool) preg_match($this->fileExtRegex, $url);

		// Enqueue.
		if ($queue) {
			$enqueuePath = $url . $query;
			$this->enqueue($state, $enqueuePath, $isFile ? 'asset' : 'page');
		}

		// Build the rewritten reference. File references keep their
		// extension; page references point at the SAVED page's directory
		// (htmlRelLinkTarget) rather than the directory's index.html,
		// so the rendered HTML doesn't read like a sitemap. Directory
		// indexing on the hosting webserver does the rest.
		$inner = ltrim($url, '/');
		if ($isFile) {
			$ref = $prefix . $inner . $query . $frag;
		} else {
			$queryNoQ = $query === '' ? '' : ltrim($query, '?');
			$rel = $this->htmlRelLinkTarget($url, $queryNoQ);
			$ref = $prefix . $rel . $frag;
		}
		// Collapse to "./" so href is never an empty string.
		return $ref === '' ? './' : $ref;
	}

	/**
	 * Rewrite a URL that appears as a string literal inside an inline
	 * <script> block. Differs from rewriteRef() in two important ways:
	 *
	 *   - URLs that end in "/" are kept as directory prefixes (no
	 *     index.html appended). Theme JS commonly stores a folder URL
	 *     in a variable and concatenates a filename onto it later;
	 *     turning the prefix into a page link would corrupt every
	 *     resulting URL.
	 *
	 *   - For directory-prefix URLs we walk the corresponding disk
	 *     directory and enqueue every regular asset file underneath.
	 *     The crawler can't follow JS string concatenation, so without
	 *     this step files referenced only by JS (e.g. a search index
	 *     JSON, a fonts manifest, an upload-listing JSON) would never
	 *     be copied into the build dir.
	 *
	 * Non-directory URLs delegate straight to rewriteRef().
	 */
	public function rewriteJsUrl(array &$state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath)
	{
		$original = $url;
		$url = trim($url);
		if ($url === '' || $url[0] === '#') {
			return $original;
		}
		if (preg_match('#^(mailto|tel|javascript|data):#i', $url)) {
			return $original;
		}

		// Resolve to an in-site path-only URL, or bail if external.
		if (preg_match('#^https?://#i', $url)) {
			$urlOrigin = $this->originOf($url);
			if (strcasecmp($urlOrigin, $crawlOrigin) !== 0 && strcasecmp($urlOrigin, $siteOrigin) !== 0) {
				return $original;
			}
			$path = parse_url($url, PHP_URL_PATH);
			$query = parse_url($url, PHP_URL_QUERY);
			$frag = parse_url($url, PHP_URL_FRAGMENT);
			$path = ($path === null || $path === '') ? '/' : $path;
			$url = $path . ($query ? '?' . $query : '') . ($frag ? '#' . $frag : '');
		} elseif ($url[0] !== '/') {
			return $original;
		}

		// Strip the site base path (if installed in a subdirectory).
		if ($basePath !== '' && $basePath !== '/' && strpos($url, $basePath . '/') === 0) {
			$url = substr($url, strlen($basePath));
		} elseif ($basePath !== '' && $basePath !== '/' && $url === $basePath) {
			$url = '/';
		}

		// Split off query / fragment for the trailing-slash check.
		$frag = '';
		$hashPos = strpos($url, '#');
		if ($hashPos !== false) {
			$frag = substr($url, $hashPos);
			$url = substr($url, 0, $hashPos);
		}
		$query = '';
		$qPos = strpos($url, '?');
		if ($qPos !== false) {
			$query = substr($url, $qPos);
			$url = substr($url, 0, $qPos);
		}

		// Directory prefix: keep as a relative directory path and walk the
		// matching disk directory to make sure JS-referenced files are
		// included in the build.
		if (substr($url, -1) === '/') {
			$diskDir = $this->mapToDiskDir($url);
			if ($diskDir !== null && is_dir($diskDir)) {
				$this->enqueueDirContents($state, $url, $diskDir);
			}
			$inner = ltrim($url, '/');
			return $prefix . $inner . $query . $frag;
		}

		// Non-directory: hand off to the existing path that classifies
		// file vs page, enqueues, and relativises.
		return $this->rewriteRef($state, $original, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
	}

	/**
	 * Like mapToDisk() but maps a URL DIRECTORY (no extension required)
	 * to a disk directory under PATH_ROOT, applying the same denylist
	 * (admin, databases, workspaces, tmp, dotfile segments) and lexical
	 * traversal protection. Returns null if the URL doesn't map to a
	 * publicly-walkable directory.
	 */
	private function mapToDiskDir($urlPath)
	{
		$clean = $this->cleanPath($urlPath);
		if ($clean === null) {
			return null;
		}
		$rel = trim(ltrim($clean['path'], '/'), '/');
		if ($rel === '') {
			return null;
		}
		$denyPrefixes = array(
			ADMIN_URI_FILTER,
			'bl-content/databases',
			'bl-content/workspaces',
			'bl-content/tmp',
		);
		foreach ($denyPrefixes as $deny) {
			if ($rel === $deny || strpos($rel, $deny . '/') === 0) {
				return null;
			}
		}
		foreach (explode('/', $rel) as $seg) {
			if ($seg !== '' && $seg[0] === '.') {
				return null;
			}
		}
		return $this->resolveSafePath(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR), $rel);
	}

	/**
	 * Walk a disk directory and enqueue every regular asset file inside
	 * it. Asset extension matching uses the same regex as the rest of
	 * the plugin so we don't accidentally copy things that aren't safe
	 * to expose statically. Dedupe is handled by enqueue().
	 */
	private function enqueueDirContents(array &$state, $urlDirPath, $diskDir)
	{
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($diskDir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
		} catch (Throwable $e) {
			return;
		}
		$rootLen = strlen(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR)) + 1;
		foreach ($it as $entry) {
			if (!$entry->isFile()) {
				continue;
			}
			$abs = $entry->getPathname();
			if (strncmp($abs, rtrim(PATH_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $rootLen) !== 0) {
				continue;
			}
			$relToRoot = substr($abs, $rootLen);
			$relToRoot = str_replace(DIRECTORY_SEPARATOR, '/', $relToRoot);
			if (!preg_match($this->fileExtRegex, $relToRoot)) {
				continue;
			}
			$skip = false;
			foreach (explode('/', $relToRoot) as $seg) {
				if ($seg !== '' && $seg[0] === '.') { $skip = true; break; }
			}
			if ($skip) {
				continue;
			}
			$this->enqueue($state, '/' . $relToRoot, 'asset');
		}
	}

	// ----------------------------------------------------------------------
	// CSS rewriting
	// ----------------------------------------------------------------------
	public function rewriteCss(array &$state, $path, $css)
	{
		$prefix = $this->depthPrefix($path);
		$crawlOrigin = '';
		$siteOrigin = $this->originOf(DOMAIN_BASE);
		$basePath = rtrim($state['sitePathPrefix'], '/');

		$plugin = $this;
		$rewriteOne = function ($u) use ($plugin, &$state, $prefix, $crawlOrigin, $siteOrigin, $basePath) {
			return $plugin->rewriteRef($state, $u, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
		};

		// url(...) and url("...") and url('...')
		$css = preg_replace_callback(
			'#url\(\s*("([^"]*)"|\'([^\']*)\'|([^)]*))\s*\)#i',
			function ($m) use ($rewriteOne) {
				$raw = isset($m[2]) && $m[2] !== '' ? $m[2] : (isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) ? $m[4] : ''));
				$quote = (isset($m[2]) && $m[2] !== '') ? '"' : ((isset($m[3]) && $m[3] !== '') ? "'" : '');
				$new = $rewriteOne(trim($raw));
				return 'url(' . $quote . $new . $quote . ')';
			},
			$css
		);

		// @import "..." (without url())
		$css = preg_replace_callback(
			'#@import\s+("([^"]+)"|\'([^\']+)\')#i',
			function ($m) use ($rewriteOne) {
				$raw = isset($m[2]) && $m[2] !== '' ? $m[2] : $m[3];
				$quote = (isset($m[2]) && $m[2] !== '') ? '"' : "'";
				return '@import ' . $quote . $rewriteOne($raw) . $quote;
			},
			$css
		);

		return $css;
	}

	// ----------------------------------------------------------------------
	// Path mapping (URL -> filesystem)
	// ----------------------------------------------------------------------
	private function htmlSavePath($outDir, $path)
	{
		$clean = $this->cleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = $this->htmlRelFilename($clean['path'], $clean['query']);
		return $this->resolveSafePath($outDir, $rel);
	}

	/**
	 * Map a (path, query) pair to the relative filename used to STORE the
	 * static copy on disk. Always ends in "index.html" so the file is
	 * directly servable.
	 *
	 *   /                   -> index.html
	 *   /foo                -> foo/index.html
	 *   /?page=2            -> page/2/index.html
	 *   /category/x?page=2  -> category/x/page/2/index.html
	 *   /?page=1            -> index.html        (page 1 == unparameterised)
	 *   /?preview=…         -> index.preview=….html  (unknown queries
	 *                          keep the legacy slug-in-filename form so
	 *                          they remain uniquely addressable)
	 *
	 * The companion htmlRelLinkTarget() returns the same locations as
	 * DIRECTORY paths so internal page links can rely on the webserver's
	 * directory-index resolution and don't need the explicit /index.html
	 * suffix in every href.
	 */
	private function htmlRelFilename($path, $query)
	{
		$pathPart = trim($path, '/');
		$pageNum = $this->parsePageQuery($query);

		// Page 1 collapses onto the unparameterised page; bytes are
		// identical so there's no value in writing two copies.
		if ($query === '' || $pageNum === 1) {
			return $pathPart === '' ? 'index.html' : $pathPart . '/index.html';
		}
		if ($pageNum !== null) {
			$sub = 'page/' . $pageNum . '/index.html';
			return $pathPart === '' ? $sub : $pathPart . '/' . $sub;
		}

		// Unknown query - keep the legacy filename layout. These are rare
		// (drafts, search filters, ad-hoc params) and don't have a
		// natural directory translation.
		$qSafe = preg_replace('/[^A-Za-z0-9_\-=&]/', '_', $query);
		return $pathPart === '' ? 'index.' . $qSafe . '.html' : $pathPart . '/index.' . $qSafe . '.html';
	}

	/**
	 * Map a (path, query) pair to the DIRECTORY URL used in <a href>
	 * links inside rendered HTML. The directory's index.html is what
	 * actually gets served by any directory-indexing webserver, so we
	 * can drop the explicit suffix and produce cleaner-looking links.
	 *
	 *   /                   -> "./"
	 *   /foo                -> "foo/"
	 *   /?page=2            -> "page/2/"
	 *   /category/x?page=2  -> "category/x/page/2/"
	 *   /?page=1            -> "./"   (same target as /)
	 *
	 * For queries we don't know how to fold into a directory the link
	 * falls back to the explicit .html filename so it still resolves.
	 */
	private function htmlRelLinkTarget($path, $query)
	{
		$pathPart = trim($path, '/');
		$pageNum = $this->parsePageQuery($query);

		// Root returns "" rather than "./" so that callers can concatenate
		// it with a depth prefix without ending up with a noisy
		// "../.././" — the surrounding rewriteRef() rule collapses an
		// empty final result to "./".
		if ($query === '' || $pageNum === 1) {
			return $pathPart === '' ? '' : $pathPart . '/';
		}
		if ($pageNum !== null) {
			$sub = 'page/' . $pageNum . '/';
			return $pathPart === '' ? $sub : $pathPart . '/' . $sub;
		}
		$qSafe = preg_replace('/[^A-Za-z0-9_\-=&]/', '_', $query);
		return $pathPart === '' ? 'index.' . $qSafe . '.html' : $pathPart . '/index.' . $qSafe . '.html';
	}

	/**
	 * Recognise the "?page=N" pagination query. Returns the integer N or
	 * null when the query isn't a pure page-number query (so callers
	 * fall back to the generic filename form). Refuses queries that
	 * include any other params — keeping them separate avoids silently
	 * dropping data like "?page=2&preview=…".
	 */
	private function parsePageQuery($query)
	{
		if ($query === '' || !preg_match('/^page=(\d+)$/', $query, $m)) {
			return null;
		}
		$n = (int) $m[1];
		return $n > 0 ? $n : null;
	}

	private function fileSavePath($outDir, $path)
	{
		$clean = $this->cleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = ltrim($clean['path'], '/');
		if ($rel === '' || substr($rel, -1) === '/') {
			return null;
		}
		return $this->resolveSafePath($outDir, $rel);
	}

	private function cleanPath($path)
	{
		// Split path from query.
		$query = '';
		$qPos = strpos($path, '?');
		if ($qPos !== false) {
			$query = substr($path, $qPos + 1);
			$path = substr($path, 0, $qPos);
		}
		$path = rawurldecode($path);
		// Reject control chars and null bytes.
		if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
			return null;
		}
		// Reject traversal.
		$segments = explode('/', $path);
		foreach ($segments as $s) {
			if ($s === '..') {
				return null;
			}
		}
		return array('path' => $path, 'query' => $query);
	}

	private function resolveSafePath($outDir, $relative)
	{
		$relative = str_replace('/', DIRECTORY_SEPARATOR, $relative);
		// Strip any drive letters or leading separators.
		$relative = ltrim($relative, DIRECTORY_SEPARATOR);
		$full = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
		// Final canonicalisation: ensure the resolved path stays under outDir.
		$normalizedBase = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$normalized = $this->lexicallyNormalize($full);
		if (strpos($normalized, $normalizedBase) !== 0) {
			return null;
		}
		return $normalized;
	}

	private function lexicallyNormalize($path)
	{
		$isAbs = ($path !== '' && ($path[0] === DIRECTORY_SEPARATOR || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Za-z]:/', $path))));
		$parts = preg_split('#[' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+#', $path);
		$stack = array();
		foreach ($parts as $p) {
			if ($p === '' || $p === '.') {
				continue;
			}
			if ($p === '..') {
				if (!empty($stack)) {
					array_pop($stack);
				}
				continue;
			}
			$stack[] = $p;
		}
		$result = implode(DIRECTORY_SEPARATOR, $stack);
		return $isAbs ? DIRECTORY_SEPARATOR . $result : $result;
	}

	/**
	 * Map a URL path to a file under PATH_ROOT, but only for paths that:
	 *   - resolve lexically inside PATH_ROOT (no traversal),
	 *   - are NOT under one of the deny prefixes (admin URI, the JSON
	 *     databases, workspaces, tmp, dotfiles like .git),
	 *   - have an asset-style extension we know how to serve statically.
	 *
	 * Returns the absolute filesystem path, or null if the URL doesn't
	 * map to a publicly-servable file.
	 */
	private function mapToDisk($path)
	{
		$clean = $this->cleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = ltrim($clean['path'], '/');
		if ($rel === '') {
			return null;
		}

		// Refuse anything that isn't an asset by extension.
		if (!preg_match($this->fileExtRegex, $rel)) {
			return null;
		}

		// Deny dotfiles, hidden dirs, and the private content dirs.
		$denyPrefixes = array(
			ADMIN_URI_FILTER . '/',
			'bl-content/databases/',
			'bl-content/workspaces/',
			'bl-content/tmp/',
		);
		foreach ($denyPrefixes as $deny) {
			if (strpos($rel, $deny) === 0) {
				return null;
			}
		}
		// Block .anything segments (.git, .env, .htaccess, etc.).
		foreach (explode('/', $rel) as $seg) {
			if ($seg !== '' && $seg[0] === '.') {
				return null;
			}
		}

		$candidate = $this->resolveSafePath(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR), $rel);
		return $candidate;
	}

	/**
	 * Depth-prefix for document-relative URLs from the saved location of
	 * the current resource. The depth is computed from the actual save
	 * filename so that pages whose query expands into a subdirectory
	 * (e.g. "?page=2" -> "page/2/index.html") get the right number of
	 * leading "../" segments. Asset files (CSS/JS/etc.) are stored at
	 * their request path verbatim, so their depth is just the number of
	 * directory components above the file.
	 */
	private function depthPrefix($path)
	{
		$clean = $this->cleanPath($path);
		if ($clean === null) {
			return '';
		}
		if (preg_match($this->fileExtRegex, $clean['path'])) {
			$p = trim($clean['path'], '/');
			if ($p === '') {
				return '';
			}
			$depth = count(explode('/', $p)) - 1;
			return $depth > 0 ? str_repeat('../', $depth) : '';
		}
		$rel = $this->htmlRelFilename($clean['path'], $clean['query']);
		$depth = substr_count($rel, '/');
		return $depth > 0 ? str_repeat('../', $depth) : '';
	}

	private function originOf($url)
	{
		$p = parse_url($url);
		if (!$p || !isset($p['scheme']) || !isset($p['host'])) {
			return '';
		}
		$o = $p['scheme'] . '://' . $p['host'];
		if (isset($p['port'])) {
			$o .= ':' . $p['port'];
		}
		return $o;
	}

	// The URL the build should treat as the public-facing origin. Falls
	// back to $site->url() so existing installs keep working when the
	// "Production URL" field is left blank.
	private function effectiveBuildUrl()
	{
		$override = trim((string) $this->getValue('productionUrl'));
		if ($override !== '') {
			return $override;
		}
		global $site;
		return $site->url();
	}

	// ----------------------------------------------------------------------
	// Filesystem helpers
	// ----------------------------------------------------------------------
	public function outputDir()
	{
		return rtrim(PATH_CONTENT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::OUTPUT_DIRNAME . DIRECTORY_SEPARATOR;
	}

	private function ensureDir($dir)
	{
		if (is_dir($dir)) {
			return true;
		}
		return @mkdir($dir, defined('DIR_PERMISSIONS') ? DIR_PERMISSIONS : 0755, true);
	}

	/**
	 * Atomically write a file. Returns the number of bytes written on
	 * success, or FALSE on any failure (mkdir, write, or rename). The
	 * boolean false (rather than int 0) is what lets callers distinguish
	 * "wrote an empty file" from "couldn't write" and bump the error
	 * counter accordingly.
	 */
	private function writeFile($path, $contents)
	{
		if (!$this->ensureDir(dirname($path))) {
			$this->log('ERROR mkdir ' . dirname($path));
			return false;
		}
		$tmp = $path . '.part';
		$wrote = @file_put_contents($tmp, $contents);
		if ($wrote === false) {
			$this->log('ERROR write ' . $path);
			return false;
		}
		if (!@rename($tmp, $path)) {
			@unlink($tmp);
			$this->log('ERROR rename ' . $path);
			return false;
		}
		return $wrote;
	}

	private function clearDir($dir)
	{
		if (!is_dir($dir)) {
			return;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($it as $entry) {
			$p = $entry->getPathname();
			// Belt-and-braces: never delete anything outside our output dir.
			if (strpos($p, $dir) !== 0) {
				continue;
			}
			if ($entry->isDir()) {
				@rmdir($p);
			} else {
				@unlink($p);
			}
		}
	}

	// ----------------------------------------------------------------------
	// Logging
	// ----------------------------------------------------------------------
	private function logPath()
	{
		return $this->workspace() . self::LOG_FILENAME;
	}

	private function logReset()
	{
		@file_put_contents($this->logPath(), '');
	}

	private function log($msg)
	{
		@file_put_contents($this->logPath(), '[' . date('H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
	}

	private function readLogTail()
	{
		$p = $this->logPath();
		if (!is_file($p)) {
			return '';
		}
		$raw = @file_get_contents($p);
		if ($raw === false) {
			return '';
		}
		$lines = preg_split('/\r?\n/', rtrim($raw, "\r\n"));
		if (count($lines) > self::MAX_LOG_LINES) {
			$lines = array_slice($lines, -self::MAX_LOG_LINES);
		}
		return implode(PHP_EOL, $lines);
	}

	// ----------------------------------------------------------------------
	// Form rendering helpers (same conventions as the companion plugin so the
	// admin UI stays visually consistent)
	// ----------------------------------------------------------------------
	private function openCard($titleKey, $icon = null, $subtitleKey = null)
	{
		global $L;
		$html = '<div class="card mb-4 shadow-sm">';
		$html .= '<div class="card-header bg-light">';
		if ($icon) {
			$html .= '<span class="fa fa-' . $icon . ' mr-2"></span>';
		}
		$html .= '<strong>' . $L->get($titleKey) . '</strong>';
		$html .= '</div>';
		$html .= '<div class="card-body">';
		if ($subtitleKey) {
			$html .= '<p class="text-muted mb-4">' . $L->get($subtitleKey) . '</p>';
		}
		return $html;
	}

	private function closeCard()
	{
		return '</div></div>';
	}

	private function textField($name, $labelText, $tip = null)
	{
		$html = '<div class="form-group">';
		$html .= '<label for="sgj_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<input id="sgj_' . $name . '" class="form-control" name="' . $name . '" type="text" dir="auto" value="' . $this->getValue($name) . '">';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}

	private function numberField($name, $labelText, $min = null, $tip = null)
	{
		$attrs = '';
		if ($min !== null) {
			$attrs .= ' min="' . (int) $min . '"';
		}
		$html = '<div class="form-group">';
		$html .= '<label for="sgj_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<input id="sgj_' . $name . '" class="form-control" name="' . $name . '" type="number"' . $attrs . ' value="' . (int) $this->getValue($name) . '">';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}

	private function textareaField($name, $labelText, $tip = null, $rows = 4)
	{
		$html = '<div class="form-group">';
		$html .= '<label for="sgj_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<textarea id="sgj_' . $name . '" class="form-control" name="' . $name . '" rows="' . (int) $rows . '" style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.85rem;">' . $this->getValue($name) . '</textarea>';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}
}

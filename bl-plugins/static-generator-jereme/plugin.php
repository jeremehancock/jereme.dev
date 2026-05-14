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
	const DEFAULT_MAX_URLS = 500;
	const CRAWL_TIMEOUT = 30;
	const CRAWL_USER_AGENT = 'BluditStaticGenerator/1.0';

	public function init()
	{
		$this->dbFields = array(
			'excludePaths' => '',
			'maxUrls' => self::DEFAULT_MAX_URLS,

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

		$html = '<div class="alert alert-primary mb-4" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		// ============ SECTION: Generate ===================================
		$html .= $this->openCard('sgj-section-action', 'hammer', 'sgj-section-action-subtitle');

		$confirmAttr = htmlspecialchars($L->get('sgj-build-confirm'), ENT_QUOTES, 'UTF-8');
		$buildLabel = htmlspecialchars($L->get('sgj-build-button'), ENT_QUOTES, 'UTF-8');
		$html .= '<button type="submit" class="btn btn-primary" name="action" value="build"'
			. ' onclick="return confirm(\'' . $confirmAttr . '\');">'
			. '<span class="fa fa-bolt mr-2"></span>' . $buildLabel
			. '</button>';

		$html .= $this->closeCard();

		// ============ SECTION: Settings ===================================
		$html .= $this->openCard('sgj-section-settings', 'cog');
		$html .= $this->numberField('maxUrls', $L->get('sgj-max-urls-label'), 1, $L->get('sgj-max-urls-tip'));
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
		$html .= '<li>' . $L->get('sgj-howitworks-4') . '</li>';
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
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
			$html .= '<details><summary>' . $L->get('sgj-status-log') . '</summary>';
			$html .= '<pre style="max-height: 320px; overflow: auto; background: #f8f9fa; padding: 0.75rem; border: 1px solid #dee2e6; font-size: 0.8rem;">'
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
		// changed maxUrls / excludePaths value silently reverts.
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

		$crawl = $this->resolveCrawlBase();
		$this->log('Crawl base: ' . $crawl['url'] . ($crawl['host'] !== '' ? ' (Host: ' . $crawl['host'] . ')' : ''));

		$state = array(
			'crawlBase' => $crawl['url'],
			'crawlHost' => $crawl['host'],
			'sitePathPrefix' => $this->sitePathPrefix(),
			'outDir' => $outDir,
			'queue' => array(),
			'enqueued' => array(),
			'urlsFetched' => 0,
			'bytesWritten' => 0,
			'errors' => 0,
			'maxUrls' => max(1, (int) $this->getValue('maxUrls')),
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
		// Always skip admin + databases.
		$alwaysSkip = array(
			'/' . ADMIN_URI_FILTER . '/',
			'/' . ADMIN_URI_FILTER,
			'/bl-content/databases/',
			'/bl-content/workspaces/',
			'/bl-content/tmp/',
		);
		foreach ($alwaysSkip as $prefix) {
			if (strpos($path, $prefix) === 0) {
				return true;
			}
		}
		foreach ($excludePaths as $prefix) {
			if (strpos($path, $prefix) === 0) {
				return true;
			}
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
	// ----------------------------------------------------------------------
	private function processItem(array &$state, array $item)
	{
		$path = $item['path'];
		$kind = $item['kind'];

		// Try to serve from disk for asset paths (theme, uploads, kernel, plugins).
		$diskPath = $this->mapToDisk($path);
		if ($diskPath !== null && is_file($diskPath) && $kind !== 'page') {
			$this->saveAssetFromDisk($state, $path, $diskPath);
			return;
		}

		// Fetch via HTTP.
		$url = $this->joinUrl($state['crawlBase'], $path);
		$fetch = $this->httpFetch($url, $state['crawlHost']);
		if (!$fetch['ok']) {
			$state['errors']++;
			$this->log('ERROR ' . $fetch['status'] . ' ' . $url . ' (' . $fetch['error'] . ')');
			return;
		}
		$state['urlsFetched']++;
		$this->log('OK  ' . $fetch['status'] . ' ' . $url . ' [' . $fetch['contentType'] . ']');

		$body = $fetch['body'];
		$ct = strtolower($fetch['contentType']);
		$isHtml = (strpos($ct, 'text/html') !== false) || ($kind === 'page' && $body !== '' && stripos($body, '<html') !== false);

		if ($isHtml) {
			$savePath = $this->htmlSavePath($state['outDir'], $path);
			if ($savePath === null) {
				$state['errors']++;
				$this->log('ERROR refusing unsafe save path for ' . $path);
				return;
			}
			$rewritten = $this->rewriteHtml($state, $path, $body);
			$bytes = $this->writeFile($savePath, $rewritten);
			$state['bytesWritten'] += $bytes;
		} elseif (strpos($ct, 'text/css') !== false || preg_match('#\.css($|\?)#i', $path)) {
			$savePath = $this->fileSavePath($state['outDir'], $path);
			if ($savePath === null) {
				$state['errors']++;
				return;
			}
			$rewritten = $this->rewriteCss($state, $path, $body);
			$bytes = $this->writeFile($savePath, $rewritten);
			$state['bytesWritten'] += $bytes;
		} else {
			$savePath = $this->fileSavePath($state['outDir'], $path);
			if ($savePath === null) {
				$state['errors']++;
				return;
			}
			$bytes = $this->writeFile($savePath, $body);
			$state['bytesWritten'] += $bytes;
		}
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
			$state['urlsFetched']++;
			$state['bytesWritten'] += $bytes;
			$this->log('OK  disk  ' . $path . ' [text/css]');
			return;
		}
		if (!$this->ensureDir(dirname($savePath))) {
			$state['errors']++;
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
	// HTTP fetch
	//
	// $hostHeader, when non-empty, overrides the Host header — necessary
	// when we cURL to a loopback address (e.g. 127.0.0.1:80) but need the
	// webserver to route to the vhost the user is actually visiting. Cert
	// verification is relaxed for loopback URLs since we're talking to
	// ourselves; for any other host we keep full TLS validation.
	// ----------------------------------------------------------------------
	private function httpFetch($url, $hostHeader = '')
	{
		$isLoopback = $this->isLoopbackUrl($url);
		$ch = curl_init();
		$opts = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_TIMEOUT => self::CRAWL_TIMEOUT,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_USERAGENT => self::CRAWL_USER_AGENT,
			CURLOPT_HEADER => false,
			CURLOPT_COOKIE => '',
			CURLOPT_COOKIEFILE => '',
			CURLOPT_COOKIEJAR => '',
			CURLOPT_SSL_VERIFYPEER => $isLoopback ? false : true,
			CURLOPT_SSL_VERIFYHOST => $isLoopback ? 0 : 2,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		);
		if ($hostHeader !== '') {
			$opts[CURLOPT_HTTPHEADER] = array('Host: ' . $hostHeader);
		}
		curl_setopt_array($ch, $opts);
		$body = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$effective = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$err = curl_error($ch);
		curl_close($ch);
		// If a redirect bounced us off-origin, refuse — we don't want to
		// archive third-party content under our build dir.
		if ($body !== false && $effective !== '') {
			$originalOrigin = $this->originOf($url);
			$effectiveOrigin = $this->originOf($effective);
			if ($originalOrigin !== '' && $effectiveOrigin !== '' && strcasecmp($originalOrigin, $effectiveOrigin) !== 0) {
				return array(
					'ok' => false,
					'status' => $status,
					'contentType' => $contentType,
					'body' => '',
					'error' => 'cross-origin redirect to ' . $effective,
				);
			}
		}
		return array(
			'ok' => ($body !== false && $status >= 200 && $status < 400),
			'status' => $status,
			'contentType' => $contentType,
			'body' => $body === false ? '' : $body,
			'error' => $err,
		);
	}

	private function joinUrl($base, $path)
	{
		return rtrim($base, '/') . $path;
	}

	/**
	 * Pick a URL the crawler can actually reach. Tries (in order):
	 *   1. http://127.0.0.1:SERVER_PORT  — works for `php -S`, Vagrant
	 *      port-forwards (where HTTP_HOST is the host-side port and PHP
	 *      sees the inside-VM port), and any setup where the webserver
	 *      listens on TCP loopback.
	 *   2. http://SERVER_ADDR:SERVER_PORT — if SERVER_ADDR is not 127.0.0.1.
	 *   3. scheme://HTTP_HOST — the URL the admin is currently using;
	 *      works for production where DNS resolves back to the same box.
	 *   4. $site->url() as a final fallback.
	 *
	 * When the chosen URL is a loopback IP, we send a Host header matching
	 * HTTP_HOST so the webserver routes to the right vhost and Bludit
	 * renders canonical links using the configured site URL.
	 *
	 * Returns array{url:string, host:string} where host is '' when no
	 * Host-header override is needed.
	 */
	private function resolveCrawlBase()
	{
		$candidates = $this->loopbackCandidates();
		foreach ($candidates as $cand) {
			if ($this->probeUrl($cand['url'], $cand['host'])) {
				$this->log('Probe OK: ' . $cand['url'] . ($cand['host'] !== '' ? ' (Host: ' . $cand['host'] . ')' : ''));
				return $cand;
			}
			$this->log('Probe failed: ' . $cand['url']);
		}
		global $site;
		return array('url' => rtrim($site->url(), '/'), 'host' => '');
	}

	private function loopbackCandidates()
	{
		$port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 0;
		$addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
		$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		// Reject malformed Host headers up front so we don't pass them
		// through to cURL.
		if ($httpHost !== '' && !preg_match('/^[A-Za-z0-9.\-]+(?::[0-9]{1,5})?$/', $httpHost) && !preg_match('/^\[[0-9A-Fa-f:]+\](?::[0-9]{1,5})?$/', $httpHost)) {
			$httpHost = '';
		}

		$out = array();

		// Loopback to whatever port PHP sees the server on. Use HTTP even
		// if the public site is HTTPS — TLS to 127.0.0.1 with a public cert
		// won't validate, and most stacks (Apache, nginx) listen on the
		// same TCP port for both schemes only when explicitly configured.
		if ($port > 0 && $port !== 443) {
			$out[] = array('url' => 'http://127.0.0.1:' . $port, 'host' => $httpHost);
			if ($addr !== '' && $addr !== '127.0.0.1' && filter_var($addr, FILTER_VALIDATE_IP) && !filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$out[] = array('url' => 'http://' . $addr . ':' . $port, 'host' => $httpHost);
			}
		}

		// The admin's own request URL.
		if ($httpHost !== '') {
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
			$out[] = array('url' => $scheme . '://' . $httpHost, 'host' => '');
		}

		return $out;
	}

	private function probeUrl($base, $hostHeader)
	{
		$ch = curl_init();
		$opts = array(
			CURLOPT_URL => $base . '/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_NOBODY => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT => 3,
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_USERAGENT => self::CRAWL_USER_AGENT,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		);
		if ($hostHeader !== '') {
			$opts[CURLOPT_HTTPHEADER] = array('Host: ' . $hostHeader);
		}
		curl_setopt_array($ch, $opts);
		curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errno = curl_errno($ch);
		curl_close($ch);
		// Connection refused / timeout: errno != 0 and status 0. Any real
		// HTTP response (even a 4xx) means the server is reachable.
		return ($errno === 0 && $status > 0 && $status < 600);
	}

	private function isLoopbackUrl($url)
	{
		$host = parse_url($url, PHP_URL_HOST);
		if ($host === null) {
			return false;
		}
		if ($host === '127.0.0.1' || $host === '::1' || strtolower($host) === 'localhost') {
			return true;
		}
		if (filter_var($host, FILTER_VALIDATE_IP) && (strpos($host, '10.') === 0 || strpos($host, '192.168.') === 0 || preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host))) {
			return true;
		}
		return false;
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
		$crawlOrigin = $this->originOf($state['crawlBase']);
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

		// Build the rewritten reference.
		$inner = ltrim($url, '/');
		if ($isFile) {
			$ref = $prefix . $inner . $query . $frag;
		} else {
			$queryNoQ = $query === '' ? '' : ltrim($query, '?');
			$rel = $this->htmlRelFilename($url, $queryNoQ);
			$ref = $prefix . $rel . $frag;
		}
		// Collapse a leading "./" only if it's purely cosmetic.
		return $ref === '' ? './' : $ref;
	}

	// ----------------------------------------------------------------------
	// CSS rewriting
	// ----------------------------------------------------------------------
	public function rewriteCss(array &$state, $path, $css)
	{
		$prefix = $this->depthPrefix($path);
		$crawlOrigin = $this->originOf($state['crawlBase']);
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
	 * Map a (path, query) pair to the relative filename used in the static
	 * build. Pages without a query become "path/index.html"; pages with a
	 * query (e.g. "?page=2") become "path/index.<query>.html" so the static
	 * build does not rely on query strings — important for file:// hosting
	 * and for static webservers that ignore the query when serving files.
	 *
	 * Used by both htmlSavePath() (to decide where to write) and rewriteRef()
	 * (to decide what to link to), so the two stay in lockstep.
	 */
	private function htmlRelFilename($path, $query)
	{
		$pathPart = trim($path, '/');
		if ($query === '') {
			return $pathPart === '' ? 'index.html' : $pathPart . '/index.html';
		}
		$qSafe = preg_replace('/[^A-Za-z0-9_\-=&]/', '_', $query);
		return $pathPart === '' ? 'index.' . $qSafe . '.html' : $pathPart . '/index.' . $qSafe . '.html';
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
		// Only map a known set of static prefixes — never anything else.
		$prefixes = array('bl-themes/', 'bl-content/uploads/', 'bl-kernel/', 'bl-plugins/');
		$matched = false;
		foreach ($prefixes as $p) {
			if (strpos($rel, $p) === 0) {
				$matched = true;
				break;
			}
		}
		if (!$matched) {
			return null;
		}
		$candidate = $this->resolveSafePath(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR), $rel);
		return $candidate;
	}

	private function depthPrefix($path)
	{
		// For a path like "/foo/bar" saved as "foo/bar/index.html", the
		// depth (number of directories above the file) is 2 + 1 (index.html
		// is itself inside foo/bar/), so we need "../../" to get back to
		// the root. For files like "/foo/style.css" saved as "foo/style.css",
		// depth is 1, so "../". For "/" saved as "index.html", depth is 0.
		$clean = $this->cleanPath($path);
		if ($clean === null) {
			return '';
		}
		$p = trim($clean['path'], '/');
		if ($p === '') {
			return '';
		}
		$isFile = (bool) preg_match($this->fileExtRegex, $clean['path']);
		$segments = explode('/', $p);
		$depth = $isFile ? count($segments) - 1 : count($segments);
		if ($depth <= 0) {
			return '';
		}
		return str_repeat('../', $depth);
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

	private function writeFile($path, $contents)
	{
		if (!$this->ensureDir(dirname($path))) {
			$this->log('ERROR mkdir ' . dirname($path));
			return 0;
		}
		$tmp = $path . '.part';
		$wrote = @file_put_contents($tmp, $contents);
		if ($wrote === false) {
			$this->log('ERROR write ' . $path);
			return 0;
		}
		if (!@rename($tmp, $path)) {
			@unlink($tmp);
			$this->log('ERROR rename ' . $path);
			return 0;
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

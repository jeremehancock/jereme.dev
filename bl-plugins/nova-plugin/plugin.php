<?php

class pluginNovaPlugin extends Plugin
{
	// ---- Static Site Generator (merged from static-generator-jereme) ----
	const SGJ_OUTPUT_DIRNAME = 'static-build';
	const SGJ_LOCK_FILENAME = 'build.lock';
	const SGJ_LOG_FILENAME = 'build.log';
	const SGJ_MAX_LOG_LINES = 100;
	// Hard cap on URLs per build — purely a runaway-safety net, not a
	// user-facing setting.
	const SGJ_URL_HARD_CAP = 10000;

	private $sgjFileExtRegex = '~\.(css|js|mjs|json|xml|txt|map|png|jpe?g|gif|webp|avif|svg|ico|bmp|tiff?|woff2?|ttf|otf|eot|mp4|webm|ogg|mp3|wav|flac|m4a|pdf|zip|gz|tar|br)([?#].*)?$~i';

	public function init()
	{
		$this->dbFields = array(
			// Categories widget
			'categoriesEnabled' => true,
			'categoriesLabel' => 'Categories',
			'categoriesHideEmpty' => true,

			// Latest Posts widget
			'latestEnabled' => true,
			'latestLabel' => 'Latest Posts',
			'latestNumberOfItems' => 3,

			// About widget (static pages)
			'staticEnabled' => true,
			'staticLabel' => 'About',

			// External link behavior
			'targetBlankEnabled' => true,

			// Web stats
			'webStatsDevport' => '',
			'webStatsCode' => '',

			// Admin version check
			'versionCheckEnabled' => true,

			// Custom HTML injection — website
			'htmlHead' => '',
			'htmlBodyBegin' => '',
			'htmlBodyEnd' => '',

			// Custom HTML injection — admin
			'htmlAdminHead' => '',
			'htmlAdminBodyBegin' => '',
			'htmlAdminBodyEnd' => '',

			// Open Graph meta tags — Defaults are relative to avoid hardcoding localhost/domain in DB.
			'ogEnabled' => false,
			'ogDefaultImage' => 'bl-themes/nova/img/jereme-meta.png',
			'ogFbAppId' => '',

			// Twitter / X Card meta tags
			'twitterCardsEnabled' => false,
			'twitterCardType' => 'summary_large_image',
			'twitterSite' => '',
			'twitterDefaultImage' => 'bl-themes/nova/img/jereme-meta.png',

			// EasyMDE markdown editor — off by default; opt-in.
			'easymdeEnabled' => false,
			'easymdeTabSize' => '2',
			'easymdeToolbar' => '"bold", "italic", "heading", "|", "quote", "unordered-list", "|", "link", "image", "code", "horizontal-rule", "|", "preview", "side-by-side", "fullscreen"',
			'easymdeSpellChecker' => true,

			// Static Site Generator (merged from static-generator-jereme)
			'excludePaths' => '',
			'extraDirs' => '',
			'productionUrl' => '',
			// Written by runStaticBuild() — not user-editable.
			'lastBuildTime' => '',
			'lastBuildResult' => '',
			'lastBuildMessage' => '',
			'lastBuildUrls' => 0,
			'lastBuildBytes' => 0,
			'lastBuildDuration' => 0,
		);
	}

	// EasyMDE only loads on these two admin views (create/edit pages).
	private $easymdeViews = array('new-content', 'edit-content');

	// Helper to convert relative DB paths to absolute URLs at runtime.
	private function makeAbsolute($path)
	{
		if (empty($path) || filter_var($path, FILTER_VALIDATE_URL)) {
			return $path;
		}
		global $site;
		return rtrim($site->url(), '/') . '/' . ltrim($path, '/');
	}

	// ------------------------------------------------------------------
	// Admin settings form
	// ------------------------------------------------------------------
	public function form()
	{
		global $L;

		// Tab definitions: id => [labelKey, icon, subtitleKey|null]
		$tabs = array(
			'sidebar'     => array('jdpc-section-sidebar',     'list-alt',          'jdpc-section-sidebar-subtitle'),
			'external'    => array('jdpc-section-external',    'external-link-alt', 'jdpc-section-external-subtitle'),
			'social'      => array('jdpc-section-social',      'share-alt',         'jdpc-section-social-subtitle'),
			'stats'       => array('jdpc-section-stats',       'chart-bar',         'jdpc-section-stats-subtitle'),
			'html'        => array('jdpc-section-html',        'code',              'jdpc-section-html-subtitle'),
			'easymde'     => array('jdpc-section-easymde',     'edit',              'jdpc-section-easymde-subtitle'),
			'version'     => array('jdpc-section-version',     'tag',               'jdpc-section-version-subtitle'),
			'staticgen'   => array('jdpc-section-staticgen',   'hammer',            'jdpc-section-staticgen-subtitle'),
			'howitworks'  => array('jdpc-section-howitworks',  'info-circle',       null),
		);

		$html  = '<div class="alert alert-primary mb-4" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		// Tab navigation
		$html .= '<nav class="mb-3">';
		$html .= '<div class="nav nav-tabs" id="jdpc-nav-tab" role="tablist">';
		$first = true;
		foreach ($tabs as $id => $meta) {
			$activeCls = $first ? ' active' : '';
			$selected  = $first ? 'true' : 'false';
			$html .= '<a class="nav-item nav-link' . $activeCls . '"'
				. ' id="jdpc-nav-' . $id . '-tab"'
				. ' data-toggle="tab"'
				. ' href="#jdpc-' . $id . '"'
				. ' role="tab"'
				. ' aria-controls="jdpc-' . $id . '"'
				. ' aria-selected="' . $selected . '">'
				. '<span class="fa fa-' . $meta[1] . ' mr-2"></span>'
				. $L->get($meta[0])
				. '</a>';
			$first = false;
		}
		$html .= '</div>';
		$html .= '</nav>';

		// Tab content
		$html .= '<div class="tab-content" id="jdpc-nav-tab-content">';

		// --- Sidebar widgets tab ---
		$html .= $this->openTabPane('sidebar', $tabs['sidebar'][2], true);

		$html .= $this->subHeading('jdpc-section-categories');
		$html .= $this->selectField('categoriesEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('categoriesLabel', $L->get('Label'));
		$html .= $this->selectField('categoriesHideEmpty', $L->get('jdpc-hide-empty-categories'));

		$html .= $this->subHeading('jdpc-section-latest');
		$html .= $this->selectField('latestEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('latestLabel', $L->get('Label'));
		if (defined('ORDER_BY') && ORDER_BY === 'date') {
			$html .= $this->numberField('latestNumberOfItems', $L->get('jdpc-amount-of-items'), 1);
		}

		$html .= $this->subHeading('jdpc-section-static');
		$html .= $this->selectField('staticEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('staticLabel', $L->get('Label'));

		$html .= $this->closeTabPane();

		// --- External links tab ---
		$html .= $this->openTabPane('external', $tabs['external'][2]);
		$html .= $this->selectField('targetBlankEnabled', $L->get('jdpc-enable-external-target-blank'));
		$html .= $this->closeTabPane();

		// --- Social previews tab ---
		$html .= $this->openTabPane('social', $tabs['social'][2]);

		$html .= $this->subHeading('jdpc-subsection-og');
		$html .= $this->selectField('ogEnabled', $L->get('jdpc-enable-meta'));
		$html .= $this->textField('ogDefaultImage', $L->get('jdpc-default-image-label'), $L->get('jdpc-og-default-image-tip'));
		$html .= $this->textField('ogFbAppId', $L->get('jdpc-og-fb-appid-label'), $L->get('jdpc-og-fb-appid-tip'));

		$html .= $this->subHeading('jdpc-subsection-twitter');
		$html .= $this->selectField('twitterCardsEnabled', $L->get('jdpc-enable-meta'));

		// Card type — non-binary select, inline since selectField is Enabled/Disabled only.
		$cardType = $this->getValue('twitterCardType');
		$html .= '<div class="form-group">';
		$html .= '<label for="jdpc_twitterCardType"><strong>' . $L->get('jdpc-twitter-card-type-label') . '</strong></label>';
		$html .= '<select id="jdpc_twitterCardType" class="form-control" name="twitterCardType">';
		$html .= '<option value="summary_large_image"' . ($cardType === 'summary_large_image' ? ' selected' : '') . '>' . $L->get('jdpc-twitter-card-type-large') . '</option>';
		$html .= '<option value="summary"' . ($cardType === 'summary' ? ' selected' : '') . '>' . $L->get('jdpc-twitter-card-type-summary') . '</option>';
		$html .= '</select>';
		$html .= '<small class="form-text text-muted">' . $L->get('jdpc-twitter-card-type-tip') . '</small>';
		$html .= '</div>';

		$html .= $this->textField('twitterSite', $L->get('jdpc-twitter-site-label'), $L->get('jdpc-twitter-site-tip'));
		$html .= $this->textField('twitterDefaultImage', $L->get('jdpc-default-image-label'), $L->get('jdpc-twitter-default-image-tip'));

		$html .= $this->closeTabPane();

		// --- Web stats tab ---
		$html .= $this->openTabPane('stats', $tabs['stats'][2]);
		$html .= $this->numberField('webStatsDevport', $L->get('jdpc-dev-port'), null, $L->get('jdpc-dev-port-tip'));
		$html .= '<div class="form-group">';
		$html .= '<label for="jdpcStatsCode"><strong>' . $L->get('jdpc-stats-code') . '</strong></label>';
		$html .= '<textarea id="jdpcStatsCode" class="form-control" name="webStatsCode" rows="8" style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.85rem;">' . $this->getValue('webStatsCode') . '</textarea>';
		$html .= '<small class="form-text text-muted">' . $L->get('jdpc-stats-code-tip') . '</small>';
		$html .= '</div>';
		$html .= $this->closeTabPane();

		// --- Custom HTML tab ---
		$html .= $this->openTabPane('html', $tabs['html'][2]);

		$html .= $this->subHeading('jdpc-section-html-website');
		$html .= $this->textareaField('htmlHead', $L->get('jdpc-html-head-label'), $L->get('jdpc-html-head-tip'));
		$html .= $this->textareaField('htmlBodyBegin', $L->get('jdpc-html-bodybegin-label'), $L->get('jdpc-html-bodybegin-tip'));
		$html .= $this->textareaField('htmlBodyEnd', $L->get('jdpc-html-bodyend-label'), $L->get('jdpc-html-bodyend-tip'));

		$html .= $this->subHeading('jdpc-section-html-admin');
		$html .= $this->textareaField('htmlAdminHead', $L->get('jdpc-html-head-label'), $L->get('jdpc-html-adminhead-tip'));
		$html .= $this->textareaField('htmlAdminBodyBegin', $L->get('jdpc-html-bodybegin-label'), $L->get('jdpc-html-adminbodybegin-tip'));
		$html .= $this->textareaField('htmlAdminBodyEnd', $L->get('jdpc-html-bodyend-label'), $L->get('jdpc-html-adminbodyend-tip'));

		$html .= $this->closeTabPane();

		// --- Markdown editor (EasyMDE) tab ---
		$html .= $this->openTabPane('easymde', $tabs['easymde'][2]);
		$html .= $this->selectField('easymdeEnabled', $L->get('jdpc-enable-easymde'));
		$html .= $this->textField('easymdeTabSize', $L->get('jdpc-easymde-tabsize-label'), $L->get('jdpc-easymde-tabsize-tip'));
		$html .= $this->textField('easymdeToolbar', $L->get('jdpc-easymde-toolbar-label'), $L->get('jdpc-easymde-toolbar-tip'));
		$html .= $this->selectField('easymdeSpellChecker', $L->get('jdpc-easymde-spellchecker-label'));
		$html .= $this->closeTabPane();

		// --- Admin version check tab ---
		$html .= $this->openTabPane('version', $tabs['version'][2]);
		$html .= $this->selectField('versionCheckEnabled', $L->get('jdpc-enable-version-check'));
		$html .= $this->closeTabPane();

		// --- Static Site Generator tab ---
		$html .= $this->openTabPane('staticgen', $tabs['staticgen'][2]);
		$html .= $this->renderStaticGeneratorTab();
		$html .= $this->closeTabPane();

		// --- How it works tab ---
		$html .= $this->openTabPane('howitworks', null);
		$html .= '<ul class="mb-0" style="padding-left: 1.2rem; line-height: 1.7;">';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-external') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-404') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-archived') . '</li>';
		$html .= '<li>' . $L->get('jdpc-howitworks-targetblank') . '</li>';
		$html .= '</ul>';
		$html .= $this->closeTabPane();

		$html .= '</div>'; // /tab-content

		// Remember the active tab across saves / refreshes
		$html .= '<script>(function(){'
			. 'var key="jdpcActiveTab";'
			. 'var $tabs=$(\'#jdpc-nav-tab a[data-toggle="tab"]\');'
			. '$tabs.on("click",function(e){window.localStorage.setItem(key,$(e.target).attr("href"));});'
			. 'var active=window.localStorage.getItem(key);'
			. 'if(active&&$(\'#jdpc-nav-tab a[href="\'+active+\'"]\').length){'
			. '$(\'#jdpc-nav-tab a[href="\'+active+\'"]\').tab("show");'
			. '}'
			. '})();</script>';

		return $html;
	}

	// ------------------------------------------------------------------
	// Form rendering helpers
	// ------------------------------------------------------------------
	private function openTabPane($id, $subtitleKey = null, $active = false)
	{
		global $L;
		$cls = 'tab-pane fade' . ($active ? ' show active' : '');
		$html = '<div class="' . $cls . '" id="jdpc-' . $id . '" role="tabpanel" aria-labelledby="jdpc-nav-' . $id . '-tab">';
		if ($subtitleKey) {
			$html .= '<p class="text-muted mb-4">' . $L->get($subtitleKey) . '</p>';
		}
		return $html;
	}

	private function closeTabPane()
	{
		return '</div>';
	}

	private function subHeading($titleKey)
	{
		global $L;
		return '<h6 class="text-uppercase text-muted mt-3 mb-2" style="letter-spacing: 0.05em; font-size: 0.75rem;">'
			. $L->get($titleKey)
			. '</h6><hr class="mt-1 mb-3">';
	}

	private function textField($name, $labelText, $tip = null)
	{
		$html = '<div class="form-group">';
		$html .= '<label for="jdpc_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<input id="jdpc_' . $name . '" class="form-control" name="' . $name . '" type="text" dir="auto" value="' . $this->getValue($name) . '">';
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
		$html .= '<label for="jdpc_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<input id="jdpc_' . $name . '" class="form-control" name="' . $name . '" type="number"' . $attrs . ' value="' . $this->getValue($name) . '">';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}

	private function readonlyUrlField($labelKey, $url)
	{
		global $L;
		$safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		$html = '<div class="form-group">';
		$html .= '<label><strong>' . $L->get($labelKey) . '</strong></label>';
		$html .= '<div><a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">' . $safeUrl . '</a></div>';
		$html .= '</div>';
		return $html;
	}

	private function textareaField($name, $labelText, $tip = null, $rows = 4)
	{
		$html = '<div class="form-group">';
		$html .= '<label for="jdpc_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<textarea id="jdpc_' . $name . '" class="form-control" name="' . $name . '" rows="' . (int) $rows . '" style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.85rem;">' . $this->getValue($name) . '</textarea>';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}

	private function selectField($name, $labelText, $tip = null)
	{
		global $L;
		$val = $this->getValue($name);
		$html = '<div class="form-group">';
		$html .= '<label for="jdpc_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<select id="jdpc_' . $name . '" class="form-control" name="' . $name . '">';
		$html .= '<option value="true" ' . ($val === true ? 'selected' : '') . '>' . $L->get('Enabled') . '</option>';
		$html .= '<option value="false" ' . ($val === false ? 'selected' : '') . '>' . $L->get('Disabled') . '</option>';
		$html .= '</select>';
		if ($tip) {
			$html .= '<small class="form-text text-muted">' . $tip . '</small>';
		}
		$html .= '</div>';
		return $html;
	}

	// ------------------------------------------------------------------
	// Frontend: sidebar widgets (Categories -> Latest Posts -> About)
	// ------------------------------------------------------------------
	public function siteSidebar()
	{
		$out = '';
		if ($this->getValue('categoriesEnabled')) {
			$out .= $this->renderCategoriesWidget();
		}
		if ($this->getValue('latestEnabled')) {
			$out .= $this->renderLatestPostsWidget();
		}
		if ($this->getValue('staticEnabled')) {
			$out .= $this->renderStaticPagesWidget();
		}
		return $out;
	}

	private function renderCategoriesWidget()
	{
		global $categories;

		$label = $this->getValue('categoriesLabel');
		$hideZero = $this->getValue('categoriesHideEmpty');

		$html = '<div class="plugin plugin-categories">';
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">' . $label . '</h2>';
		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		// Separate "Archived" so it can be pinned to the bottom regardless of alphabetical order
		$regular = array();
		$archived = array();
		foreach ($categories->db as $key => $fields) {
			if (strcasecmp($fields['name'], 'Archived') === 0) {
				$archived[$key] = $fields;
			} else {
				$regular[$key] = $fields;
			}
		}
		$sorted = $regular + $archived;

		foreach ($sorted as $key => $fields) {
			$count = count($fields['list']);
			if (!$hideZero || $count > 0) {
				$html .= '<li>';
				$html .= '<a href="' . DOMAIN_CATEGORIES . $key . '">';
				$html .= $fields['name'] . ' (' . $count . ')';
				$html .= '</a>';
				$html .= '</li>';
			}
		}

		$html .= '</ul></div></div>';
		return $html;
	}

	public function renderLatestPostsWidget()
	{
		global $pages;

		$label = $this->getValue('latestLabel');

		$html = '<div class="plugin plugin-navigation">';
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">' . $label . '</h2>';
		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		if (defined('ORDER_BY') && ORDER_BY === 'position') {
			// Parent/child rendering for sites that order pages by manual position
			$parents = buildParentPages();
			foreach ($parents as $parent) {
				$html .= '<li class="parent">';
				$html .= '<a href="' . $parent->permalink() . '">' . $parent->title() . '</a>';
				if ($parent->hasChildren()) {
					$html .= '<ul class="child">';
					foreach ($parent->children() as $child) {
						$html .= '<li class="child"><a class="child" href="' . $child->permalink() . '">' . $child->title() . '</a></li>';
					}
					$html .= '</ul>';
				}
				$html .= '</li>';
			}
		} else {
			$count = max(1, (int) $this->getValue('latestNumberOfItems'));
			$onlyPublished = true;
			$publishedPages = $pages->getList(1, $count, $onlyPublished);
			foreach ($publishedPages as $pageKey) {
				try {
					$p = new Page($pageKey);
					$html .= '<li><a href="' . $p->permalink() . '">' . $p->title() . '</a></li>';
				} catch (Exception $e) {
					// Skip pages that fail to construct
				}
			}
		}

		$html .= '</ul></div></div>';
		return $html;
	}

	public function renderStaticPagesWidget()
	{
		$label = $this->getValue('staticLabel');

		$html = '<div class="plugin plugin-static-pages">';
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">' . $label . '</h2>';
		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		// Static pages whose description is set to special markers:
		//   "404"               -> hidden from this widget (theme also hides them)
		//   "external:<url>"    -> link points to <url>, opens in a new tab (theme matches)
		$staticPages = buildStaticPages();
		foreach ($staticPages as $p) {
			$desc = trim($p->description());
			if ($desc === '404') {
				continue;
			}
			$externalUrl = '';
			if (stripos($desc, 'external:') === 0) {
				$externalUrl = trim(substr($desc, 9));
			}
			$liClass = $p->isParent() ? 'parent' : 'subpage';
			$liStyle = $p->isParent() ? '' : ' style="margin-left: 10px"';
			$html .= '<li class="' . $liClass . '"' . $liStyle . '>';
			if ($externalUrl !== '') {
				$html .= '<a href="' . htmlspecialchars($externalUrl, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: baseline; gap: 4px;">';
				$html .= $p->title();
				$html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            <polyline points="15 3 21 3 21 9"></polyline>
            <line x1="10" y1="14" x2="21" y2="3"></line>
          </svg>';
				$html .= '</a>';
			} else {
				$html .= '<a href="' . $p->permalink() . '">' . $p->title() . '</a>';
			}
			$html .= '</li>';
		}

		$html .= '</ul></div></div>';
		return $html;
	}

	// ------------------------------------------------------------------
	// Frontend: head / body begin / body end
	//
	// siteBodyEnd combines three things:
	//   - target=_blank script for external anchors (with rel=noopener noreferrer)
	//   - web stats code (skipped when SERVER_PORT matches the configured dev port)
	//   - custom HTML from the "Body end" injection field
	// ------------------------------------------------------------------
	public function siteHead()
	{
		$out = '';
		if ($this->getValue('ogEnabled')) {
			$out .= $this->renderOpenGraphTags();
		}
		if ($this->getValue('twitterCardsEnabled')) {
			$out .= $this->renderTwitterCardTags();
		}
		$out .= $this->decodedValue('htmlHead');
		return $out;
	}

	// ------------------------------------------------------------------
	// Webhooks: INTERCEPTION REMOVED.
	// We no longer exit() here because Nginx serves the physical files.
	// ------------------------------------------------------------------
	public function beforeAll()
	{
		// Interception removed to allow Nginx to serve physical files from root.
	}

	public function siteBodyBegin()
	{
		return $this->decodedValue('htmlBodyBegin');
	}

	public function siteBodyEnd()
	{
		$out = '';

		if ($this->getValue('targetBlankEnabled')) {
			$out .= '<script>'
				. '(function(){var ls=document.querySelectorAll("a[href]");'
				. 'for(var i=0,n=ls.length;i<n;i++){var a=ls[i];'
				. 'if(!a.hostname||a.hostname===window.location.hostname)continue;'
				. 'a.target="_blank";'
				. 'var r=(a.getAttribute("rel")||"").split(/\s+/).filter(Boolean);'
				. 'if(r.indexOf("noopener")===-1)r.push("noopener");'
				. 'if(r.indexOf("noreferrer")===-1)r.push("noreferrer");'
				. 'a.setAttribute("rel",r.join(" "));}})();'
				. '</script>' . PHP_EOL;
		}

		$devport = $this->getValue('webStatsDevport');
		$code = $this->getValue('webStatsCode');
		if (!empty($code)) {
			$serverPort = isset($_SERVER['SERVER_PORT']) ? (string) $_SERVER['SERVER_PORT'] : '';
			$onDevPort = ($devport !== '' && $serverPort === (string) $devport);
			if (!$onDevPort) {
				$out .= html_entity_decode($code);
			}
		}

		$out .= $this->decodedValue('htmlBodyEnd');

		return $out;
	}

	// ------------------------------------------------------------------
	// Admin: version chip in sidebar + AJAX check for newer Bludit releases
	// ------------------------------------------------------------------
	public function adminSidebar()
	{
		if (!$this->getValue('versionCheckEnabled')) {
			return '';
		}
		global $L;
		$isPro = defined('BLUDIT_PRO');
		$heart = $isPro ? '<span class="fa fa-heart" style="color: #ffc107"></span>' : '';
		$newHref = $isPro ? 'https://www.patreon.com/bludit/posts' : 'https://www.bludit.com';

		$html = '<a id="current-version" class="nav-link" href="' . HTML_PATH_ADMIN_ROOT . 'about">';
		$html .= 'Version ' . $heart;
		$html .= '<span class="badge badge-warning badge-pill">' . BLUDIT_VERSION . '</span>';
		$html .= '</a>';
		$html .= '<a id="new-version" style="display: none;" target="_blank" rel="noopener" href="' . $newHref . '">';
		$html .= $L->get('New version available') . ' <span class="fa fa-bell" style="color: red"></span>';
		$html .= '</a>';
		return $html;
	}

	public function adminHead()
	{
		$out = '';
		if ($this->getValue('easymdeEnabled') && $this->onEasymdeView()) {
			$out .= $this->includeCSS('easymde.min.css');
			$out .= $this->includeCSS('easymde-bludit.css');
		}
		$out .= $this->decodedValue('htmlAdminHead');
		return $out;
	}

	private function onEasymdeView()
	{
		return isset($GLOBALS['ADMIN_VIEW']) && in_array($GLOBALS['ADMIN_VIEW'], $this->easymdeViews, true);
	}

	public function adminBodyBegin()
	{
		return $this->decodedValue('htmlAdminBodyBegin');
	}

	public function adminBodyEnd()
	{
		$out = '';

		if ($this->getValue('versionCheckEnabled')) {
			$jsPath = $this->phpPath() . 'js' . DS . 'version.js';
			if (file_exists($jsPath)) {
				$out .= '<script>' . file_get_contents($jsPath) . '</script>';
			}
		}

		if ($this->getValue('easymdeEnabled') && $this->onEasymdeView()) {
			$out .= $this->renderEasymdeInit();
		}

		$out .= $this->decodedValue('htmlAdminBodyEnd');

		return $out;
	}

	private function renderEasymdeInit()
	{
		global $L;

		$spellChecker = $this->getValue('easymdeSpellChecker') ? 'true' : 'false';
		$tabSize = (int) $this->getValue('easymdeTabSize');
		if ($tabSize < 1) {
			$tabSize = 2;
		}
		$toolbar = Sanitize::htmlDecode($this->getValue('easymdeToolbar'));
		$pageBreak = defined('PAGE_BREAK') ? PAGE_BREAK : '';
		$jsEasyMDE = $this->domainPath() . 'js/easymde.min.js?version=' . BLUDIT_VERSION;
		$langImage = $L->g('Image description');

		return <<<EOF
<script charset="utf-8" src="$jsEasyMDE"></script>
<script>
	var easymde = null;

	function editorInsertMedia(filename) {
		var text = easymde.value();
		easymde.value(text + "![$langImage]("+filename+")" + "\\n");
		easymde.codemirror.refresh();
	}

	function editorGetContent() {
		return easymde.value();
	}

	easymde = new EasyMDE({
		element: document.getElementById("jseditor"),
		status: false,
		toolbarTips: true,
		toolbarGuideIcon: true,
		autofocus: false,
		placeholder: "",
		lineWrapping: true,
		autoDownloadFontAwesome: false,
		indentWithTabs: true,
		tabSize: $tabSize,
		spellChecker: $spellChecker,
		toolbar: [$toolbar,
			"|",
			{
				name: "pageBreak",
				action: function addPageBreak(editor){
					var cm = editor.codemirror;
					output = "$pageBreak";
					cm.replaceSelection(output);
				},
				className: "fa fa-crop",
				title: "Page break",
			}]
	});
</script>
EOF;
	}

	private function decodedValue($key)
	{
		$val = $this->getValue($key);
		return ($val === '' || $val === null) ? '' : html_entity_decode($val);
	}

	// ------------------------------------------------------------------
	// Social previews — Open Graph + Twitter/X Card meta tags
	// ------------------------------------------------------------------

	private function metaSanitize($text, $maxLength = 0)
	{
		$text = strip_tags((string) $text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = trim($text);
		if ($maxLength > 0 && mb_strlen($text, 'UTF-8') > $maxLength) {
			$text = mb_substr($text, 0, $maxLength, 'UTF-8') . '...';
		}
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

	private function attrEscape($s)
	{
		return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
	}

	private function renderOpenGraphTags()
	{
		global $site;
		global $WHERE_AM_I;
		global $page;
		global $content;

		$og = array(
			'locale' => $site->locale(),
			'type' => 'website',
			'title' => $this->metaSanitize($site->title()),
			'description' => $this->metaSanitize($site->description(), 200),
			'url' => $site->url(),
			'image' => '',
			'siteName' => $this->metaSanitize($site->title()),
			'publishedTime' => '',
			'modifiedTime' => '',
			'author' => '',
		);

		$pageContent = '';
		if ($WHERE_AM_I === 'page' && isset($page)) {
			$og['type'] = 'article';
			$og['title'] = $this->metaSanitize($page->title());
			$description = $page->description();
			if (empty($description)) {
				$description = Text::truncate(strip_tags($page->contentRaw()), 160);
			}
			$og['description'] = $this->metaSanitize($description, 200);
			$og['url'] = $page->permalink(true);
			$og['image'] = $page->coverImage(true);
			$og['publishedTime'] = $page->date('c');
			$mod = $page->dateModified('c');
			if (!empty($mod)) {
				$og['modifiedTime'] = $mod;
			}
			$og['author'] = $this->metaSanitize($page->user('nickname'));
			$pageContent = $page->content();
		} else {
			$default = $this->makeAbsolute($this->getValue('ogDefaultImage'));
			if (!empty($default)) {
				$og['image'] = $default;
			} elseif (isset($content[0])) {
				$og['image'] = $content[0]->coverImage(true);
				$pageContent = $content[0]->content();
			}
		}

		$out = PHP_EOL . '' . PHP_EOL;
		$out .= '<meta property="og:locale" content="' . $this->attrEscape($og['locale']) . '">' . PHP_EOL;
		$out .= '<meta property="og:type" content="' . $this->attrEscape($og['type']) . '">' . PHP_EOL;
		$out .= '<meta property="og:title" content="' . $og['title'] . '">' . PHP_EOL;
		$out .= '<meta property="og:description" content="' . $og['description'] . '">' . PHP_EOL;
		$out .= '<meta property="og:url" content="' . $this->attrEscape($og['url']) . '">' . PHP_EOL;
		$out .= '<meta property="og:site_name" content="' . $og['siteName'] . '">' . PHP_EOL;

		if ($og['type'] === 'article') {
			if (!empty($og['publishedTime'])) {
				$out .= '<meta property="article:published_time" content="' . $this->attrEscape($og['publishedTime']) . '">' . PHP_EOL;
			}
			if (!empty($og['modifiedTime'])) {
				$out .= '<meta property="article:modified_time" content="' . $this->attrEscape($og['modifiedTime']) . '">' . PHP_EOL;
			}
			if (!empty($og['author'])) {
				$out .= '<meta property="article:author" content="' . $og['author'] . '">' . PHP_EOL;
			}
		}

		if (empty($og['image'])) {
			$src = class_exists('DOM') ? DOM::getFirstImage($pageContent) : false;
			if ($src !== false) {
				$og['image'] = $src;
			} else {
				$default = $this->makeAbsolute($this->getValue('ogDefaultImage'));
				if (!empty($default)) {
					$og['image'] = $default;
				}
			}
		}
		if (!empty($og['image'])) {
			$out .= '<meta property="og:image" content="' . $this->attrEscape($og['image']) . '">' . PHP_EOL;
			$out .= '<meta property="og:image:alt" content="' . $og['title'] . '">' . PHP_EOL;
		}

		$fbAppId = $this->getValue('ogFbAppId');
		if (!empty($fbAppId)) {
			$out .= '<meta property="fb:app_id" content="' . $this->attrEscape($fbAppId) . '">' . PHP_EOL;
		}

		return $out;
	}

	private function renderTwitterCardTags()
	{
		global $site;
		global $WHERE_AM_I;
		global $page;
		global $content;

		$cardType = $this->getValue('twitterCardType');
		if (empty($cardType)) {
			$cardType = 'summary_large_image';
		}

		$data = array(
			'card' => $cardType,
			'site' => $this->getValue('twitterSite'),
			'title' => $this->metaSanitize($site->title(), 70),
			'description' => $this->metaSanitize($site->description(), 200),
			'image' => '',
			'imageAlt' => '',
		);

		$pageContent = '';
		if ($WHERE_AM_I === 'page' && isset($page)) {
			$data['title'] = $this->metaSanitize($page->title(), 70);
			$description = $page->description();
			if (empty($description)) {
				$description = Text::truncate(strip_tags($page->contentRaw()), 160);
			}
			$data['description'] = $this->metaSanitize($description, 200);
			$data['image'] = $page->coverImage(true);
			$data['imageAlt'] = $data['title'];
			$pageContent = $page->content();
		} else {
			$default = $this->makeAbsolute($this->getValue('twitterDefaultImage'));
			if (!empty($default)) {
				$data['image'] = $default;
			} elseif (isset($content[0])) {
				$data['image'] = $content[0]->coverImage(true);
				$data['imageAlt'] = $this->metaSanitize($content[0]->title(), 70);
				$pageContent = $content[0]->content();
			}
		}

		$out = PHP_EOL . '' . PHP_EOL;
		$out .= '<meta name="twitter:card" content="' . $this->attrEscape($data['card']) . '">' . PHP_EOL;

		if (!empty($data['site'])) {
			$out .= '<meta name="twitter:site" content="' . $this->metaSanitize($data['site']) . '">' . PHP_EOL;
		}
		$out .= '<meta name="twitter:title" content="' . $data['title'] . '">' . PHP_EOL;
		$out .= '<meta name="twitter:description" content="' . $data['description'] . '">' . PHP_EOL;

		if (empty($data['image'])) {
			$src = class_exists('DOM') ? DOM::getFirstImage($pageContent) : false;
			if ($src !== false) {
				$data['image'] = $src;
			} else {
				$default = $this->makeAbsolute($this->getValue('twitterDefaultImage'));
				if (!empty($default)) {
					$data['image'] = $default;
				}
			}
		}
		if (!empty($data['image'])) {
			$out .= '<meta name="twitter:image" content="' . $this->attrEscape($data['image']) . '">' . PHP_EOL;
			if (!empty($data['imageAlt'])) {
				$out .= '<meta name="twitter:image:alt" content="' . $data['imageAlt'] . '">' . PHP_EOL;
			}
		}

		return $out;
	}

	// ==================================================================
	// Static Site Generator (merged from static-generator-jereme)
	//
	// Crawls the running Bludit instance and writes a fully static HTML
	// mirror to bl-content/static-build/. Pages are saved as
	// path/index.html with all absolute and root-relative URLs rewritten
	// to document-relative form so the output works on any host (and
	// over file://).
	//
	// Security notes:
	//   - The form posts through the standard configure-plugin endpoint,
	//     so the kernel's checkRole(['admin']) + tokenCSRF rules already
	//     apply before post() runs.
	//   - The output directory is a hard-coded constant under PATH_CONTENT.
	//     Filesystem paths derived from URLs are validated to stay under it.
	//   - Admin URLs and bl-content/databases are always skipped, regardless
	//     of the exclude-paths setting.
	// ==================================================================

	private function renderStaticGeneratorTab()
	{
		global $L;

		// The wrapper class scopes the theme-aware button/details CSS in
		// renderSgjConfirmAndLoading() so we don't accidentally restyle
		// the "Save" / "Cancel" buttons the configure-plugin view emits
		// outside the plugin's own form contents.
		$html = '<div class="sgj-cfg">';

		// SECTION: Generate
		$html .= $this->openSgjCard('sgj-section-action', 'hammer', 'sgj-section-action-subtitle');
		$buildLabel = htmlspecialchars($L->get('sgj-build-button'), ENT_QUOTES, 'UTF-8');
		$html .= '<button type="submit" id="sgj-build-btn" class="btn btn-primary"'
			. ' name="action" value="build">'
			. $buildLabel
			. '</button>';
		$html .= $this->closeSgjCard();

		// SECTION: Settings
		$html .= $this->openSgjCard('sgj-section-settings', 'cog');
		$html .= $this->textField('productionUrl', $L->get('sgj-production-url-label'), $L->get('sgj-production-url-tip'));
		$html .= $this->textareaField('excludePaths', $L->get('sgj-exclude-paths-label'), $L->get('sgj-exclude-paths-tip'), 4);
		$html .= $this->textareaField('extraDirs', $L->get('sgj-extra-dirs-label'), $L->get('sgj-extra-dirs-tip'), 4);
		$html .= $this->closeSgjCard();

		// SECTION: Status
		$html .= $this->openSgjCard('sgj-section-status', 'history');
		$html .= $this->renderSgjStatus();
		$html .= $this->closeSgjCard();

		// Confirmation modal + full-page loading overlay + JS that wires
		// the "Generate static site" button to them.
		$html .= $this->renderSgjConfirmAndLoading();

		$html .= '</div>'; // .sgj-cfg
		return $html;
	}

	private function openSgjCard($titleKey, $icon = null, $subtitleKey = null)
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

	private function closeSgjCard()
	{
		return '</div></div>';
	}

	private function renderSgjConfirmAndLoading()
	{
		global $L;
		$confirmTitle = htmlspecialchars($L->get('sgj-confirm-title'), ENT_QUOTES, 'UTF-8');
		$confirmBody = htmlspecialchars($L->get('sgj-confirm-body'), ENT_QUOTES, 'UTF-8');
		$confirmYes = htmlspecialchars($L->get('sgj-confirm-yes'), ENT_QUOTES, 'UTF-8');
		$confirmCancel = htmlspecialchars($L->get('sgj-confirm-cancel'), ENT_QUOTES, 'UTF-8');
		$loadingTitle = htmlspecialchars($L->get('sgj-loading-title'), ENT_QUOTES, 'UTF-8');
		$loadingBody = htmlspecialchars($L->get('sgj-loading-body'), ENT_QUOTES, 'UTF-8');
		$buildingJson = json_encode($L->get('sgj-building-button'), JSON_UNESCAPED_UNICODE);

		return <<<HTML

<style>
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

	private function renderSgjStatus()
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

		$log = $this->readSgjLogTail();
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
			$this->runStaticBuild();
		}
		return true;
	}

	// ----------------------------------------------------------------------
	// Build orchestration
	// ----------------------------------------------------------------------
	private function runStaticBuild()
	{
		global $L;

		@set_time_limit(0);
		$start = microtime(true);

		// Make sure the workspace exists for lock + log files.
		$workspace = $this->workspace();
		if (!is_dir($workspace)) {
			@mkdir($workspace, DIR_PERMISSIONS, true);
		}

		$lockPath = $workspace . self::SGJ_LOCK_FILENAME;
		$lockFp = @fopen($lockPath, 'c');
		if (!$lockFp || !@flock($lockFp, LOCK_EX | LOCK_NB)) {
			$this->recordSgjResult('fail', $L->get('sgj-build-locked'), 0, 0, microtime(true) - $start);
			if ($lockFp) {
				@fclose($lockFp);
			}
			Alert::set($L->get('sgj-build-locked'), ALERT_STATUS_FAIL);
			return;
		}
		@ftruncate($lockFp, 0);
		@fwrite($lockFp, (string) getmypid());

		// Fresh log.
		$this->sgjLogReset();
		$this->sgjLog('Build started at ' . date('c'));

		$outDir = $this->outputDir();
		if (!$this->sgjEnsureDir($outDir)) {
			$this->sgjLog('FATAL: could not create output directory: ' . $outDir);
			$this->recordSgjResult('fail', $L->get('sgj-build-no-output-dir'), 0, 0, microtime(true) - $start);
			@flock($lockFp, LOCK_UN);
			@fclose($lockFp);
			Alert::set($L->get('sgj-build-no-output-dir'), ALERT_STATUS_FAIL);
			return;
		}

		// Clear previous build so deleted pages don't linger.
		$this->sgjLog('Clearing output directory: ' . $outDir);
		$this->sgjClearDir($outDir);

		$state = array(
			'sitePathPrefix' => $this->sgjSitePathPrefix(),
			'outDir' => $outDir,
			'queue' => array(),
			'enqueued' => array(),
			'urlsFetched' => 0,
			'bytesWritten' => 0,
			'errors' => 0,
			'maxUrls' => self::SGJ_URL_HARD_CAP,
			'excludePaths' => array_merge(
				$this->sgjParseExcludePaths(),
				// Auto-exclude extra-copy dirs from the crawl: they're
				// stand-alone HTML that gets copied verbatim at the end,
				// so we don't want Bludit trying to render them.
				array_map(function ($n) { return '/' . $n; }, $this->sgjParseExtraDirs())
			),
		);

		// Seed.
		foreach ($this->sgjSeedPaths() as $path) {
			$this->sgjEnqueue($state, $path, 'page');
		}

		// BFS.
		while (!empty($state['queue']) && $state['urlsFetched'] < $state['maxUrls']) {
			$item = array_shift($state['queue']);
			$this->sgjProcessItem($state, $item);
		}

		// Copy user-supplied extra directories verbatim (e.g. resume/,
		// dumbprojects/). These live at the site root next to bl-content
		// and aren't Bludit-managed content.
		$this->sgjCopyExtraDirs($state);

		// 404 page.
		$this->sgjWriteNotFoundPage($state);

		$duration = microtime(true) - $start;
		$result = $state['errors'] === 0 ? 'ok' : 'partial';
		$message = 'Fetched ' . $state['urlsFetched'] . ' URLs (' . $state['errors'] . ' errors)';
		$this->sgjLog('Build finished: ' . $message . ' in ' . number_format($duration, 2) . 's');

		$this->recordSgjResult($result, $message, $state['urlsFetched'], $state['bytesWritten'], $duration);

		@flock($lockFp, LOCK_UN);
		@fclose($lockFp);

		$alertStatus = $state['errors'] === 0 ? ALERT_STATUS_OK : ALERT_STATUS_FAIL;
		Alert::set($message, $alertStatus);
	}

	private function recordSgjResult($result, $message, $urls, $bytes, $duration)
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
	private function sgjSeedPaths()
	{
		global $pages, $categories, $tags, $site;

		$paths = array('/');

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

		$keys = array_merge(
			(array) $pages->getDB(true),
			(array) $pages->getStaticDB(true),
			(array) $pages->getStickyDB(true)
		);
		foreach (array_unique($keys) as $key) {
			try {
				$page = new Page($key);
				$paths[] = $this->sgjUrlToPath($page->permalink(true));
			} catch (Exception $e) {
				// Skip pages that fail to construct.
			}
		}

		if (is_object($categories) && isset($categories->db) && is_array($categories->db)) {
			foreach ($categories->db as $key => $fields) {
				if (!empty($fields['list'])) {
					$paths[] = '/' . CATEGORY_URI_FILTER . '/' . $key;
				}
			}
		}

		if (is_object($tags) && isset($tags->db) && is_array($tags->db)) {
			foreach ($tags->db as $key => $fields) {
				if (!empty($fields['list'])) {
					$paths[] = '/' . TAG_URI_FILTER . '/' . $key;
				}
			}
		}

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

	private function sgjUrlToPath($url)
	{
		$parsed = parse_url($url);
		$path = isset($parsed['path']) ? $parsed['path'] : '/';
		if (isset($parsed['query']) && $parsed['query'] !== '') {
			$path .= '?' . $parsed['query'];
		}
		return $path === '' ? '/' : $path;
	}

	private function sgjParseExcludePaths()
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

	/**
	 * Parse the user's "extra directories" list. Each entry is the name
	 * of a directory at the site root (sibling of bl-content) that we
	 * copy verbatim into the build. Strict validation:
	 *   - flat name only — no path separators
	 *   - no ".." anywhere
	 *   - no leading dot (excludes .git, .env, etc.)
	 */
	private function sgjParseExtraDirs()
	{
		$raw = (string) $this->getValue('extraDirs', false);
		$out = array();
		foreach (preg_split('/\r?\n/', $raw) as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			// Lenient on paste: strip leading/trailing slashes.
			$line = trim($line, "/\\");
			if ($line === '' || $line === '.' || $line === '..') {
				continue;
			}
			if (strpbrk($line, "/\\") !== false || strpos($line, '..') !== false) {
				continue;
			}
			if ($line[0] === '.') {
				continue;
			}
			$out[] = $line;
		}
		return array_values(array_unique($out));
	}

	private function sgjCopyExtraDirs(array &$state)
	{
		$names = $this->sgjParseExtraDirs();
		if (empty($names)) {
			return;
		}
		$rootBase = rtrim(PATH_ROOT, DIRECTORY_SEPARATOR);
		$outBase = rtrim($state['outDir'], DIRECTORY_SEPARATOR);
		foreach ($names as $name) {
			$srcDir = $rootBase . DIRECTORY_SEPARATOR . $name;
			if (!is_dir($srcDir)) {
				$this->sgjLog('WARN extra dir not found: ' . $name);
				continue;
			}
			$dstDir = $outBase . DIRECTORY_SEPARATOR . $name;
			$copied = $this->sgjCopyDirRecursive($srcDir, $dstDir, $state);
			$this->sgjLog('OK  copy  /' . $name . '/ (' . $copied . ' files)');
		}
	}

	/**
	 * Recursive verbatim copy of $srcDir into $dstDir. Skips dotfile
	 * segments (.git, .env, .DS_Store, etc.) and does not follow
	 * symlinks. Updates $state['bytesWritten'] and $state['errors']
	 * but does NOT touch $state['urlsFetched'] — these aren't URLs.
	 * Returns the number of files successfully copied.
	 */
	private function sgjCopyDirRecursive($srcDir, $dstDir, array &$state)
	{
		if (!$this->sgjEnsureDir($dstDir)) {
			$this->sgjLog('ERROR mkdir ' . $dstDir);
			$state['errors']++;
			return 0;
		}
		try {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
		} catch (Throwable $e) {
			$this->sgjLog('ERROR iterating ' . $srcDir . ': ' . $e->getMessage());
			$state['errors']++;
			return 0;
		}
		$srcLen = strlen(rtrim($srcDir, DIRECTORY_SEPARATOR)) + 1;
		$copied = 0;
		foreach ($it as $entry) {
			$abs = $entry->getPathname();
			$rel = substr($abs, $srcLen);

			// Skip any dotfile/dot-directory segment for safety.
			$skip = false;
			foreach (explode(DIRECTORY_SEPARATOR, $rel) as $seg) {
				if ($seg !== '' && $seg[0] === '.') {
					$skip = true;
					break;
				}
			}
			if ($skip) {
				continue;
			}

			$dst = $dstDir . DIRECTORY_SEPARATOR . $rel;
			if ($entry->isLink()) {
				// Don't follow symlinks — silently skip.
				continue;
			}
			if ($entry->isDir()) {
				if (!$this->sgjEnsureDir($dst)) {
					$this->sgjLog('ERROR mkdir ' . $dst);
					$state['errors']++;
				}
				continue;
			}
			if ($entry->isFile()) {
				if (!$this->sgjEnsureDir(dirname($dst))) {
					$this->sgjLog('ERROR mkdir ' . dirname($dst));
					$state['errors']++;
					continue;
				}
				if (!@copy($abs, $dst)) {
					$this->sgjLog('ERROR copy ' . $abs);
					$state['errors']++;
					continue;
				}
				$size = @filesize($dst);
				if ($size !== false) {
					$state['bytesWritten'] += $size;
				}
				$copied++;
			}
		}
		return $copied;
	}

	private function sgjShouldSkip($path, array $excludePaths)
	{
		$alwaysSkip = array(
			'/' . ADMIN_URI_FILTER,
			'/bl-content/databases',
			'/bl-content/workspaces',
			'/bl-content/tmp',
		);
		foreach ($alwaysSkip as $prefix) {
			if ($this->sgjPathMatchesPrefix($path, $prefix)) {
				return true;
			}
		}
		foreach ($excludePaths as $prefix) {
			if ($this->sgjPathMatchesPrefix($path, $prefix)) {
				return true;
			}
		}
		return false;
	}

	private function sgjPathMatchesPrefix($path, $prefix)
	{
		$prefix = rtrim($prefix, '/');
		if ($prefix === '') {
			return false;
		}
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

	private function sgjEnqueue(array &$state, $path, $kind)
	{
		$path = $this->sgjNormalizePath($path, $state['sitePathPrefix']);
		if ($path === null) {
			return;
		}
		if ($this->sgjShouldSkip($path, $state['excludePaths'])) {
			return;
		}
		if (isset($state['enqueued'][$path])) {
			return;
		}
		$state['enqueued'][$path] = true;
		$state['queue'][] = array('path' => $path, 'kind' => $kind);
	}

	private function sgjNormalizePath($candidate, $sitePathPrefix)
	{
		$candidate = trim((string) $candidate);
		if ($candidate === '' || $candidate[0] === '#') {
			return null;
		}
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
		$hash = strpos($out, '#');
		if ($hash !== false) {
			$out = substr($out, 0, $hash);
		}
		if ($out === '') {
			$out = '/';
		}
		if ($out[0] !== '/') {
			return null;
		}
		if (strpos($out, '/../') !== false || substr($out, -3) === '/..') {
			return null;
		}
		if ($sitePathPrefix !== '/' && strpos($out, $sitePathPrefix) === 0) {
			$out = substr($out, strlen(rtrim($sitePathPrefix, '/')));
			if ($out === '' || $out[0] !== '/') {
				$out = '/' . ltrim($out, '/');
			}
		}
		return $out;
	}

	private function sgjProcessItem(array &$state, array $item)
	{
		try {
			$this->sgjProcessItemInner($state, $item);
		} catch (Throwable $e) {
			$state['errors']++;
			$this->sgjLog('ERROR exception processing ' . $item['path'] . ': ' . $e->getMessage());
		}
	}

	private function sgjProcessItemInner(array &$state, array $item)
	{
		$path = $item['path'];
		$kind = $item['kind'];

		if ($kind !== 'page') {
			$diskPath = $this->sgjMapToDisk($path);
			if ($diskPath !== null && is_file($diskPath)) {
				$this->sgjSaveAssetFromDisk($state, $path, $diskPath);
				return;
			}
			$state['errors']++;
			$this->sgjLog('ERROR asset not on disk: ' . $path);
			return;
		}

		$result = $this->sgjRenderInternal($path);
		if (!$result['ok']) {
			$state['errors']++;
			$this->sgjLog('ERROR render ' . $path . ' (' . $result['error'] . ')');
			return;
		}
		if ($result['notFound'] && $path !== '/404' && $path !== '/page-not-found') {
			$state['errors']++;
			$this->sgjLog('ERROR 404 ' . $path);
			return;
		}
		$state['urlsFetched']++;
		$this->sgjLog('OK  render ' . $path);

		$savePath = $this->sgjHtmlSavePath($state['outDir'], $path);
		if ($savePath === null) {
			$state['errors']++;
			$this->sgjLog('ERROR refusing unsafe save path for ' . $path);
			return;
		}
		$rewritten = $this->sgjRewriteHtml($state, $path, $result['body']);
		$bytes = $this->sgjWriteFile($savePath, $rewritten);
		if ($bytes === false) {
			$state['errors']++;
			return;
		}
		$state['bytesWritten'] += $bytes;
	}

	/**
	 * Render Bludit's configured 404 page and write it to <build>/404.html.
	 */
	private function sgjWriteNotFoundPage(array &$state)
	{
		$probe = '/__sgj_notfound__';
		$result = $this->sgjRenderInternal($probe);
		if (!$result['ok']) {
			$state['errors']++;
			$this->sgjLog('ERROR rendering 404 page: ' . $result['error']);
			return;
		}
		$rewritten = $this->sgjRewriteHtml($state, '/', $result['body']);

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
			$rewritten = '<head>' . $baseTag . '</head>' . $rewritten;
		}

		$savePath = $this->sgjResolveSafePath($state['outDir'], '404.html');
		if ($savePath === null) {
			$state['errors']++;
			$this->sgjLog('ERROR refusing unsafe save path for 404.html');
			return;
		}
		$bytes = $this->sgjWriteFile($savePath, $rewritten);
		if ($bytes === false) {
			$state['errors']++;
			return;
		}
		$state['bytesWritten'] += $bytes;
		$state['urlsFetched']++;
		$this->sgjLog('OK  render 404 -> 404.html');
	}

	private function sgjSaveAssetFromDisk(array &$state, $path, $diskPath)
	{
		$savePath = $this->sgjFileSavePath($state['outDir'], $path);
		if ($savePath === null) {
			$state['errors']++;
			return;
		}
		if (preg_match('#\.css($|\?)#i', $path)) {
			$body = @file_get_contents($diskPath);
			if ($body === false) {
				$state['errors']++;
				$this->sgjLog('ERROR reading ' . $diskPath);
				return;
			}
			$rewritten = $this->rewriteCss($state, $path, $body);
			$bytes = $this->sgjWriteFile($savePath, $rewritten);
			if ($bytes === false) {
				$state['errors']++;
				return;
			}
			$state['urlsFetched']++;
			$state['bytesWritten'] += $bytes;
			$this->sgjLog('OK  disk  ' . $path . ' [text/css]');
			return;
		}
		if (!$this->sgjEnsureDir(dirname($savePath))) {
			$state['errors']++;
			$this->sgjLog('ERROR mkdir ' . dirname($savePath));
			return;
		}
		if (!@copy($diskPath, $savePath)) {
			$state['errors']++;
			$this->sgjLog('ERROR copy ' . $diskPath . ' -> ' . $savePath);
			return;
		}
		$state['urlsFetched']++;
		$state['bytesWritten'] += @filesize($savePath);
		$this->sgjLog('OK  disk  ' . $path);
	}

	// ----------------------------------------------------------------------
	// Internal renderer
	// ----------------------------------------------------------------------
	private function sgjRenderInternal($path)
	{
		global $site, $url, $page, $content, $pages, $categories, $tags, $L, $plugins, $WHERE_AM_I;
		global $staticContent, $staticPages;
		global $helper, $login, $searchJson;

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

			$buildUrl = $this->sgjEffectiveBuildUrl();
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

			$newUrl = new Url();
			$newUrl->checkFilters($site->uriFilters());
			$url = $GLOBALS['url'] = $newUrl;

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

			$this->sgjSetupPaginator($url);

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

	private function sgjSetupPaginator($url)
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

	private function sgjSitePathPrefix()
	{
		return HTML_PATH_ROOT;
	}

	// ----------------------------------------------------------------------
	// HTML rewriting
	// ----------------------------------------------------------------------
	private function sgjRewriteHtml(array &$state, $path, $html)
	{
		$prefix = $this->sgjDepthPrefix($path);
		$crawlOrigin = '';
		$siteOrigin = $this->sgjOriginOf(DOMAIN_BASE);
		$basePath = rtrim($state['sitePathPrefix'], '/');

		$plugin = $this;
		$rewrite = function ($url) use ($plugin, &$state, $prefix, $crawlOrigin, $siteOrigin, $basePath) {
			return $plugin->rewriteRef($state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
		};

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

		$html = preg_replace_callback(
			'#(srcset\s*=\s*)"([^"]*)"#i',
			function ($m) use ($rewrite) { return $m[1] . '"' . $this->sgjRewriteSrcset($m[2], $rewrite) . '"'; },
			$html
		);
		$html = preg_replace_callback(
			"#(srcset\s*=\s*)'([^']*)'#i",
			function ($m) use ($rewrite) { return $m[1] . "'" . $this->sgjRewriteSrcset($m[2], $rewrite) . "'"; },
			$html
		);

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

		if ($crawlOrigin !== '') {
			$html = str_replace($crawlOrigin, '', $html);
		}
		if ($siteOrigin !== '' && $siteOrigin !== $crawlOrigin) {
			$html = str_replace($siteOrigin, '', $html);
		}

		$buildUrl = rtrim($this->sgjEffectiveBuildUrl(), '/');
		$buildOrigin = $this->sgjOriginOf($buildUrl);
		if ($buildOrigin !== '' && $buildOrigin !== $siteOrigin) {
			$html = str_replace($buildOrigin, '', $html);
		}
		$absOrigin = $buildOrigin !== '' ? $buildOrigin : rtrim($siteOrigin, '/');
		$metaProps = '(?:og:url|og:image|og:image:url|og:image:secure_url|og:video|og:video:url|og:video:secure_url|og:audio|og:audio:url|og:audio:secure_url)';
		$metaNames = '(?:twitter:url|twitter:image|twitter:image:src|twitter:player|twitter:player:stream)';
		$imageProps = '(?:og:image|og:image:url|og:image:secure_url|og:video|og:video:url|og:video:secure_url|og:audio|og:audio:url|og:audio:secure_url)';
		$imageNames = '(?:twitter:image|twitter:image:src|twitter:player|twitter:player:stream)';

		$enqueueFromMeta = function ($m) use (&$state) {
			$url = htmlspecialchars_decode($m[1], ENT_QUOTES);
			$this->sgjEnqueue($state, $url, 'asset');
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

	private function sgjRewriteSrcset($value, callable $rewrite)
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

	public function rewriteRef(array &$state, $url, $prefix, $crawlOrigin, $siteOrigin, $basePath, $queue)
	{
		$original = $url;
		$url = trim($url);
		if ($url === '' || $url[0] === '#') {
			return $original;
		}
		if (preg_match('#^(mailto|tel|javascript|data):#i', $url)) {
			return $original;
		}
		if (preg_match('#^https?://#i', $url)) {
			$urlOrigin = $this->sgjOriginOf($url);
			if (strcasecmp($urlOrigin, $crawlOrigin) !== 0 && strcasecmp($urlOrigin, $siteOrigin) !== 0) {
				return $original;
			}
			$path = parse_url($url, PHP_URL_PATH);
			$query = parse_url($url, PHP_URL_QUERY);
			$frag = parse_url($url, PHP_URL_FRAGMENT);
			$path = $path === null || $path === '' ? '/' : $path;
			$rebuilt = $path . ($query ? '?' . $query : '') . ($frag ? '#' . $frag : '');
			$url = $rebuilt;
		} elseif ($url[0] !== '/') {
			return $original;
		}

		if ($basePath !== '' && $basePath !== '/' && strpos($url, $basePath . '/') === 0) {
			$url = substr($url, strlen($basePath));
		} elseif ($basePath !== '' && $basePath !== '/' && $url === $basePath) {
			$url = '/';
		}

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

		$isFile = (bool) preg_match($this->sgjFileExtRegex, $url);

		if ($queue) {
			$enqueuePath = $url . $query;
			$this->sgjEnqueue($state, $enqueuePath, $isFile ? 'asset' : 'page');
		}

		$inner = ltrim($url, '/');
		if ($isFile) {
			$ref = $prefix . $inner . $query . $frag;
		} else {
			$queryNoQ = $query === '' ? '' : ltrim($query, '?');
			$rel = $this->sgjHtmlRelLinkTarget($url, $queryNoQ);
			$ref = $prefix . $rel . $frag;
		}
		return $ref === '' ? './' : $ref;
	}

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

		if (preg_match('#^https?://#i', $url)) {
			$urlOrigin = $this->sgjOriginOf($url);
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

		if ($basePath !== '' && $basePath !== '/' && strpos($url, $basePath . '/') === 0) {
			$url = substr($url, strlen($basePath));
		} elseif ($basePath !== '' && $basePath !== '/' && $url === $basePath) {
			$url = '/';
		}

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

		if (substr($url, -1) === '/') {
			$diskDir = $this->sgjMapToDiskDir($url);
			if ($diskDir !== null && is_dir($diskDir)) {
				$this->sgjEnqueueDirContents($state, $url, $diskDir);
			}
			$inner = ltrim($url, '/');
			return $prefix . $inner . $query . $frag;
		}

		return $this->rewriteRef($state, $original, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
	}

	private function sgjMapToDiskDir($urlPath)
	{
		$clean = $this->sgjCleanPath($urlPath);
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
		return $this->sgjResolveSafePath(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR), $rel);
	}

	private function sgjEnqueueDirContents(array &$state, $urlDirPath, $diskDir)
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
			if (!preg_match($this->sgjFileExtRegex, $relToRoot)) {
				continue;
			}
			$skip = false;
			foreach (explode('/', $relToRoot) as $seg) {
				if ($seg !== '' && $seg[0] === '.') { $skip = true; break; }
			}
			if ($skip) {
				continue;
			}
			$this->sgjEnqueue($state, '/' . $relToRoot, 'asset');
		}
	}

	// ----------------------------------------------------------------------
	// CSS rewriting
	// ----------------------------------------------------------------------
	public function rewriteCss(array &$state, $path, $css)
	{
		$prefix = $this->sgjDepthPrefix($path);
		$crawlOrigin = '';
		$siteOrigin = $this->sgjOriginOf(DOMAIN_BASE);
		$basePath = rtrim($state['sitePathPrefix'], '/');

		$plugin = $this;
		$rewriteOne = function ($u) use ($plugin, &$state, $prefix, $crawlOrigin, $siteOrigin, $basePath) {
			return $plugin->rewriteRef($state, $u, $prefix, $crawlOrigin, $siteOrigin, $basePath, true);
		};

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
	private function sgjHtmlSavePath($outDir, $path)
	{
		$clean = $this->sgjCleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = $this->sgjHtmlRelFilename($clean['path'], $clean['query']);
		return $this->sgjResolveSafePath($outDir, $rel);
	}

	private function sgjHtmlRelFilename($path, $query)
	{
		$pathPart = trim($path, '/');
		$pageNum = $this->sgjParsePageQuery($query);

		if ($query === '' || $pageNum === 1) {
			return $pathPart === '' ? 'index.html' : $pathPart . '/index.html';
		}
		if ($pageNum !== null) {
			$sub = 'page/' . $pageNum . '/index.html';
			return $pathPart === '' ? $sub : $pathPart . '/' . $sub;
		}

		$qSafe = preg_replace('/[^A-Za-z0-9_\-=&]/', '_', $query);
		return $pathPart === '' ? 'index.' . $qSafe . '.html' : $pathPart . '/index.' . $qSafe . '.html';
	}

	private function sgjHtmlRelLinkTarget($path, $query)
	{
		$pathPart = trim($path, '/');
		$pageNum = $this->sgjParsePageQuery($query);

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

	private function sgjParsePageQuery($query)
	{
		if ($query === '' || !preg_match('/^page=(\d+)$/', $query, $m)) {
			return null;
		}
		$n = (int) $m[1];
		return $n > 0 ? $n : null;
	}

	private function sgjFileSavePath($outDir, $path)
	{
		$clean = $this->sgjCleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = ltrim($clean['path'], '/');
		if ($rel === '' || substr($rel, -1) === '/') {
			return null;
		}
		return $this->sgjResolveSafePath($outDir, $rel);
	}

	private function sgjCleanPath($path)
	{
		$query = '';
		$qPos = strpos($path, '?');
		if ($qPos !== false) {
			$query = substr($path, $qPos + 1);
			$path = substr($path, 0, $qPos);
		}
		$path = rawurldecode($path);
		if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
			return null;
		}
		$segments = explode('/', $path);
		foreach ($segments as $s) {
			if ($s === '..') {
				return null;
			}
		}
		return array('path' => $path, 'query' => $query);
	}

	private function sgjResolveSafePath($outDir, $relative)
	{
		$relative = str_replace('/', DIRECTORY_SEPARATOR, $relative);
		$relative = ltrim($relative, DIRECTORY_SEPARATOR);
		$full = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
		$normalizedBase = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$normalized = $this->sgjLexicallyNormalize($full);
		if (strpos($normalized, $normalizedBase) !== 0) {
			return null;
		}
		return $normalized;
	}

	private function sgjLexicallyNormalize($path)
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

	private function sgjMapToDisk($path)
	{
		$clean = $this->sgjCleanPath($path);
		if ($clean === null) {
			return null;
		}
		$rel = ltrim($clean['path'], '/');
		if ($rel === '') {
			return null;
		}

		if (!preg_match($this->sgjFileExtRegex, $rel)) {
			return null;
		}

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
		foreach (explode('/', $rel) as $seg) {
			if ($seg !== '' && $seg[0] === '.') {
				return null;
			}
		}

		$candidate = $this->sgjResolveSafePath(rtrim(PATH_ROOT, DIRECTORY_SEPARATOR), $rel);
		return $candidate;
	}

	private function sgjDepthPrefix($path)
	{
		$clean = $this->sgjCleanPath($path);
		if ($clean === null) {
			return '';
		}
		if (preg_match($this->sgjFileExtRegex, $clean['path'])) {
			$p = trim($clean['path'], '/');
			if ($p === '') {
				return '';
			}
			$depth = count(explode('/', $p)) - 1;
			return $depth > 0 ? str_repeat('../', $depth) : '';
		}
		$rel = $this->sgjHtmlRelFilename($clean['path'], $clean['query']);
		$depth = substr_count($rel, '/');
		return $depth > 0 ? str_repeat('../', $depth) : '';
	}

	private function sgjOriginOf($url)
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

	private function sgjEffectiveBuildUrl()
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
		return rtrim(PATH_CONTENT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::SGJ_OUTPUT_DIRNAME . DIRECTORY_SEPARATOR;
	}

	private function sgjEnsureDir($dir)
	{
		if (is_dir($dir)) {
			return true;
		}
		return @mkdir($dir, defined('DIR_PERMISSIONS') ? DIR_PERMISSIONS : 0755, true);
	}

	private function sgjWriteFile($path, $contents)
	{
		if (!$this->sgjEnsureDir(dirname($path))) {
			$this->sgjLog('ERROR mkdir ' . dirname($path));
			return false;
		}
		$tmp = $path . '.part';
		$wrote = @file_put_contents($tmp, $contents);
		if ($wrote === false) {
			$this->sgjLog('ERROR write ' . $path);
			return false;
		}
		if (!@rename($tmp, $path)) {
			@unlink($tmp);
			$this->sgjLog('ERROR rename ' . $path);
			return false;
		}
		return $wrote;
	}

	private function sgjClearDir($dir)
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
	private function sgjLogPath()
	{
		return $this->workspace() . self::SGJ_LOG_FILENAME;
	}

	private function sgjLogReset()
	{
		@file_put_contents($this->sgjLogPath(), '');
	}

	private function sgjLog($msg)
	{
		@file_put_contents($this->sgjLogPath(), '[' . date('H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
	}

	private function readSgjLogTail()
	{
		$p = $this->sgjLogPath();
		if (!is_file($p)) {
			return '';
		}
		$raw = @file_get_contents($p);
		if ($raw === false) {
			return '';
		}
		$lines = preg_split('/\r?\n/', rtrim($raw, "\r\n"));
		if (count($lines) > self::SGJ_MAX_LOG_LINES) {
			$lines = array_slice($lines, -self::SGJ_MAX_LOG_LINES);
		}
		return implode(PHP_EOL, $lines);
	}
}
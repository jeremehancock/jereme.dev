<?php

/*
 * jereme-dev-pro Companion
 *
 * Single plugin that replaces six older -jereme / navigation plugins:
 *   - categories-jereme           (sidebar Categories widget, Archived sorted last)
 *   - navigation                  (sidebar Latest Posts widget)
 *   - static-pages-jereme         (sidebar About widget for static pages)
 *   - open-links-new-tab-jereme   (target=_blank on external anchors)
 *   - version-jereme              (admin sidebar version chip + AJAX update check)
 *   - web-stats-jereme            (Statcounter / similar injected at body end)
 *
 * Pair this plugin with the jereme-dev-pro theme. The theme's php/aside.php
 * already reads the same description=='external'/'404' conventions on static
 * pages; this plugin mirrors them in the sidebar widget.
 */

class pluginJeremeDevProCompanion extends Plugin
{
	public function init()
	{
		$this->dbFields = array(
			// Categories widget
			'categoriesEnabled'      => true,
			'categoriesLabel'        => 'Categories',
			'categoriesHideEmpty'    => true,

			// Latest Posts widget
			'latestEnabled'          => true,
			'latestLabel'            => 'Latest Posts',
			'latestNumberOfItems'    => 3,

			// About widget (static pages)
			'staticEnabled'          => true,
			'staticLabel'            => 'About',

			// External link behavior
			'targetBlankEnabled'     => true,

			// Web stats
			'webStatsDevport'        => '',
			'webStatsCode'           => '',

			// Admin version check
			'versionCheckEnabled'    => true,

			// Custom HTML injection — website
			'htmlHead'               => '',
			'htmlBodyBegin'          => '',
			'htmlBodyEnd'            => '',

			// Custom HTML injection — admin
			'htmlAdminHead'          => '',
			'htmlAdminBodyBegin'     => '',
			'htmlAdminBodyEnd'       => '',

			// RSS feed
			'rssEnabled'             => true,
			'rssNumberOfItems'       => 5,

			// Sitemap
			'sitemapEnabled'         => true,
		);
	}

	// ------------------------------------------------------------------
	// Admin settings form
	// ------------------------------------------------------------------
	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary mb-4" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		// ============ SECTION: Sidebar widgets ============================
		$html .= $this->openCard('jdpc-section-sidebar', 'list-alt', 'jdpc-section-sidebar-subtitle');

		// Categories sub-section
		$html .= $this->subHeading('jdpc-section-categories');
		$html .= $this->selectField('categoriesEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('categoriesLabel', $L->get('Label'));
		$html .= $this->selectField('categoriesHideEmpty', $L->get('jdpc-hide-empty-categories'));

		// Latest Posts sub-section
		$html .= $this->subHeading('jdpc-section-latest');
		$html .= $this->selectField('latestEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('latestLabel', $L->get('Label'));
		if (defined('ORDER_BY') && ORDER_BY === 'date') {
			$html .= $this->numberField('latestNumberOfItems', $L->get('jdpc-amount-of-items'), 1);
		}

		// About sub-section
		$html .= $this->subHeading('jdpc-section-static');
		$html .= $this->selectField('staticEnabled', $L->get('jdpc-show-widget'));
		$html .= $this->textField('staticLabel', $L->get('Label'));

		$html .= $this->closeCard();

		// ============ SECTION: External links =============================
		$html .= $this->openCard('jdpc-section-external', 'external-link-alt', 'jdpc-section-external-subtitle');
		$html .= $this->selectField('targetBlankEnabled', $L->get('jdpc-enable-external-target-blank'));
		$html .= $this->closeCard();

		// ============ SECTION: Feeds & sitemap ============================
		$html .= $this->openCard('jdpc-section-feeds', 'rss', 'jdpc-section-feeds-subtitle');

		// RSS sub-section
		$html .= $this->subHeading('jdpc-subsection-rss');
		$html .= $this->selectField('rssEnabled', $L->get('jdpc-enable-feed'));
		$html .= $this->readonlyUrlField('jdpc-rss-url-label', DOMAIN_BASE . 'rss.xml');
		$html .= $this->numberField('rssNumberOfItems', $L->get('jdpc-rss-items-label'), 1, $L->get('jdpc-rss-items-tip'));

		// Sitemap sub-section
		$html .= $this->subHeading('jdpc-subsection-sitemap');
		$html .= $this->selectField('sitemapEnabled', $L->get('jdpc-enable-feed'));
		$html .= $this->readonlyUrlField('jdpc-sitemap-url-label', DOMAIN_BASE . 'sitemap.xml');

		$html .= $this->closeCard();

		// ============ SECTION: Web stats ==================================
		$html .= $this->openCard('jdpc-section-stats', 'chart-bar', 'jdpc-section-stats-subtitle');
		$html .= $this->numberField('webStatsDevport', $L->get('jdpc-dev-port'), null, $L->get('jdpc-dev-port-tip'));
		$html .= '<div class="form-group">';
		$html .= '<label for="jdpcStatsCode"><strong>' . $L->get('jdpc-stats-code') . '</strong></label>';
		$html .= '<textarea id="jdpcStatsCode" class="form-control" name="webStatsCode" rows="8" style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.85rem;">' . $this->getValue('webStatsCode') . '</textarea>';
		$html .= '<small class="form-text text-muted">' . $L->get('jdpc-stats-code-tip') . '</small>';
		$html .= '</div>';
		$html .= $this->closeCard();

		// ============ SECTION: Custom HTML injection ======================
		$html .= $this->openCard('jdpc-section-html', 'code', 'jdpc-section-html-subtitle');

		$html .= $this->subHeading('jdpc-section-html-website');
		$html .= $this->textareaField('htmlHead',      $L->get('jdpc-html-head-label'),       $L->get('jdpc-html-head-tip'));
		$html .= $this->textareaField('htmlBodyBegin', $L->get('jdpc-html-bodybegin-label'),  $L->get('jdpc-html-bodybegin-tip'));
		$html .= $this->textareaField('htmlBodyEnd',   $L->get('jdpc-html-bodyend-label'),    $L->get('jdpc-html-bodyend-tip'));

		$html .= $this->subHeading('jdpc-section-html-admin');
		$html .= $this->textareaField('htmlAdminHead',      $L->get('jdpc-html-head-label'),      $L->get('jdpc-html-adminhead-tip'));
		$html .= $this->textareaField('htmlAdminBodyBegin', $L->get('jdpc-html-bodybegin-label'), $L->get('jdpc-html-adminbodybegin-tip'));
		$html .= $this->textareaField('htmlAdminBodyEnd',   $L->get('jdpc-html-bodyend-label'),   $L->get('jdpc-html-adminbodyend-tip'));

		$html .= $this->closeCard();

		// ============ SECTION: Admin version check ========================
		$html .= $this->openCard('jdpc-section-version', 'tag', 'jdpc-section-version-subtitle');
		$html .= $this->selectField('versionCheckEnabled', $L->get('jdpc-enable-version-check'));
		$html .= $this->closeCard();

		// ============ SECTION: How it works ===============================
		$html .= '<div class="card mb-4 border-info">';
		$html .= '<div class="card-header bg-info text-white">';
		$html .= '<span class="fa fa-info-circle mr-2"></span>';
		$html .= '<strong>' . $L->get('jdpc-section-howitworks') . '</strong>';
		$html .= '</div>';
		$html .= '<div class="card-body">';
		$html .= '<ul class="mb-0" style="padding-left: 1.2rem; line-height: 1.7;">';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-external')   . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-404')        . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-archived')   . '</li>';
		$html .= '<li>'              . $L->get('jdpc-howitworks-targetblank'). '</li>';
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	// ------------------------------------------------------------------
	// Form rendering helpers
	// ------------------------------------------------------------------
	private function openCard($titleKey, $icon = null, $subtitleKey = null)
	{
		global $L;
		$html  = '<div class="card mb-4 shadow-sm">';
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

	private function subHeading($titleKey)
	{
		global $L;
		return '<h6 class="text-uppercase text-muted mt-3 mb-2" style="letter-spacing: 0.05em; font-size: 0.75rem;">'
		     . $L->get($titleKey)
		     . '</h6><hr class="mt-1 mb-3">';
	}

	private function textField($name, $labelText, $tip = null)
	{
		$html  = '<div class="form-group">';
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
		$html  = '<div class="form-group">';
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
		$html  = '<div class="form-group">';
		$html .= '<label><strong>' . $L->get($labelKey) . '</strong></label>';
		$html .= '<div><a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">' . $safeUrl . '</a></div>';
		$html .= '</div>';
		return $html;
	}

	private function textareaField($name, $labelText, $tip = null, $rows = 4)
	{
		$html  = '<div class="form-group">';
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
		$html  = '<div class="form-group">';
		$html .= '<label for="jdpc_' . $name . '"><strong>' . $labelText . '</strong></label>';
		$html .= '<select id="jdpc_' . $name . '" class="form-control" name="' . $name . '">';
		$html .= '<option value="true" '  . ($val === true  ? 'selected' : '') . '>' . $L->get('Enabled')  . '</option>';
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
		if ($this->getValue('categoriesEnabled')) { $out .= $this->renderCategoriesWidget(); }
		if ($this->getValue('latestEnabled'))     { $out .= $this->renderLatestPostsWidget(); }
		if ($this->getValue('staticEnabled'))     { $out .= $this->renderStaticPagesWidget(); }
		return $out;
	}

	private function renderCategoriesWidget()
	{
		global $categories;

		$label    = $this->getValue('categoriesLabel');
		$hideZero = $this->getValue('categoriesHideEmpty');

		$html  = '<div class="plugin plugin-categories">';
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">' . $label . '</h2>';
		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		// Separate "Archived" so it can be pinned to the bottom regardless of alphabetical order
		$regular  = array();
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

	private function renderLatestPostsWidget()
	{
		global $pages;

		$label = $this->getValue('latestLabel');

		$html  = '<div class="plugin plugin-navigation">';
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

	private function renderStaticPagesWidget()
	{
		$label = $this->getValue('staticLabel');

		$html  = '<div class="plugin plugin-static-pages">';
		if (!empty($label)) {
			$html .= '<h2 class="plugin-label">' . $label . '</h2>';
		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		// Static pages whose description is set to special markers:
		//   "404"      -> hidden from this widget (theme also hides them)
		//   "external" -> rendered with target=_blank rel=noopener (theme matches)
		$staticPages = buildStaticPages();
		foreach ($staticPages as $p) {
			$desc = $p->description();
			if ($desc === '404') {
				continue;
			}
			$liClass = $p->isParent() ? 'parent' : 'subpage';
			$liStyle = $p->isParent() ? '' : ' style="margin-left: 10px"';
			$html .= '<li class="' . $liClass . '"' . $liStyle . '>';
			if ($desc === 'external') {
				$html .= '<a href="' . $p->permalink() . '" target="_blank" rel="noopener noreferrer">' . $p->title() . '</a>';
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
		// RSS feed discovery link — only emit it when the feed is actually enabled,
		// otherwise readers would follow it to a 404.
		if ($this->getValue('rssEnabled')) {
			$out .= '<link rel="alternate" type="application/rss+xml" href="' . DOMAIN_BASE . 'rss.xml" title="RSS Feed">' . PHP_EOL;
		}
		$out .= $this->decodedValue('htmlHead');
		return $out;
	}

	// ------------------------------------------------------------------
	// Webhooks: intercept /rss.xml and /sitemap.xml requests and stream
	// the cached XML files. Bludit's normal page router never sees these
	// paths because we exit() before it runs.
	// ------------------------------------------------------------------
	public function beforeAll()
	{
		// When a feed is disabled, don't intercept its URL — Bludit's normal page
		// router takes over and the URL 404s (no slug matches "rss.xml"/"sitemap.xml").
		if ($this->getValue('rssEnabled') && $this->webhook('rss.xml')) {
			$this->serveXml($this->workspace() . 'rss.xml');
		}
		if ($this->getValue('sitemapEnabled') && $this->webhook('sitemap.xml')) {
			$this->serveXml($this->workspace() . 'sitemap.xml');
		}
	}

	private function serveXml($file)
	{
		if (!file_exists($file)) {
			// File missing — regenerate on the fly so the URL never 404s.
			if (basename($file) === 'rss.xml') {
				$this->createRssXml();
			} else {
				$this->createSitemapXml();
			}
		}
		header('Content-type: text/xml');
		$doc = new DOMDocument();
		// External entity loading is disabled by default in PHP 8.0+; no XXE risk.
		$doc->load($file);
		echo $doc->saveXML();
		exit(0);
	}

	// ------------------------------------------------------------------
	// Page lifecycle: regenerate both XML files whenever the page set changes.
	// ------------------------------------------------------------------
	public function afterPageCreate() { $this->regenerateFeeds(); }
	public function afterPageModify() { $this->regenerateFeeds(); }
	public function afterPageDelete() { $this->regenerateFeeds(); }

	private function regenerateFeeds()
	{
		// Don't regenerate a feed while it's disabled — leaves the on-disk XML
		// untouched so a re-enable doesn't require an intervening page edit.
		if ($this->getValue('rssEnabled'))     { $this->createRssXml(); }
		if ($this->getValue('sitemapEnabled')) { $this->createSitemapXml(); }
	}

	// Regenerate XML when settings are saved or the plugin is installed.
	// Routes through regenerateFeeds() so the enable toggles are respected.
	public function post()
	{
		$result = parent::post();
		$this->regenerateFeeds();
		return $result;
	}

	public function install($position = 1)
	{
		parent::install($position);
		$this->regenerateFeeds();
		return true;
	}

	public function siteBodyBegin()
	{
		return $this->decodedValue('htmlBodyBegin');
	}

	public function siteBodyEnd()
	{
		$out = '';

		if ($this->getValue('targetBlankEnabled')) {
			// External-link normalizer. Runs once at body-end (parser has reached
			// every <a> by then). For any anchor whose hostname differs from the
			// page's, sets target=_blank and ensures rel includes both noopener
			// and noreferrer — prevents reverse-tabnabbing on links from post
			// content (theme-rendered external anchors already set rel inline).
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
		$code    = $this->getValue('webStatsCode');
		if (!empty($code)) {
			$serverPort = isset($_SERVER['SERVER_PORT']) ? (string) $_SERVER['SERVER_PORT'] : '';
			$onDevPort  = ($devport !== '' && $serverPort === (string) $devport);
			if (!$onDevPort) {
				// Stored value was entity-encoded on save by Plugin::post() ->
				// Sanitize::html; decode here to emit the original markup.
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

		$html  = '<a id="current-version" class="nav-link" href="' . HTML_PATH_ADMIN_ROOT . 'about">';
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
		return $this->decodedValue('htmlAdminHead');
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

		$out .= $this->decodedValue('htmlAdminBodyEnd');

		return $out;
	}

	// Decode a stored html-coded value for output. Stored values were entity-encoded
	// on save via Plugin::post() -> Sanitize::html; this reverses that to emit the
	// original raw markup. Returns '' for empty values so concatenation is harmless.
	private function decodedValue($key)
	{
		$val = $this->getValue($key);
		return ($val === '' || $val === null) ? '' : html_entity_decode($val);
	}

	// ------------------------------------------------------------------
	// XML generation (RSS feed + sitemap)
	//
	// The upstream rss/sitemap plugins concatenated dynamic values into XML
	// without escaping, so any '&' in $site->description() or a page title
	// silently broke loadXML(), and save() then truncated the file to just
	// the prolog. This implementation routes every dynamic value through
	// xmlText() / xmlAttr() so the XML always parses.
	// ------------------------------------------------------------------

	// Escape arbitrary string for XML text content or attribute.
	private function xmlText($s)
	{
		return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}

	// URL-encode any non-ASCII byte in a URL so it's valid in an XML context.
	// Mirrors what the upstream rss plugin did with its private encodeURL().
	private function encodeUrlBytes($url)
	{
		return preg_replace_callback('/[^\x20-\x7f]/', function ($m) {
			return urlencode($m[0]);
		}, (string) $url);
	}

	private function ensureWorkspace()
	{
		$ws = $this->workspace();
		if (!is_dir($ws)) {
			mkdir($ws, 0755, true);
		}
		return $ws;
	}

	private function createRssXml()
	{
		global $site;
		global $pages;

		$ws = $this->ensureWorkspace();

		$n = (int) $this->getValue('rssNumberOfItems');
		if ($n < 1) { $n = 5; }

		$list = $pages->getList(
			$pageNumber    = 1,
			$numberOfItems = $n,
			$published     = true,
			$static        = true,
			$sticky        = true,
			$draft         = false,
			$scheduled     = false
		);

		$xml  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
		$xml .= '<channel>';
		$xml .= '<atom:link href="' . $this->xmlText(DOMAIN_BASE . 'rss.xml') . '" rel="self" type="application/rss+xml" />';
		$xml .= '<title>'       . $this->xmlText($site->title())                       . '</title>';
		$xml .= '<link>'        . $this->xmlText($this->encodeUrlBytes($site->url())) . '</link>';
		$xml .= '<description>' . $this->xmlText($site->description())                 . '</description>';
		$xml .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';

		foreach ($list as $pageKey) {
			try {
				$page = new Page($pageKey);
				$xml .= '<item>';
				$xml .= '<title>' . $this->xmlText($page->title()) . '</title>';
				$xml .= '<link>'  . $this->xmlText($this->encodeUrlBytes($page->permalink())) . '</link>';
				$cover = $page->coverImage(true);
				if (!empty($cover)) {
					$xml .= '<image>' . $this->xmlText($cover) . '</image>';
				}
				// contentBreak() returns text/HTML. We escape it for safe inclusion as
				// text content (same approach the upstream plugin used via Sanitize::html).
				$xml .= '<description>' . $this->xmlText($page->contentBreak()) . '</description>';
				$xml .= '<pubDate>' . date(DATE_RSS, strtotime($page->getValue('dateRaw'))) . '</pubDate>';
				$xml .= '<guid isPermaLink="false">' . $this->xmlText($page->uuid()) . '</guid>';
				$xml .= '</item>';
			} catch (Exception $e) {
				// Skip pages that fail to construct
			}
		}

		$xml .= '</channel></rss>';

		$doc = new DOMDocument();
		$doc->formatOutput = true;
		// Use libxml errors so we can decide what to do on failure rather than silently truncating.
		libxml_use_internal_errors(true);
		$loaded = $doc->loadXML($xml);
		libxml_clear_errors();
		if (!$loaded) {
			// Don't overwrite an existing-good file with truncated garbage.
			return false;
		}
		return $doc->save($ws . 'rss.xml');
	}

	private function createSitemapXml()
	{
		global $site;
		global $pages;

		$ws = $this->ensureWorkspace();

		$xml  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$xml .= '<url><loc>' . $this->xmlText($site->url()) . '</loc></url>';

		$list = $pages->getList(
			$pageNumber    = 1,
			$numberOfItems = -1,
			$published     = true,
			$static        = true,
			$sticky        = true,
			$draft         = false,
			$scheduled     = false
		);
		foreach ($list as $pageKey) {
			try {
				$page = new Page($pageKey);
				if ($page->noindex()) { continue; }
				$xml .= '<url>';
				$xml .= '<loc>' . $this->xmlText($page->permalink()) . '</loc>';
				$xml .= '<lastmod>' . $this->xmlText($page->date(SITEMAP_DATE_FORMAT)) . '</lastmod>';
				$xml .= '</url>';
			} catch (Exception $e) {
				// Skip pages that fail to construct
			}
		}

		$xml .= '</urlset>';

		$doc = new DOMDocument();
		$doc->formatOutput = true;
		libxml_use_internal_errors(true);
		$loaded = $doc->loadXML($xml);
		libxml_clear_errors();
		if (!$loaded) {
			return false;
		}
		return $doc->save($ws . 'sitemap.xml');
	}

}

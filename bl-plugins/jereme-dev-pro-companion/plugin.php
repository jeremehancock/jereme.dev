<?php

class pluginJeremeDevProCompanion extends Plugin
{
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
			'ogDefaultImage' => 'bl-themes/jereme-dev-pro/img/jereme-meta.png',
			'ogFbAppId' => '',

			// Twitter / X Card meta tags
			'twitterCardsEnabled' => false,
			'twitterCardType' => 'summary_large_image',
			'twitterSite' => '',
			'twitterDefaultImage' => 'bl-themes/jereme-dev-pro/img/jereme-meta.png',

			// EasyMDE markdown editor — off by default; opt-in.
			'easymdeEnabled' => false,
			'easymdeTabSize' => '2',
			'easymdeToolbar' => '"bold", "italic", "heading", "|", "quote", "unordered-list", "|", "link", "image", "code", "horizontal-rule", "|", "preview", "side-by-side", "fullscreen"',
			'easymdeSpellChecker' => true,
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

		$html = '<div class="alert alert-primary mb-4" role="alert">';
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

		// ============ SECTION: Social previews ============================
		$html .= $this->openCard('jdpc-section-social', 'share-alt', 'jdpc-section-social-subtitle');

		// Open Graph sub-section
		$html .= $this->subHeading('jdpc-subsection-og');
		$html .= $this->selectField('ogEnabled', $L->get('jdpc-enable-meta'));
		$html .= $this->textField('ogDefaultImage', $L->get('jdpc-default-image-label'), $L->get('jdpc-og-default-image-tip'));
		$html .= $this->textField('ogFbAppId', $L->get('jdpc-og-fb-appid-label'), $L->get('jdpc-og-fb-appid-tip'));

		// Twitter / X Card sub-section
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
		$html .= $this->textareaField('htmlHead', $L->get('jdpc-html-head-label'), $L->get('jdpc-html-head-tip'));
		$html .= $this->textareaField('htmlBodyBegin', $L->get('jdpc-html-bodybegin-label'), $L->get('jdpc-html-bodybegin-tip'));
		$html .= $this->textareaField('htmlBodyEnd', $L->get('jdpc-html-bodyend-label'), $L->get('jdpc-html-bodyend-tip'));

		$html .= $this->subHeading('jdpc-section-html-admin');
		$html .= $this->textareaField('htmlAdminHead', $L->get('jdpc-html-head-label'), $L->get('jdpc-html-adminhead-tip'));
		$html .= $this->textareaField('htmlAdminBodyBegin', $L->get('jdpc-html-bodybegin-label'), $L->get('jdpc-html-adminbodybegin-tip'));
		$html .= $this->textareaField('htmlAdminBodyEnd', $L->get('jdpc-html-bodyend-label'), $L->get('jdpc-html-adminbodyend-tip'));

		$html .= $this->closeCard();

		// ============ SECTION: Markdown editor (EasyMDE) ==================
		$html .= $this->openCard('jdpc-section-easymde', 'edit', 'jdpc-section-easymde-subtitle');
		$html .= $this->selectField('easymdeEnabled', $L->get('jdpc-enable-easymde'));
		$html .= $this->textField('easymdeTabSize', $L->get('jdpc-easymde-tabsize-label'), $L->get('jdpc-easymde-tabsize-tip'));
		$html .= $this->textField('easymdeToolbar', $L->get('jdpc-easymde-toolbar-label'), $L->get('jdpc-easymde-toolbar-tip'));
		$html .= $this->selectField('easymdeSpellChecker', $L->get('jdpc-easymde-spellchecker-label'));
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
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-external') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-404') . '</li>';
		$html .= '<li class="mb-2">' . $L->get('jdpc-howitworks-archived') . '</li>';
		$html .= '<li>' . $L->get('jdpc-howitworks-targetblank') . '</li>';
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
				$html .= '<a href="' . htmlspecialchars($externalUrl, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer">' . $p->title() . '</a>';
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
}
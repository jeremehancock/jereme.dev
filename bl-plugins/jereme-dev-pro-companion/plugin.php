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
			'categoriesLabel'        => 'Categories',
			'categoriesHideEmpty'    => true,

			// Latest Posts widget
			'latestLabel'            => 'Latest Posts',
			'latestNumberOfItems'    => 3,

			// About widget (static pages)
			'staticLabel'            => 'About',

			// External link behavior
			'targetBlankEnabled'     => true,

			// Web stats
			'webStatsDevport'        => '',
			'webStatsCode'           => '',

			// Admin version check
			'versionCheckEnabled'    => true,
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
		$html .= $this->textField('categoriesLabel', $L->get('Label'));
		$html .= $this->selectField('categoriesHideEmpty', $L->get('jdpc-hide-empty-categories'));

		// Latest Posts sub-section
		$html .= $this->subHeading('jdpc-section-latest');
		$html .= $this->textField('latestLabel', $L->get('Label'));
		if (defined('ORDER_BY') && ORDER_BY === 'date') {
			$html .= $this->numberField('latestNumberOfItems', $L->get('jdpc-amount-of-items'), 1);
		}

		// About sub-section
		$html .= $this->subHeading('jdpc-section-static');
		$html .= $this->textField('staticLabel', $L->get('Label'));

		$html .= $this->closeCard();

		// ============ SECTION: External links =============================
		$html .= $this->openCard('jdpc-section-external', 'external-link-alt', 'jdpc-section-external-subtitle');
		$html .= $this->selectField('targetBlankEnabled', $L->get('jdpc-enable-external-target-blank'));
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
		return $this->renderCategoriesWidget()
		     . $this->renderLatestPostsWidget()
		     . $this->renderStaticPagesWidget();
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
	// Frontend: body end
	//   - target=_blank script for external anchors (with rel=noopener noreferrer)
	//   - web stats code (skipped when SERVER_PORT matches the configured dev port)
	// ------------------------------------------------------------------
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

	public function adminBodyEnd()
	{
		if (!$this->getValue('versionCheckEnabled')) {
			return '';
		}
		$jsPath = $this->phpPath() . 'js' . DS . 'version.js';
		if (!file_exists($jsPath)) {
			return '';
		}
		return '<script>' . file_get_contents($jsPath) . '</script>';
	}
}

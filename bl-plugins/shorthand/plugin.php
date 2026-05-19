<?php

/*
 * Shorthand - Custom Shortcodes for Bludit
 *
 * Define reusable shortcodes in the plugin settings and drop them anywhere
 * in a page or post. Supports three call forms:
 *
 *   [name]                          -> simple replacement
 *   [name foo="value" ...]          -> with attributes ({foo} in the template)
 *   [name attr="value"]inner[/name] -> wrapping a block ({content} in the template)
 *
 * Attribute values written by page authors are HTML-escaped when substituted
 * into the admin-defined template, so a low-privilege editor cannot inject
 * script through a shortcode call.
 */

class pluginShorthand extends Plugin {

	const MAX_PASSES = 3;

	public function init()
	{
		$this->dbFields = array(
			'shortcodes' => json_encode(array())
		);
	}

	// ------------------------------------------------------------------
	// Admin form
	// ------------------------------------------------------------------

	public function form()
	{
		global $L;

		$shortcodes = $this->getShortcodes();

		$html  = $this->panelStyles();
		$html .= '<div class="shorthand-panel">';

		$html .= '<div class="alert alert-primary" role="alert">';
		$html .= '<strong>Shorthand</strong> &mdash; define a tag once, drop it anywhere in your content.';
		$html .= '</div>';

		// How to use
		$html .= '<details class="sh-details">';
		$html .= '<summary>How to use</summary>';
		$html .= '<div class="sh-details-body">';
		$html .= '<p><strong>1.</strong> Pick a <strong>name</strong> (letters, digits, <code>-</code>, <code>_</code>).</p>';
		$html .= '<p><strong>2.</strong> Write a <strong>template</strong>. For every attribute you want to plug in, put <code>{your-attr-name}</code> in the template &mdash; the name is up to you and just has to match the shortcode call (e.g. <code>{to}</code> in the template pairs with <code>to="..."</code> in the page). Use <code>{content}</code> (literal name) if you want to wrap text with <code>[tag]...[/tag]</code>.</p>';
		$html .= '<p><strong>3.</strong> Use the shortcode in any page or post &mdash; works in both the Markdown and HTML editors.</p>';
		$html .= '<p class="sh-table-label"><strong>Examples</strong></p>';
		$html .= '<table class="sh-tbl">';
		$html .= '<thead><tr><th>Name</th><th>Template</th><th>Used as</th></tr></thead>';
		$html .= '<tbody>';
		$html .= '<tr><td><code>year</code></td><td><code>2026</code></td><td><code>[year]</code></td></tr>';
		$html .= '<tr><td><code>email</code></td><td><code>&lt;a href="mailto:{to}"&gt;{to}&lt;/a&gt;</code></td><td><code>[email to="hi@example.com"]</code></td></tr>';
		$html .= '<tr><td><code>callout</code></td><td><code>&lt;div class="callout {type}"&gt;{content}&lt;/div&gt;</code></td><td><code>[callout type="warning"]Heads up![/callout]</code></td></tr>';
		$html .= '<tr><td><code>yt</code></td><td><code>&lt;iframe src="https://www.youtube.com/embed/{id}" allowfullscreen&gt;&lt;/iframe&gt;</code></td><td><code>[yt id="dQw4w9WgXcQ"]</code></td></tr>';
		$html .= '</tbody></table>';
		$html .= '</div></details>';

		// Things to keep in mind — short, scannable
		$html .= '<details class="sh-details">';
		$html .= '<summary>Things to keep in mind</summary>';
		$html .= '<div class="sh-details-body sh-tips">';
		$html .= '<ul>';
		$html .= '<li><strong>Two roles, two trust levels.</strong> In Bludit, only <em>Administrators</em> can edit Shorthand templates; <em>Editors</em> can only write pages. Attribute values typed into a page (anything inside <code>[tag attr="..."]</code>) are HTML-escaped before being plugged into the template, so an Editor can\'t inject scripts through a shortcode call. If you\'re a solo admin this is defense-in-depth rather than a hard boundary &mdash; it still helps if you later add an editor, paste content from elsewhere, or another account is compromised.</li>';
		$html .= '<li><strong>URL placeholders are auto-cleaned.</strong> Attributes named <code>url</code>, <code>src</code>, <code>href</code>, <code>link</code>, <code>action</code>, <code>formaction</code>, <code>poster</code>, or <code>background</code> only accept safe schemes (<code>http</code>, <code>https</code>, <code>mailto</code>, <code>tel</code>, <code>ftp</code>, <code>sms</code>) or relative paths. <code>javascript:</code> and friends are stripped.</li>';
		$html .= '<li><strong>Don\'t put any placeholder inside <code>&lt;script&gt;</code> or <code>&lt;style&gt;</code>.</strong> HTML-escaping doesn\'t make a value safe inside JS or CSS. Shorthand will warn you if a template does this.</li>';
		$html .= '<li><strong>Pick names that don\'t collide with Markdown link references.</strong> Shortcodes are replaced before Markdown runs, so a tag named <code>foo</code> would also eat a <code>[foo]: https://...</code> link reference.</li>';
		$html .= '<li><strong>Nested shortcodes work, up to 3 passes.</strong> A template can include another shortcode, but the resolver stops after three passes to avoid loops.</li>';
		$html .= '<li><strong>Match your template to your editor.</strong> If you\'re using the <strong>HTML editor</strong>, your templates must emit HTML &mdash; the page content is stored as HTML (e.g. <code>&lt;p&gt;[mytag]&lt;/p&gt;</code>), and Markdown does not parse inside block-level HTML, so <code>**bold**</code> would show as literal text. If you\'re using the <strong>Markdown editor</strong>, templates can emit either HTML or Markdown.</li>';
		$html .= '</ul>';
		$html .= '</div></details>';

		// Inline warnings for risky templates (admin-defined; non-blocking)
		$warnings = $this->templateWarnings($shortcodes);
		if (!empty($warnings)) {
			$html .= '<div class="alert alert-warning sh-warn" role="alert">';
			$html .= '<strong>Heads up:</strong> some templates may need a second look.';
			$html .= '<ul>';
			foreach ($warnings as $name => $items) {
				$nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
				foreach ($items as $item) {
					$html .= '<li><code>'.$nameEsc.'</code> &mdash; '.$item.'</li>';
				}
			}
			$html .= '</ul>';
			$html .= '</div>';
		}

		$html .= '<div id="shorthand-list">';
		foreach ($shortcodes as $sc) {
			$html .= $this->renderRow($sc['name'], $sc['template']);
		}
		$html .= '</div>';

		// Empty-state hint when no shortcodes have been defined yet
		$emptyStyle = empty($shortcodes) ? '' : ' style="display:none;"';
		$html .= '<div id="shorthand-empty" class="sh-empty"'.$emptyStyle.'>';
		$html .= 'No shortcodes defined yet. Click <strong>+ Add shortcode</strong> below to create your first one.';
		$html .= '</div>';

		$html .= '<button type="button" id="shorthand-add" class="btn btn-secondary sh-add-btn">+ Add shortcode</button>';

		// Hidden template for new rows
		$html .= '<template id="shorthand-row-template">';
		$html .= $this->renderRow('', '');
		$html .= '</template>';

		$html .= "\n<script>\n";
		$html .= "(function(){\n";
		$html .= "  var addBtn = document.getElementById('shorthand-add');\n";
		$html .= "  var list = document.getElementById('shorthand-list');\n";
		$html .= "  var tpl = document.getElementById('shorthand-row-template');\n";
		$html .= "  var emptyEl = document.getElementById('shorthand-empty');\n";
		$html .= "  function refreshEmpty(){\n";
		$html .= "    if (!emptyEl || !list) return;\n";
		$html .= "    emptyEl.style.display = list.querySelector('.shorthand-row') ? 'none' : '';\n";
		$html .= "  }\n";
		$html .= "  if (addBtn && list && tpl) {\n";
		$html .= "    addBtn.addEventListener('click', function(){\n";
		$html .= "      list.insertAdjacentHTML('beforeend', tpl.innerHTML);\n";
		$html .= "      refreshEmpty();\n";
		$html .= "      var rows = list.querySelectorAll('.shorthand-row');\n";
		$html .= "      var lastRow = rows[rows.length - 1];\n";
		$html .= "      if (lastRow) { var nameInput = lastRow.querySelector('input[name=\"shorthand_name[]\"]'); if (nameInput) nameInput.focus(); }\n";
		$html .= "    });\n";
		$html .= "  }\n";
		$html .= "  if (list) {\n";
		$html .= "    list.addEventListener('click', function(e){\n";
		$html .= "      if (e.target && e.target.classList.contains('shorthand-remove')) {\n";
		$html .= "        var row = e.target.closest('.shorthand-row');\n";
		$html .= "        if (row) row.parentNode.removeChild(row);\n";
		$html .= "        refreshEmpty();\n";
		$html .= "      }\n";
		$html .= "    });\n";
		$html .= "  }\n";
		$html .= "})();\n";
		$html .= "</script>\n";

		$html .= '</div>'; // /.shorthand-panel

		return $html;
	}

	// CSS for the admin panel. Uses var(--name, fallback) so the panel
	// picks up dark-theme variables defined by admin themes like
	// nova-admin while falling back to neutral light values on the
	// default Bludit admin theme.
	private function panelStyles()
	{
		return <<<'CSS'
<style>
.shorthand-panel {
	--sh-border: var(--border-color, #dee2e6);
	--sh-border-strong: var(--border-light, #ced4da);
	--sh-card: var(--bg-card, #fafbfc);
	--sh-light: var(--bg-light, #f1f3f5);
	--sh-text: var(--text-primary, inherit);
	--sh-text-muted: var(--text-muted, #6c757d);
	--sh-code-bg: var(--bg-light, #f6f8fa);
	--sh-radius: var(--radius-sm, 4px);
	color: var(--sh-text);
}
.shorthand-panel .sh-details {
	border: 1px solid var(--sh-border);
	border-radius: var(--sh-radius);
	padding: 0.75rem 1rem;
	margin-bottom: 0.75rem;
	background: transparent;
}
.shorthand-panel .sh-details summary {
	cursor: pointer;
	font-weight: 600;
}
.shorthand-panel .sh-details-body {
	margin-top: 0.75rem;
}
.shorthand-panel .sh-table-label {
	margin-bottom: 0.25rem;
}
.shorthand-panel .sh-tbl {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.9em;
}
.shorthand-panel .sh-tbl th,
.shorthand-panel .sh-tbl td {
	padding: 0.4rem 0.6rem;
	border: 1px solid var(--sh-border);
	text-align: left;
	vertical-align: top;
}
.shorthand-panel .sh-tbl thead tr {
	background: var(--sh-light);
}
.shorthand-panel .sh-tips {
	font-size: 0.92em;
}
.shorthand-panel .sh-tips ul {
	margin: 0;
	padding-left: 1.2rem;
	line-height: 1.55;
}
.shorthand-panel code {
	background: var(--sh-code-bg);
	padding: 0.05rem 0.3rem;
	border-radius: 3px;
	font-size: 0.92em;
}
.shorthand-panel .sh-warn {
	margin-bottom: 1rem;
}
.shorthand-panel .sh-warn ul {
	margin: 0.5rem 0 0 0;
	padding-left: 1.2rem;
}
.shorthand-panel .sh-empty {
	padding: 1.25rem 1rem;
	border: 1px dashed var(--sh-border-strong);
	border-radius: var(--sh-radius);
	text-align: center;
	color: var(--sh-text-muted);
	margin-bottom: 1rem;
}
.shorthand-panel .shorthand-row {
	border: 1px solid var(--sh-border);
	border-radius: var(--sh-radius);
	padding: 1rem;
	margin-bottom: 1rem;
	background: var(--sh-card);
}
.shorthand-panel .sh-row-grid {
	display: flex;
	gap: 1rem;
	align-items: flex-start;
	flex-wrap: wrap;
}
.shorthand-panel .sh-col-name {
	flex: 0 0 220px;
	min-width: 180px;
}
.shorthand-panel .sh-col-tpl {
	flex: 1 1 320px;
	min-width: 240px;
}
.shorthand-panel .sh-col-actions {
	flex: 0 0 auto;
	padding-top: 1.5rem;
}
.shorthand-panel .shorthand-row label {
	display: block;
	font-weight: 600;
	margin-bottom: 0.25rem;
}
.shorthand-panel .shorthand-row input[type="text"],
.shorthand-panel .shorthand-row textarea {
	width: 100%;
}
.shorthand-panel .shorthand-row textarea {
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.shorthand-panel .sh-tip {
	font-size: 0.8em;
	color: var(--sh-text-muted);
	margin-top: 0.25rem;
}
.shorthand-panel .sh-add-btn {
	margin-top: 0.25rem;
}
</style>
CSS;
	}

	private function renderRow($name, $template)
	{
		$nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$tmplEsc = htmlspecialchars($template, ENT_QUOTES, 'UTF-8');

		$html  = '<div class="shorthand-row">';
		$html .= '<div class="sh-row-grid">';
		$html .= '<div class="sh-col-name">';
		$html .= '<label>Name</label>';
		$html .= '<input type="text" name="shorthand_name[]" value="'.$nameEsc.'" placeholder="my-tag" pattern="[a-zA-Z0-9_-]+">';
		$html .= '<div class="sh-tip">Letters, digits, <code>-</code>, <code>_</code></div>';
		$html .= '</div>';
		$html .= '<div class="sh-col-tpl">';
		$html .= '<label>Template</label>';
		$html .= '<textarea name="shorthand_template[]" rows="3">'.$tmplEsc.'</textarea>';
		$html .= '<div class="sh-tip">Use <code>{your-attr-name}</code> for each attribute (e.g. <code>{to}</code> matches <code>to="..."</code>). Use literal <code>{content}</code> for wrapped content.</div>';
		$html .= '</div>';
		$html .= '<div class="sh-col-actions">';
		$html .= '<button type="button" class="btn btn-sm btn-danger shorthand-remove">Remove</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	public function post()
	{
		$names = isset($_POST['shorthand_name']) && is_array($_POST['shorthand_name']) ? $_POST['shorthand_name'] : array();
		$templates = isset($_POST['shorthand_template']) && is_array($_POST['shorthand_template']) ? $_POST['shorthand_template'] : array();

		$shortcodes = array();
		$seen = array();
		$count = count($names);
		for ($i = 0; $i < $count; $i++) {
			$name = isset($names[$i]) ? trim((string)$names[$i]) : '';
			$template = isset($templates[$i]) ? (string)$templates[$i] : '';

			if ($name === '') {
				continue;
			}
			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
				continue;
			}
			if (isset($seen[$name])) {
				continue; // skip duplicate names
			}
			$seen[$name] = true;

			$shortcodes[] = array(
				'name' => $name,
				'template' => $template
			);
		}

		$this->db['shortcodes'] = json_encode($shortcodes);
		return $this->save();
	}

	// ------------------------------------------------------------------
	// Content processing
	// ------------------------------------------------------------------

	public function beforeSiteLoad()
	{
		if (!isset($GLOBALS['WHERE_AM_I'])) {
			return;
		}
		if ($GLOBALS['WHERE_AM_I'] === 'page' && isset($GLOBALS['page'])) {
			$GLOBALS['page']->setField('content', $this->parsePage($GLOBALS['page']));
		} elseif (isset($GLOBALS['content']) && is_array($GLOBALS['content'])) {
			foreach ($GLOBALS['content'] as $key => $page) {
				$GLOBALS['content'][$key]->setField('content', $this->parsePage($GLOBALS['content'][$key]));
			}
		}
	}

	private function parsePage($page)
	{
		$content = $page->contentRaw();
		$content = $this->applyShortcodes($content);

		if (MARKDOWN_PARSER) {
			$parsedown = new Parsedown();
			$content = $parsedown->text($content);
		}

		if (IMAGE_RELATIVE_TO_ABSOLUTE) {
			$domain = IMAGE_RESTRICT ? DOMAIN_UPLOADS_PAGES . $page->uuid() . '/' : DOMAIN_UPLOADS;
			$content = Text::imgRel2Abs($content, $domain);
		}

		return $content;
	}

	private function applyShortcodes($content)
	{
		$shortcodes = $this->getShortcodes();
		if (empty($shortcodes)) {
			return $content;
		}

		// Run a few passes so a shortcode template can contain another
		// shortcode call, but cap it to prevent infinite recursion.
		for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
			$before = $content;
			foreach ($shortcodes as $sc) {
				$content = $this->replaceOne($content, $sc['name'], $sc['template']);
			}
			if ($content === $before) {
				break;
			}
		}

		return $content;
	}

	private function replaceOne($content, $name, $template)
	{
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
			return $content;
		}
		$nameQuoted = preg_quote($name, '/');
		$hasContentPlaceholder = strpos($template, '{content}') !== false;

		if ($hasContentPlaceholder) {
			$pattern = '/\[' . $nameQuoted . '(\s+[^\]\n\r]*)?\](.*?)\[\/' . $nameQuoted . '\]/s';
			$content = preg_replace_callback($pattern, function ($m) use ($template) {
				$attrs = $this->parseAttrs(isset($m[1]) ? $m[1] : '');
				$inner = isset($m[2]) ? $m[2] : '';
				return $this->fillTemplate($template, $attrs, $inner);
			}, $content);
		} else {
			$pattern = '/\[' . $nameQuoted . '(\s+[^\]\n\r]*)?\]/';
			$content = preg_replace_callback($pattern, function ($m) use ($template) {
				$attrs = $this->parseAttrs(isset($m[1]) ? $m[1] : '');
				return $this->fillTemplate($template, $attrs, '');
			}, $content);
		}

		return $content === null ? '' : $content;
	}

	private function parseAttrs($string)
	{
		$attrs = array();
		if ($string === '' || $string === null) {
			return $attrs;
		}
		$pattern = '/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/';
		if (preg_match_all($pattern, $string, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $m) {
				$key = $m[1];
				if (isset($m[2]) && $m[2] !== '') {
					$value = $m[2];
				} elseif (isset($m[3]) && $m[3] !== '') {
					$value = $m[3];
				} else {
					$value = isset($m[4]) ? $m[4] : '';
				}
				$attrs[$key] = $value;
			}
		}
		return $attrs;
	}

	private function fillTemplate($template, $attrs, $content)
	{
		$replacements = array();
		foreach ($attrs as $key => $value) {
			if ($this->isUrlAttr($key)) {
				$value = $this->sanitizeUrl($value);
			}
			$replacements['{' . $key . '}'] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
		$replacements['{content}'] = $content;
		$result = strtr($template, $replacements);
		// Strip any leftover {placeholder} tokens that had no matching attribute.
		$result = preg_replace('/\{[a-zA-Z0-9_-]+\}/', '', $result);
		return $result;
	}

	// Attribute names that we treat as URLs. Values for these get scrubbed of
	// dangerous schemes (javascript:, data:, vbscript:, ...) before being
	// substituted into the template.
	private function isUrlAttr($key)
	{
		static $urlAttrs = array(
			'url' => true, 'src' => true, 'href' => true, 'link' => true,
			'action' => true, 'formaction' => true, 'poster' => true,
			'background' => true,
		);
		return isset($urlAttrs[strtolower($key)]);
	}

	// Allow only safe URL schemes plus relative paths / fragments / queries.
	// Returns an empty string for anything else so the template still renders
	// but the unsafe URL is gone.
	private function sanitizeUrl($value)
	{
		// Strip control characters and whitespace that browsers ignore but
		// that can be used to smuggle a scheme (e.g. "java\tscript:").
		$cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', (string)$value);
		$trimmed = ltrim($cleaned);
		if ($trimmed === '') {
			return '';
		}

		$first = $trimmed[0];
		// Relative paths, fragments and query-only links are fine.
		if ($first === '/' || $first === '#' || $first === '?' || $first === '.') {
			return $cleaned;
		}

		// If a scheme is present, allowlist it.
		if (preg_match('/^([a-zA-Z][a-zA-Z0-9+.-]*):/', $trimmed, $m)) {
			static $safeSchemes = array(
				'http' => true, 'https' => true, 'mailto' => true, 'tel' => true,
				'ftp' => true, 'ftps' => true, 'sms' => true,
			);
			if (isset($safeSchemes[strtolower($m[1])])) {
				return $cleaned;
			}
			return '';
		}

		// No scheme, no leading slash: treat as a relative path (e.g. "page/about").
		return $cleaned;
	}

	// Flag templates that mix placeholders into contexts HTML-escaping can't
	// protect. Surfaced as a non-blocking warning in the admin form.
	private function templateWarnings($shortcodes)
	{
		$warnings = array();
		foreach ($shortcodes as $sc) {
			$name = isset($sc['name']) ? $sc['name'] : '';
			$tpl = isset($sc['template']) ? $sc['template'] : '';
			if ($name === '' || $tpl === '') {
				continue;
			}
			$hasPlaceholder = preg_match('/\{[a-zA-Z0-9_-]+\}/', $tpl);
			if (!$hasPlaceholder) {
				continue;
			}
			if (preg_match('/<script\b/i', $tpl)) {
				$warnings[$name][] = 'placeholders inside <code>&lt;script&gt;</code> are not safe from XSS &mdash; HTML-escaping does not protect JavaScript string contexts.';
			}
			if (preg_match('/<style\b/i', $tpl)) {
				$warnings[$name][] = 'placeholders inside <code>&lt;style&gt;</code> are not safe from XSS &mdash; HTML-escaping does not protect CSS contexts.';
			}
			if (preg_match('/\son[a-z]+\s*=/i', $tpl)) {
				$warnings[$name][] = 'template defines an inline event handler (<code>on...="..."</code>) &mdash; if a placeholder appears inside it, the event will still fire on whatever the editor passes.';
			}
		}
		return $warnings;
	}

	private function getShortcodes()
	{
		$raw = $this->getValue('shortcodes', false);
		if (empty($raw)) {
			return array();
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : array();
	}
}

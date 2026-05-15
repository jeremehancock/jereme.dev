# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **Bludit** site (flat-file PHP CMS — no database, content lives in JSON/PHP files under `bl-content/`) powering a personal dev/homelab site. Bludit itself is upstream and kept on the latest release; the customization in this repo is limited to a custom site theme, a custom admin theme, and a single companion plugin. There is no build pipeline, no package manager, and no test suite. CSS/JS in the active theme are pre-minified by hand.

### Do not edit core

Treat the upstream as read-only. **Never edit anything under `bl-kernel/` or `bl-languages/`**, with one exception: `bl-kernel/admin/themes/nova-admin/` is the custom admin theme and is fair game. Everything else under those paths is upstream Bludit and will be overwritten on the next update.

The customization surface is:

- `bl-themes/nova/` — custom site theme
- `bl-kernel/admin/themes/nova-admin/` — custom admin theme
- `bl-plugins/nova-plugin/` — companion plugin for the Nova theme (see below)

Do not pin or reference a specific Bludit version in this file or in code — it is updated regularly.

## Running locally

```bash
php -S 0.0.0.0:8080 -t .
```

`nixpacks.toml` uses the same `php -S` invocation for deployment. `.htaccess` rewrites every non-existent path to `index.php` and 404s any direct request under `bl-content/{databases,workspaces,pages,tmp}/`. When testing locally with `php -S`, those rewrites do not run — the built-in server has different routing, so URL behavior may differ from production.

## Request flow

`index.php` → `bl-kernel/boot/init.php` (defines all `PATH_*` / `DB_*` / `DOMAIN_*` constants, sets up autoload) → dispatches to either `bl-kernel/boot/admin.php` or `bl-kernel/boot/site.php` based on `$url->whereAmI()`.

Templates and plugin methods can rely on these globals being already constructed:

| Global | Class file (`bl-kernel/`) | Purpose |
|---|---|---|
| `$site` | `site.class.php` | Site config |
| `$pages` | `pages.class.php` | Page index / queries |
| `$categories`, `$tags` | `categories.class.php`, `tags.class.php` | Taxonomies |
| `$login` | `login.class.php` | Auth state |
| `$url` | `url.class.php` | Routing |
| `$L` | `language.class.php` | i18n strings |
| `$page` | `pagex.class.php` | Current page (template context only) |

## Plugins

A plugin is a folder under `bl-plugins/{name}/` with `plugin.php` (a class extending the `Plugin` abstract in `bl-kernel/abstract/plugin.class.php`) and `metadata.json`. Optional `languages/`, `css/`, `js/`. Persistent settings live at `bl-content/databases/plugins/{name}/db.php`.

Plugins integrate by implementing named hook methods that the core calls — none of them are declared in the abstract base, the core just looks up methods by name. The ones used in this repo:

- `init()` — define `$this->dbFields` (default settings) and class state
- `form()` — render the admin settings form
- `siteHead()` / `siteBodyBegin()` / `siteBodyEnd()` — inject HTML into the frontend
- `siteSidebar()` — render a sidebar widget
- `beforeAll()` — runs before any output (used by `rss`, `sitemap`, `api` to intercept and emit non-HTML responses)
- `afterPageCreate()` / `afterPageModify()` / `afterPageDelete()` — page lifecycle
- `pageBegin()` — runs at the start of each page render

### Companion plugin (custom to this site)

There is one companion plugin: **`bl-plugins/nova-plugin/`** (class `pluginNovaPlugin`). It is the only plugin in this repo and bundles everything the Nova theme depends on — sidebar widgets, head/body injection, admin UI tweaks, OpenGraph/Twitter meta, the EasyMDE editor wiring, and an in-admin static-site generator. Previously this functionality was split across several `-jereme` plugins; do not re-introduce that split — add new behavior as a method on `pluginNovaPlugin` and wire it through the appropriate hook.

Notable hooks it implements (see `bl-plugins/nova-plugin/plugin.php`):

- `siteSidebar()` — renders the categories / latest-posts / static-pages widgets (the categories widget sorts the "Archived" category to the bottom).
- `siteHead()` / `siteBodyBegin()` / `siteBodyEnd()` — frontend injection, including OpenGraph/Twitter tags and the external-link `target="_blank"` rewrite.
- `adminSidebar()` / `adminHead()` / `adminBodyBegin()` / `adminBodyEnd()` — admin UI, including EasyMDE setup and the static-generator tab.
- `post()` — handles the static-site generator build request.

## Theme

The active site theme is `bl-themes/nova/`. The admin theme is `bl-kernel/admin/themes/nova-admin/`. Structure of the site theme:

- `index.php` — top-level HTML shell; dispatches to `php/page.php` or `php/home.php` based on `$WHERE_AM_I`.
- `php/{head,header,footer,home,page,aside}.php` — template partials.
- `php/lib/helper.php` — theme-specific helpers, notably `cdn_that_image()` / `cdn_cover_image()` which rewrite image URLs through `cdn.meln.top`.
- `init.php` — builds a search-index JSON (excludes `Uncategorized` and `Archived`).
- `js/lozad.min.js` + inline script in `index.php` — lazy-load all `<img>` in `.page-content` / `.entry-content` / `.entry-summary`, wrapping each in a `.lozad-wrap` span and applying an `is-loaded` class once the image actually paints. The two `requestAnimationFrame` calls are intentional — they let the placeholder state paint before the fade transition, otherwise cached images skip the fade.

There is no theme build step; `style.min.css` / `bundle.min.js` are edited or replaced directly.

## Data layout

Everything is files. No DB.

- `bl-content/databases/*.php` — JSON databases (PHP-prefixed so direct access is denied): `pages.php`, `site.php`, `categories.php`, `tags.php`, `users.php`, `security.php`, `syslog.php`.
- `bl-content/databases/plugins/{name}/db.php` — per-plugin settings.
- `bl-content/pages/{slug}/index.txt` — markdown body for a page.
- `bl-content/uploads/` — user uploads.
- `bl-content/tmp/` — only path in `.gitignore`.

Page `type` is one of `published`, `draft`, `sticky`, `static`, `scheduled`.

## Editing conventions

- `bl-kernel/` and `bl-languages/` are upstream — do not edit. The only exception is `bl-kernel/admin/themes/nova-admin/` (custom admin theme). If a change seems to require touching core, do it in a plugin or theme instead.
- `bl-kernel/helpers/functions.php` (~1100 LOC) holds global utilities; read it to find a helper, but do not modify it.
- Markdown parsing is `bl-kernel/parsedown.class.php` (Parsedown), vendored upstream — do not hand-edit.
- The `.htaccess` 301-redirects `/resume` and `/dumbprojects` to external URLs; the local `resume/` and `dumbprojects/` directories only render under `php -S`.

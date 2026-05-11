# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A customized fork of **Bludit v3.21.1**, a flat-file PHP CMS (no database — content lives in JSON/PHP files under `bl-content/`). This instance powers a personal dev/homelab site. There is no build pipeline, no package manager, and no test suite. CSS/JS in the active theme are pre-minified by hand.

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

### Custom plugins specific to this fork (not standard Bludit)

- **`categories-jereme`** — sidebar categories widget; sorts the "Archived" category to the bottom instead of alphabetical (see the `$regularCategories` / `$archivedCategories` split in `siteSidebar()`).
- **`static-pages-jereme`** — sidebar navigation for static pages.
- **`open-links-new-tab-jereme`** — JS injection that retargets external links to `_blank`.
- **`version-jereme`** — admin-area Bludit version display.
- **`web-stats-jereme`** — analytics script injection, with a port-based check so it does not load on dev ports.

When working on sidebar ordering or analytics behavior, edit the `-jereme` variant, not the upstream plugin of the same family.

## Theme

The active theme is `bl-themes/jereme-dev/` (there is also `jereme-dev-pro/`, which is older and less maintained — confirm before touching it). Structure:

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

- Admin code paths live under `bl-kernel/admin/`; frontend rendering goes through the theme. Both paths share the kernel classes — changing a class affects both.
- `bl-kernel/helpers/functions.php` (~1100 LOC) holds global utilities; check there before adding a new helper.
- Markdown parsing is `bl-kernel/parsedown.class.php` (Parsedown). It is vendored — do not hand-edit.
- The `.htaccess` 301-redirects `/resume` and `/dumbprojects` to external URLs; the local `resume/` and `dumbprojects/` directories only render under `php -S`.

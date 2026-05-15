# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **Bludit** site (flat-file PHP CMS — no database, content lives in JSON/PHP files under `bl-content/`) powering a personal dev/homelab site. Bludit itself is upstream and kept on the latest release; the customization in this repo is limited to a custom site theme, a custom admin theme, and a single companion plugin. There is no package manager and no test suite. The theme's CSS/JS (`theme.css` / `theme.js`) are edited directly — they're not minified or built. The site is rendered through Bludit at runtime, and the plugin's Static Site Generator can mirror it to `bl-content/static-build/` (see below).

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

There is one companion plugin: **`bl-plugins/nova-plugin/`** (class `pluginNovaPlugin`). It is the only plugin in this repo and bundles everything the Nova theme depends on. Previously this functionality was split across several `-jereme` plugins; do not re-introduce that split — add new behavior as a method on `pluginNovaPlugin` and wire it through the appropriate hook.

What it does:

- **Sidebar widgets** — categories (with "Archived" pinned to the bottom), latest posts, and a static-pages list ("About") rendered via `siteSidebar()`.
- **External-link rewrite** — JS injected via `siteBodyEnd()` retargets external anchors to `_blank` with `rel="noopener noreferrer"`.
- **OpenGraph / Twitter Card meta** — emitted in `siteHead()`, configurable in the admin form.
- **Web stats injection** — analytics code injected via `siteBodyEnd()`, skipped when `SERVER_PORT` matches the configured dev port.
- **Custom HTML injection** — six free-form fields (site head/body-begin/body-end + admin head/body-begin/body-end).
- **EasyMDE editor** — opt-in markdown editor for the page create/edit admin views (`new-content`, `edit-content`).
- **Static Site Generator (SSG)** — runs in `post()`; crawls the site, mirrors output into `bl-content/static-build/`, copies extra top-level dirs (e.g. `homelab/`) verbatim, and records status under `bl-content/workspaces/nova-plugin/` (`build.lock`, `build.log`).
- **Link Checker** — also runs in `post()`; walks all published / sticky / static pages outside the Archived category, reports broken anchors, and persists `linkcheck.lock` / `linkcheck.log` to the same workspace dir.
- **Admin UI** — `adminSidebar()` / `adminHead()` / `adminBodyBegin()` / `adminBodyEnd()` render the tabbed settings form and load EasyMDE only on the relevant admin views.

### Production URL

The plugin's **`productionUrl`** setting (Static Site Generator tab) is the single source of truth for the canonical site origin. When set, `effectiveSiteUrl()` rewrites the origin of every emitted URL — OpenGraph / Twitter / canonical meta tags, default-image absolutization, link-checker base, SSG build base — so admin work from a localhost or LAN host still produces production-facing URLs. When unset, it falls back to `$site->url()`. If you add a new feature that emits absolute URLs, route them through `effectiveSiteUrl()`.

### Static page description markers

Bludit's `description` field on **static** pages doubles as a convention used by both the theme and the plugin:

- `"404"` — marker for the 404 page; hidden from the About widget and excluded from the search index.
- `"external:<url>"` — turns the static page into an external link. The sidebar widget renders it as a `_blank` anchor to `<url>`, and the SSG writes a redirect snippet at its slug in `static-build/`.

If you're adding new static-page behavior, check `init.php` (search-index build), `siteSidebar()` (About widget), and the SSG rewrite in `nova-plugin/plugin.php` so all three stay aligned.

## Theme

The active site theme is `bl-themes/nova/`. The admin theme is `bl-kernel/admin/themes/nova-admin/`. Structure of the site theme:

- `index.php` — top-level HTML shell; dispatches to `php/page.php` or `php/home.php` based on `$WHERE_AM_I`, mounts the sidebar via `Theme::plugins('siteSidebar')`, and loads `js/lozad.min.js` + `js/theme.js`.
- `php/{head,header,footer,home,page,aside}.php` — template partials.
- `php/lib/helper.php` — theme-specific helpers, notably `cdn_that_image()` / `cdn_cover_image()` which rewrite image URLs through `cdn.meln.top` (the CDN is opt-in — `init.php` constructs `Helper` with `$useCdn=false` by default).
- `php/lib/jsondb.php` — small JSON-on-disk helper used only by `init.php` to build the search index. Not a general-purpose DB layer.
- `init.php` — builds the search index at `bl-content/uploads/bltsearch.json`. Indexes pages of type `published` / `sticky` / `static`, and excludes pages with no category, pages in the `Archived` category, and static pages whose description is `"404"` or starts with `"external:"`.
- `js/theme.js` — main theme script (search lookup against `bltsearch.json`, lazy-load wiring, etc.). Edited directly, not built.
- `js/lozad.min.js` + the lozad block in `theme.js` — lazy-load all `<img>` in `.page-content` / `.entry-content` / `.entry-summary`, wrapping each in a `.lozad-wrap` span and applying an `is-loaded` class once the image actually paints. The two `requestAnimationFrame` calls are intentional — they let the placeholder state paint before the fade transition, otherwise cached images skip the fade.
- `css/theme.css` — main stylesheet. Edited directly, not built.

There is no theme build step; the CSS/JS files above are the source.

### Admin theme (`bl-kernel/admin/themes/nova-admin/`)

This is the only path under `bl-kernel/` that is intentionally customized. Layout:

- `index.php` / `login.php` — admin shell and login page.
- `init.php` — admin theme bootstrap.
- `html/{navbar,sidebar,alert,media}.php` — admin partials.
- `css/bludit.css` + `css/bludit.bootstrap.css` — admin styles.
- `logo.svg` — admin logo.

## Data layout

Everything is files. No DB.

- `bl-content/databases/*.php` — JSON databases (PHP-prefixed so direct access is denied): `pages.php`, `site.php`, `categories.php`, `tags.php`, `users.php`, `security.php`, `syslog.php`.
- `bl-content/databases/plugins/{name}/db.php` — per-plugin settings.
- `bl-content/pages/{slug}/index.txt` — markdown body for a page.
- `bl-content/uploads/` — user uploads. Also where the theme writes `bltsearch.json` (the search index).
- `bl-content/workspaces/{plugin}/` — scratch space for plugins. The Nova plugin keeps `build.lock` / `build.log` (SSG) and `linkcheck.lock` / `linkcheck.log` (Link Checker) here.
- `bl-content/static-build/` — generated output of the Static Site Generator. **Committed to the repo** so the host can serve it as the production site. Treat it as build output: do not hand-edit; regenerate via the SSG tab in admin.
- `bl-content/tmp/` — scratch; gitignored.

Top-level static directories (siblings of `bl-content/`) can be mirrored into the SSG output via the plugin's `extraDirs` setting. `homelab/` is the current example — a stand-alone HTML directory served verbatim under `/homelab/`.

`.gitignore` excludes `bl-content/tmp` and `jereme-dev-info.md` (local notes file).

Page `type` is one of `published`, `draft`, `sticky`, `static`, `scheduled`.

### Locally-diverged tracked files

`bl-content/databases/users.php` and `bl-content/databases/site.php` are tracked, but the working copy on a real Bludit install carries values that must never reach the repo — the admin password hash + salt + auth tokens in `users.php`, and the real `adminTheme` plus host-specific fields in `site.php`. The committed copies are sanitized (`"password": "!"`, `"adminTheme": "!"`, `"url": "https:\/\/jereme.dev"`), and locally the files are masked with:

```bash
git update-index --assume-unchanged bl-content/databases/site.php
git update-index --assume-unchanged bl-content/databases/users.php
```

This is a per-clone setting, not stored in the repo. After a fresh clone, re-run those two commands before logging into admin, otherwise the next commit from that clone will leak the real values. If you need to legitimately update the sanitized copy in the repo (e.g. to add a new field), `git update-index --no-assume-unchanged <file>`, edit to the sanitized form, commit, then re-mask.

A tracked safety net lives at `scripts/git-hooks/pre-commit` — it rejects commits that stage either file with non-sanitized `password` / `adminTheme` / `url` values. Enabled per-clone with `git config core.hooksPath scripts/git-hooks`. See the README ("Keeping local credentials out of the repo") for the full table of enforced values and bypass options. If you're committing on behalf of the user and the hook fires, do not bypass with `--no-verify` — confirm with the user first; it usually means the working copy isn't masked.

## Editing conventions

- `bl-kernel/` and `bl-languages/` are upstream — do not edit. The only exception is `bl-kernel/admin/themes/nova-admin/` (custom admin theme). If a change seems to require touching core, do it in a plugin or theme instead.
- `bl-kernel/helpers/functions.php` (~1100 LOC) holds global utilities; read it to find a helper, but do not modify it.
- Markdown parsing is `bl-kernel/parsedown.class.php` (Parsedown), vendored upstream — do not hand-edit.
- The companion plugin's `productionUrl` setting is the canonical origin; emit absolute URLs through `effectiveSiteUrl()` rather than `$site->url()` so local admin work still produces production-facing URLs.

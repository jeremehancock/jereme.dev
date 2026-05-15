# jereme.dev

Source for [jereme.dev](https://jereme.dev) — my personal dev / homelab site.

## Stack

- [Bludit](https://www.bludit.com/) — flat-file PHP CMS (no database; content lives as JSON / Markdown under `bl-content/`).
- Custom site theme: `bl-themes/nova/`
- Custom admin theme: `bl-kernel/admin/themes/nova-admin/`
- Companion plugin: `bl-plugins/nova-plugin/` — sidebar widgets, OpenGraph / Twitter meta, EasyMDE editor, a static-site generator, and a link checker, all bundled into a single plugin.

Bludit core (`bl-kernel/`, `bl-languages/`) is upstream and kept on the latest release. Only the three paths above are customized in this repo.

## Hosting

The site is hosted with [Coolify](https://coolify.io/), serving the contents of `bl-content/static-build/` as a static site. The companion plugin's Static Site Generator mirrors the live Bludit site into that directory; the generated output is committed to the repo so each push deploys the latest build.

## License

MIT — see [LICENSE](LICENSE). Bludit is © Diego Najar and contributors.

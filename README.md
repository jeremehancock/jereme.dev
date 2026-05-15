# jereme.dev

Source for [jereme.dev](https://jereme.dev) — my personal dev / homelab site.

## Stack

- [Bludit](https://www.bludit.com/) — flat-file PHP CMS (no database; content lives as JSON / Markdown under `bl-content/`).
- Custom site theme: `bl-themes/nova/`
- Custom admin theme: `bl-kernel/admin/themes/nova-admin/`
- Companion plugin: `bl-plugins/nova-plugin/` — sidebar widgets, OpenGraph / Twitter meta, EasyMDE editor, a static-site generator, and a link checker, all bundled into a single plugin.

Bludit core (`bl-kernel/`, `bl-languages/`) is upstream and kept on the latest release. Only the three paths above are customized in this repo.

## Running locally

```bash
php -S 0.0.0.0:8080 -t .
```

Then open <http://localhost:8080>. The same `php -S` invocation is used for deployment via `nixpacks.toml`.

## Deployment

The companion plugin's Static Site Generator mirrors the live site to `bl-content/static-build/`, which is committed to the repo and served as the production output.

## License

MIT — see [LICENSE](LICENSE). Bludit is © Diego Najar and contributors.

# jereme.dev

Source for [jereme.dev](https://jereme.dev) — my personal dev / homelab site.

## Stack

- [Bludit](https://www.bludit.com/) — flat-file PHP CMS (no database; content lives as JSON / Markdown under `bl-content/`).
- Custom site theme: `bl-themes/nova/`
- Custom admin theme: `bl-kernel/admin/themes/nova-admin/`
- Companion plugin: `bl-plugins/nova-plugin/` — sidebar widgets, OpenGraph / Twitter meta, EasyMDE editor, a static-site generator, and a link checker, all bundled into a single plugin.

Bludit core (`bl-kernel/`, `bl-languages/`) is upstream and kept on the latest release. Only the three paths above are customized in this repo.

## Authoring & hosting

Bludit runs locally in a [Vagrant](https://jereme.dev/bludit-vagrant/) VM, where I author and preview content as a normal dynamic Bludit install. When a change is ready to ship, the companion plugin's Static Site Generator mirrors the rendered site into `bl-content/static-build/`. That directory is committed to the repo, and [Coolify](https://coolify.io/) serves it as a static site — so production never runs PHP, but I retain the option to host Bludit dynamically again at any time.

### Keeping local credentials out of the repo

`bl-content/databases/users.php` (admin password hash, salt, auth tokens) and `bl-content/databases/site.php` (real `adminTheme` value) are tracked with sanitized placeholders so the source tree stays runnable end-to-end. Locally the files are masked with:

```bash
git update-index --assume-unchanged bl-content/databases/site.php
git update-index --assume-unchanged bl-content/databases/users.php
```

That lets the Vagrant install carry the real values without ever staging them. The setting is per-clone — after a fresh clone, re-run both commands before logging into admin.

## AI Assistance Disclosure

This project was developed with assistance from AI language models.

## License

MIT — see [LICENSE](LICENSE). Bludit is © Diego Najar and contributors.

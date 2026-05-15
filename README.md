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

`bl-content/databases/users.php` (admin password hash, salt, auth tokens) and `bl-content/databases/site.php` (real `adminTheme`, plus host-specific fields that drift locally) are tracked with sanitized placeholders so the source tree stays runnable end-to-end. Locally the files are masked with:

```bash
git update-index --assume-unchanged bl-content/databases/site.php
git update-index --assume-unchanged bl-content/databases/users.php
```

That lets the Vagrant install carry the real values without ever staging them. The setting is per-clone — after a fresh clone, re-run both commands before logging into admin.

As a safety net, `scripts/git-hooks/pre-commit` rejects any commit that stages either file with a non-sanitized value. It enforces these exact JSON values on staged content:

| File | Field | Required value |
| --- | --- | --- |
| `bl-content/databases/users.php` | `password` | `"!"` |
| `bl-content/databases/site.php` | `adminTheme` | `"!"` |
| `bl-content/databases/site.php` | `url` | `"https:\/\/jereme.dev"` |

If a check fails the commit is aborted with a message naming the offending field. Add new guards by appending another `check_field` line in `scripts/git-hooks/pre-commit`.

#### Enabling the hook (once per clone)

`core.hooksPath` is a per-clone Git setting, so each fresh clone needs to opt in:

```bash
git config core.hooksPath scripts/git-hooks
```

Verify with `git config --get core.hooksPath` — it should print `scripts/git-hooks`.

#### Disabling the hook

If you need to commit a legitimate change to one of the guarded fields (e.g. rotating the sanitized placeholder, moving the production URL), there are three ways to bypass:

- **Skip a single commit** — pass `--no-verify` (or `-n`) on that one commit:
  ```bash
  git commit --no-verify -m "..."
  ```
- **Turn the hook off for this clone** — unset the hooks path, then re-enable later:
  ```bash
  git config --unset core.hooksPath          # disable
  git config core.hooksPath scripts/git-hooks  # re-enable
  ```
- **Edit the hook itself** — comment out the relevant `check_field` line in `scripts/git-hooks/pre-commit`. Tracked, so any change is itself a commit.

After making the change, restore the guard (re-enable the path, or revert the hook edit) so the next commit is protected again.

## AI Assistance Disclosure

Parts of this repository — code, configuration, and documentation — were drafted or edited in collaboration with [Claude Code](https://claude.ai/code), an agentic coding tool that reads files, runs commands, and proposes changes under direction. I review every change before it lands; the AI handles the typing, I'm responsible for the result.

## Disclaimer

This repository is published as-is, primarily as a reference for how I run my own site. It is **not** a supported product. No warranty of any kind is provided, express or implied, including merchantability, fitness for a particular purpose, or non-infringement. Use it at your own risk — I make no commitment to maintain it, accept contributions, or respond to issues, and I am not liable for any damages arising from its use.

## License

MIT — see [LICENSE](LICENSE). Bludit is © Diego Najar and contributors.

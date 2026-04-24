# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] — 2026-04-24

### Added
- `GET /api/index.php/v1/csintegrity/overrides/:id/override-file` — returns the contents, sha256 hash, size, and mtime of the override file on disk for the given override row.
- `GET /api/index.php/v1/csintegrity/overrides/:id/core-file` — same response shape, but for the core source file the override is shadowing.
- `Cybersalt\Component\Csintegrity\Administrator\Helper\PathResolver` — utility that decodes `hash_id` and resolves both override and core paths, with mappings for `layouts/`, `com_*`, `mod_*`, and `plg_<group>_<element>/` first segments.

### Fixed
- `OverridesModel::populateState()` now reads `filter[template]`, `filter[client_id]`, `filter[state]`, and `filter[extension_id]` from the request, so JSON:API filter params on the list endpoint actually filter results. Previously they were silently ignored.

### Documentation
- `CLAUDE.md` and `docs/MVP-v0.1-overrides-api.md` updated with the confirmed `#__template_overrides` schema and the `hash_id`-is-base64-path discovery from the live test on j53.basicjoomla.com.

## [Unreleased]

### Added
- Initial repo scaffold and planning documentation.
- `CLAUDE.md` at repo root — self-contained briefing for Claude Code / VS Code handoff.
- `docs/MVP-v0.1-overrides-api.md` — three-endpoint spec for the first read-only Web Services API exposing `#__template_overrides` data.
- `docs/CLASSIFIER-PATTERNS.md` — v1 classifier ruleset (auto-pass / flag-for-review / alert) derived from a real 11-override inspection.
- `docs/FIELDWORK-2026-04-24.md` — inspection notes from fairviewterracehoa.com including an open XSS finding in `default_links.php` that becomes the MVP's end-to-end test case.
- README links to the new docs.
- Minimal `com_csintegrity` v0.1.0 component — manifest, DI provider, component class, language strings, and the first Web Services endpoint (`GET /api/index.php/v1/csintegrity/overrides`) returning `#__template_overrides` rows via `ApiController` + `JsonApiView` + `ListModel`.
- `com_csintegrity` post-install Bootstrap-card link and admin dashboard view, per the Cybersalt extension checklist.
- `.joomla-brain` submodule pointing at cybersalt/Joomla-Brain.
- `plg_webservices_csintegrity` v0.1.0 — system plugin that registers the component's Web Services routes on `onBeforeApiRoute`. Without this plugin installed **and enabled**, the component's API folder is dead code (Joomla returns `404 Resource not found` for any `/v1/csintegrity/...` URL). Every core API-enabled component has an equivalent `plg_webservices_*`; we missed this on the first pass.

### Notes
- Confirmed Joomla Web Services API token auth requires `X-Joomla-Token: <token>`, not `Authorization: Bearer <token>`. Recorded in `CLAUDE.md`.
- Column list in `OverridesModel` / `JsonapiView` is provisional — sourced from Joomla 4.0's initial migration. Confirm against a live Joomla 5/6 install on first deploy.

# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Initial repo scaffold and planning documentation.
- `CLAUDE.md` at repo root — self-contained briefing for Claude Code / VS Code handoff.
- `docs/MVP-v0.1-overrides-api.md` — three-endpoint spec for the first read-only Web Services API exposing `#__template_overrides` data.
- `docs/CLASSIFIER-PATTERNS.md` — v1 classifier ruleset (auto-pass / flag-for-review / alert) derived from a real 11-override inspection.
- `docs/FIELDWORK-2026-04-24.md` — inspection notes from fairviewterracehoa.com including an open XSS finding in `default_links.php` that becomes the MVP's end-to-end test case.
- README links to the new docs.
- Minimal `com_csintegrity` v0.1.0 component — manifest, DI provider, component class, language strings, and the first Web Services endpoint (`GET /api/index.php/v1/csintegrity/overrides`) returning `#__template_overrides` rows via `ApiController` + `JsonApiView` + `ListModel`.

### Notes
- Confirmed Joomla Web Services API token auth requires `X-Joomla-Token: <token>`, not `Authorization: Bearer <token>`. Recorded in `CLAUDE.md`.
- Column list in `OverridesModel` / `JsonapiView` is provisional — sourced from Joomla 4.0's initial migration. Confirm against a live Joomla 5/6 install on first deploy.

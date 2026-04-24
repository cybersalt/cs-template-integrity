# Changelog

All notable changes to this project will be documented in this file.

## [0.5.0] — 2026-04-24

### Added
- Dashboard "After review" card that closes the workflow loop. Two paths once Claude's review is acted on:
  1. **Recommended** — link to Joomla's own Site Templates list (`com_templates&view=styles&client_id=0`) for per-template, per-row dismissal with an audit trail.
  2. **Bulk** — "Mark all as reviewed" button that opens a Bootstrap modal. The modal includes a checkbox the user must tick ("I confirm I have reviewed every flagged override and I accept responsibility for any unaddressed issues") before the confirm button enables. On submit, every row in `#__template_overrides` is cleared in one shot. Inverse of the existing rebuild button.
- `Cybersalt\Component\Csintegrity\Administrator\Helper\MarkReviewedHelper::clearAllOverrides()` — implementation; returns count of rows cleared.
- `DisplayController::markReviewed()` task with CSRF guard, invoked by the modal's form.

## [0.4.1] — 2026-04-24

### Changed
- Dashboard layout simplified: removed the standalone "Endpoint" and "Smoke test" cards that duplicated information now in the "Use with Claude" prompt. Endpoint URL is preserved as a small reference in the About sidebar. API status is now a one-line alert at the top of the page instead of a full card.

### Fixed
- Dark-mode compatibility. The `<pre><code>` prompt block previously inherited a hardcoded white background, making it unreadable when Joomla's Atum admin template was in dark mode. The block now uses theme-aware Bootstrap CSS variables (`--bs-tertiary-bg`, `--bs-body-color`, `--bs-border-color`) via a small `media/css/dashboard.css` so it adapts to whichever theme is active. Inline copy-button JavaScript also moved out to `media/js/dashboard.js`.

## [0.4.0] — 2026-04-24

### Added
- Dashboard "Use with Claude" card: 3-step instructions for getting a security review of the site's overrides via Claude (claude.ai or Claude Code), plus a copy-to-clipboard prompt that's pre-filled with this site's URL and API base. The user just pastes their Joomla API token and sends.
- First-pass security review of cybersalt.org's 16 flagged overrides via the v0.3 endpoints. One MEDIUM XSS finding (`default_links.php` missing `$this->escape()` on article title), 14 theming-drift items, 1 cosmetic include-path bug. Same XSS pattern found earlier on Rocky Wall's `fairviewterracehoa.com` — the cross-client hypothesis is now supported by two independent Cybersalt-managed sites.

## [0.3.1] — 2026-04-24

### Fixed
- `filter[template]`, `filter[client_id]`, `filter[state]`, and `filter[extension_id]` on the list endpoint now actually filter results. v0.2 fix tried to do it via the model's `populateState()`, which is the wrong layer for Joomla's API — the JSON:API dispatcher reads filters in the controller's `displayList()` and pushes them into `modelState`. Confirmed against core `com_content`'s `ArticlesController` pattern. Tested live on cybersalt.org with 16 real overrides on the `cybersalt` template.

## [0.3.0] — 2026-04-24

### Added
- Dashboard "Rebuild override tracker" button (admin → Components → Cybersalt Template Integrity). Walks every enabled template's `html/` folder and inserts missing `#__template_overrides` rows so a previously-dismissed site can be reset to a usable test corpus. Joomla's own "Dismiss All" deletes rows; this is the inverse.
- `Cybersalt\Component\Csintegrity\Administrator\Helper\RescanHelper` — performs the walk + insert. Skips rows that already exist (matched on template + hash_id + client_id). New rows are written with `action = 'Joomla Update'` and `state = 0` to match what Joomla's own update flow produces.

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

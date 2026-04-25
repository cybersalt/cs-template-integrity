# Changelog

All notable changes to this project will be documented in this file.

## [0.6.4] — 2026-04-24

### Fixed
- "Back" toolbar button on session view (and "Cancel" on the new-session form) now actually navigates. Both were using `ToolbarHelper::cancel()` which submits an `adminForm` — those pages don't have one, so the button silently did nothing. Replaced with `Toolbar::linkButton()` so they're real navigation links.

### Changed
- Scan prompt rewritten to produce a client-facing report. Old prompt had Claude classify by pattern names (`escape-removed`, `logic-diverged-template-theming`) and produced developer-flavored output. New prompt explicitly tells Claude the audience is non-technical, asks for the report in this order — headline answer ("did anything bad happen?"), what-to-do-today action list, what-was-checked one-liner, findings table with 🔴/🟡/⚪ severity icons and plain-language "what it does" + "recommended action" columns, then technical detail at the bottom for developers. Voice guidance inlined: contractions OK, no boilerplate, ball-in-their-court close. Tim should now be able to forward the session report to a site owner directly without a translation pass.

## [0.6.3] — 2026-04-24

### Changed
- "Copy prompt" button renamed to "Copy scan prompt" for symmetry with "Copy fix prompt" — clearer pairing of the two prompts.

## [0.6.2] — 2026-04-24

### Fixed
- "Copy prompt" button now matches "Copy fix prompt" — both are `btn btn-primary` (blue with white text). Tim preferred the blue style; the previous `btn-info` was rendering as a near-black button with white text in Atum's dark mode.
- "View all sessions" button switched from `btn-outline-info` to `btn btn-secondary`. The outline variant rendered as a black box with thin blue edge and blue text in dark mode — unreadable. Recorded as a global feedback rule: avoid `btn-outline-*` classes in any Joomla admin UI; use solid `btn-*` variants instead.

## [0.6.1] — 2026-04-24

### Fixed
- Defensive pass on the v0.6.0 install that Tim hit "Unexpected token '<'... is not valid JSON" on. Removed three plausible variables: (1) submenu strings now live in `com_csintegrity.sys.ini` so Joomla's installer/menu rendering can resolve them; (2) `script.php` no longer calls `ActionLogHelper::log` during postflight — autoloading another component class from inside install was the most plausible failure point; (3) em-dash characters in SQL comments replaced with plain `--` to avoid any non-ASCII byte parsing edge case in Joomla's SQL splitter.

## [0.6.0] — 2026-04-24

### Added
- **Session log.** New table `#__csintegrity_sessions` and an admin "Sessions" view (Components → CS Template Integrity → Sessions submenu). Reports from Claude are stored as sessions named `YYYY-MM-DD-HHMM` by default. Two ways to add a session: paste-in via a form (claude.ai users) or POST to `/api/index.php/v1/csintegrity/sessions` (Claude Code / agentic users — the dashboard prompt template now tells Claude to do this automatically). Each session can be viewed individually with its full report markdown and the action-log entries that ran while it was active. Delete works via the standard admin checkbox toolbar.
- **Action log.** New table `#__csintegrity_actions` and an admin "Action log" view. Every notable event is recorded automatically: install, update, rescan, mark-reviewed, session created/deleted, backup created. `Cybersalt\Component\Csintegrity\Administrator\Helper\ActionLogHelper::log($action, $details, $sessionId)` is the single entry point; calls are wired into `RescanHelper`, `MarkReviewedHelper`, `SessionsHelper`, `BackupsHelper`, and `script.php`'s postflight. Logging failures are swallowed — the audit log can never crash the parent operation.
- **File backups.** New table `#__csintegrity_backups` and admin "File backups" view. Claude POSTs original file contents to `/api/index.php/v1/csintegrity/backups` before proposing a fix; the original is stored as base64 in the DB along with sha256 and size. Each backup is downloadable from the admin list. Restore-from-backup is intentionally deferred — destructive enough to deserve its own design pass.
- **Apply Fixes prompt card** on the dashboard. Second copy-paste prompt that asks Claude to back up each affected file (POST to `/backups`) before proposing diffs. Read-only by default; Claude proposes, the admin applies. Pairs with the existing Use-with-Claude card.
- **Session log preview card** on the dashboard. Lists the five most recent sessions with quick links, plus "New session" and "View all sessions" buttons.
- Component manifest grew `<install>`, `<uninstall>`, and `<update>` SQL blocks pointing at the new schema files in `admin/sql/`. Submenu entries for Dashboard / Sessions / Action log / File backups added under the Components menu.

### Changed
- Use-with-Claude prompt extended with a step 6 telling Claude to POST its report back to `/v1/csintegrity/sessions` so it's preserved in the audit trail.

## [0.5.2] — 2026-04-24

### Fixed
- All dashboard buttons are now the same default size (Bootstrap default). Previously "Copy prompt", "Open Site Templates", and "Mark all as reviewed" were `btn-sm` while "Reset all overrides for review" was the default — sizes were inconsistent.
- "Copy prompt" was `btn-outline-info` which renders as blue text on a transparent background; in Atum's dark mode that gave blue-on-near-black with poor contrast. Switched to `btn-info` (solid sky-blue with white text), which is readable in both light and dark mode. The transient "Copied!" success state still flips to `btn-success` then back.
- "Open Site Templates" similarly switched from `btn-outline-secondary` to solid `btn-secondary` for the same readability reason.

## [0.5.1] — 2026-04-24

### Changed
- Components-menu label is now "CS Template Integrity" (was "Cybersalt Template Integrity"). The full name still appears on the dashboard page title.
- "Open Site Templates" link in the After-review card now points at `view=templates&client_id=0` rather than `view=styles&client_id=0` — the templates list is where the per-template "Changes found" override review actually lives.
- Repositioned the rescan card. Previously framed as a testing utility; reframed as the user-facing remedy for "I (or someone before me) bulk-dismissed overrides without actually checking them." New title "Reset overrides for review", new button "Reset all overrides for review", description rewritten accordingly.

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

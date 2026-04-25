# Changelog

All notable changes to this project will be documented in this file.

## [0.8.3] — 2026-04-24

### Changed
- `BackupsHelper::createFromContents` now deduplicates on `(file_path, sha256)`. If a backup with that exact path and content already exists, the helper returns the existing id instead of inserting an identical row. Eliminates the noise Tim hit on cybersalt.org where one apply-fix run produced 5 backups of which 4 were byte-identical pre-action snapshots. Audit-log calls in callers (e.g. `fix_applied`, `backup_restored`) still reference the now-shared backup id, which is semantically correct — the snapshot of that state.

### Added
- Backups list grew a checkbox column and a standard "Delete" toolbar action so existing duplicates (or any stale backup) can be pruned. Backed by `BackupsHelper::delete(int $id)` and `BackupsController::delete()`.

## [0.8.2] — 2026-04-24

### Fixed
- POST endpoints with `:id` URL captures (`/overrides/{id}/apply-fix`, `/overrides/{id}/dismiss`, `/backups/{id}/restore`) now resolve the id reliably even when Joomla's API dispatcher doesn't populate `$this->input` from a POST URL capture (the dispatcher does this for GET routes but not POST in practice). New `resolveIdFromRequest()` helper checks three sources in order: the method argument, `$this->input->getInt('id')`, and a regex against `REQUEST_URI`. Same fallback inlined into `BackupsController::restore()`. Existing GET endpoints (`overrideFile` / `coreFile`) also routed through the helper for consistency.

## [0.8.1] — 2026-04-24

### Fixed
- v0.8.0's three new POST endpoints (`apply-fix`, `dismiss`, `backups/.../restore`) were rejecting requests with "A numeric override id is required" even when the id was clearly in the URL. Joomla's API dispatcher passes `:id` URL captures as a method parameter for POST endpoints, not via `$this->input`. Each method now accepts `$id = null` and falls back to `$this->input->getInt('id', 0)` as a safety net.

## [0.8.0] — 2026-04-24

### Added
- **Apply Fixes is now end-to-end automatable.** Three new write-side API endpoints round out the workflow so Claude can complete the loop without the user clicking through the admin between each step:
  - `POST /v1/csintegrity/overrides/{id}/apply-fix` — body `{ contents, session_id? }`. Auto-backs up the live file's current state, writes the patched contents to disk, returns the pre-fix backup id so the operation is reversible. Path-traversal guard refuses anything outside `JPATH_ROOT`. Backed by `OverridesHelper::applyFix()`.
  - `POST /v1/csintegrity/overrides/{id}/dismiss` (and `DELETE` on the same URL) — clears one override-tracker row. Backed by `OverridesHelper::dismissOne()`.
  - `POST /v1/csintegrity/overrides/dismiss-all` — clears every flagged row. Wraps the existing `MarkReviewedHelper::clearAllOverrides()`.
  - `POST /v1/csintegrity/backups/{id}/restore` — same effect as the admin Restore button, exposed via API. Wraps `BackupsHelper::restore()`.
- New action constants `ACTION_FIX_APPLIED` and `ACTION_OVERRIDE_DISMISSED` so the action log distinguishes write events from passive ones.
- New `Cybersalt\Component\Csintegrity\Administrator\Helper\OverridesHelper` for write-side operations (the existing `OverridesModel` is read-only).

### Changed
- Apply Fixes prompt rewritten for the new endpoints. Workflow is now: classify finding → fetch current contents → build patch → POST `/apply-fix` (one call: auto-backup + write) → POST `/dismiss` for that finding's tracker row. After all fixes are applied, an optional `/dismiss-all` clears bulk non-security warnings the user explicitly told Claude to mark checked. Reverse-a-fix instructions still point at the per-backup admin page.

### Notes
- These endpoints are gated by Joomla's standard API token auth — anyone with a valid `X-Joomla-Token` and `core.manage` on `com_csintegrity` can write. Auto-backups + path guards + action-log entries are the safety nets. The admin's existing modals (with confirmation checkboxes) are unchanged; this just makes the same operations available headlessly.

## [0.7.2] — 2026-04-24

### Fixed
- Restore-backup confirmation modal was too narrow — long file paths in the prompt body got crammed against the modal's left edge. Added `modal-lg` to the dialog (~800px on medium+ screens) so paths have room.
- Post-restore success message was wrapped in `<code>` tags. The default code styling inside an `alert-success` doesn't flip readably between light and dark mode (dark text on light-green vs light text on dark-green-ish — neither version is great). Dropped the wrap; plain text in the success bar is readable in both modes.

## [0.7.1] — 2026-04-24

### Fixed
- "Restore now…" button on the backup detail view did nothing on click. The button uses Bootstrap 5's `data-bs-toggle="modal"` to open the confirmation modal, but Joomla 5+ only loads the `bootstrap.modal` script asset when explicitly requested, and the Backup view wasn't doing that. Added `useScript('bootstrap.modal')` to both the Backup and Dashboard HtmlViews so the modal opens reliably on either page (the Mark-all-reviewed modal on the dashboard had been working incidentally because Atum was loading the asset for unrelated reasons; making it explicit so we don't depend on that).

## [0.7.0] — 2026-04-24

### Added
- **Backup detail view** at `index.php?option=com_csintegrity&view=backup&id=N`. Shows the backup's metadata (relative path, absolute path, size, sha256, created-at, linked review session, whether the live file at that path currently exists), the full backed-up contents in a resizable code block, and three action cards: restore-via-button, restore-manually-via-FTP, and where-this-backup-lives. Each row in the Backups list now links to this detail page (file path is clickable, plus an explicit "View" button next to "Download").
- **Restore action.** New `BackupsHelper::restore(int $id)` writes the backup contents back to the original file path. Before overwriting, it auto-creates a fresh backup of the live file's current state — so the restore is itself reversible. Path-traversal guard via `realpath()` refuses to write outside `JPATH_ROOT`. Restore is gated by a Bootstrap modal with a confirmation checkbox (same UX as Mark-all-reviewed). Logged to the action log as `backup_restored` with both the source backup id and the auto-created pre-restore backup id, linked to the original review session if any.
- **Manual-restore instructions** card on the backup detail page. For users who prefer FTP / their editor: download the backup, open their file client, replace the file at the destination path shown.
- **Storage explanation** card. Tells the user up-front: backups are in the `#__csintegrity_backups` table as base64-encoded contents, not files on disk; a database backup also captures them; uninstalling the component drops the table.

### Changed
- `dashboard.js` factored out the gated-checkbox-modal pattern into `wireGatedConfirmModal(modalId, checkboxId, btnId)` so the existing Mark-all-reviewed modal and the new Restore modal share one implementation.

## [0.6.11] — 2026-04-24

### Changed
- Apply Fixes prompt rewritten to fix three weaknesses found while testing it: (1) it now explicitly tells Claude to fetch original contents via `GET /overrides/{id}/override-file` before staging a backup — the previous wording said "save a backup of the original file" but didn't say *how* to obtain the original; (2) introduced a "Review session" placeholder that ties backups to the prior scan's session id rather than a new one — now the audit trail can answer "which scan surfaced the issue this backup is for?"; (3) explicit "classify first" step that distinguishes code-fix findings (XSS, missing escape, etc. → diff) from config/licensing findings (Web357-style overrides → ask, don't propose a code change). Final summary step asks the user to confirm before further action and reminds them to dismiss the relevant override-tracker warnings after applying the patched files.

## [0.6.10] — 2026-04-24

### Changed
- "New session" form intro rewritten to explain its actual purpose: it's the manual paste-in for browser-Claude users. The previous text was vague ("Save the report Claude produced…"). Now reads: "Manual paste-in for browser-Claude users. If you ran the scan prompt at claude.ai, copy Claude's reply and paste it here. If you used Claude Code in a terminal, you don't need this form — Claude posts the report back automatically."

## [0.6.9] — 2026-04-24

### Changed
- "Back" button on the session viewer is now context-aware. If you arrived from the Action log, the button reads "Back to action log" and returns there. From Backups → "Back to backups". From the dashboard's session-log card → "Back to dashboard". From the Sessions list (or any other entry point) → "Back to sessions" (the previous default). Implemented via a `&from=<view>` URL param appended to every session link in the admin; missing or unknown values fall through to the sessions list.

## [0.6.8] — 2026-04-24

### Fixed
- Resize handle on the session report block now actually works. v0.6.6 set `min-height: 600px` plus `resize: vertical`, but most browsers only render the resize handle reliably when the element has a concrete `height` and non-visible `overflow`. Switched to `height: 600px` + `overflow: auto` (with `min-height: 200px` so a user can shrink it but not collapse it).

### Changed
- Auto-generated session names now include seconds. Format went from `YYYY-MM-DD-HHMM` to `YYYY-MM-DD-HHMMSS` so two sessions created in the same minute (concurrent API POSTs, rapid manual saves) don't collide on names — and downloaded report filenames stay distinct. The Use-with-Claude prompt template and the new-session form's name-help text both updated to reflect the new format.

## [0.6.7] — 2026-04-24

### Added
- **Fullscreen toggle** on the session report viewer. Button next to the "Report" heading uses the HTML5 Fullscreen API to expand the report `<pre>` to the full viewport. Click again to exit. Auto-hides if the browser doesn't support `document.fullscreenEnabled`. CSS keeps the theme-aware background and adds generous padding when in fullscreen so it isn't a wall of monochrome text.
- **Download report button** on the session viewer. Streams the session's stored markdown as `csintegrity-<session-name>.md` (sanitized filename, fallback to `session-<id>.md` if the name has no safe characters). Implemented as a `session.download` task on `SessionController` — no extra view layer, just headers + echo.

## [0.6.6] — 2026-04-24

### Changed
- "Back to sessions" button switched from `btn-secondary` (grey) to `btn-info` (sky blue with white text). It's a navigation action; it should look like one.
- Session report block is now resizable. Default minimum height of 600px (was content-fitted, often too short for long reports), plus a vertical resize handle in the bottom-right corner so users can drag it taller as needed.
- Sessions and Action log views grew an intro paragraph that explains what each view actually shows. Sessions = read-only Claude reports. Action log = the audit trail of changes (rescans, mark-reviewed, session creation, backup saves). Tim found the two confusing on first look — the wording now distinguishes content from changes.

## [0.6.5] — 2026-04-24

### Fixed
- **Back button on the session viewer actually works now.** v0.6.4 used `Toolbar::linkButton()`, which still rendered the back arrow as a toolbar button that Joomla's submit-handler tried to wire up — silent no-op. Replaced with a plain in-page `<a class="btn btn-secondary">` link at the top of the session and new-session-form templates. Guaranteed to navigate.
- **Session report wraps inside the viewer.** The `.csintegrity-codeblock` styling (with `white-space: pre-wrap; word-break: break-word`) only applied on the dashboard because `dashboard.css` was only loaded by the Dashboard `HtmlView`. Now loaded by the Session and Sessionform views too. Also relaxed the CSS selector — base styling now applies anywhere `.csintegrity-codeblock` is used, with the 320px height cap scoped to dashboard cards only. The session-report block has its own `.csintegrity-report` class that lets the page scroll instead of the block.

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

# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] — 2026-04-29

### Improvements

- **Explicit Joomla 5 + Joomla 6 compatibility** declared in every manifest. All three (`com_cstemplateintegrity`, `plg_webservices_cstemplateintegrity`, `pkg_cstemplateintegrity`) now carry `<targetplatform name="joomla" version="5\.[0-9]+|6\.[0-9]+" />`. The code itself was already 5/6-native — services/provider.php DI containers, `ApiController` + `JsonapiView`, namespaced `Factory`/`DatabaseInterface`, zero `JFactory`/`JHtml`/`JLog`/`JLoader` legacy calls — but the manifests didn't say so out loud, which can show "version not compatible" warnings on Joomla 6 even when everything works.
- **Joomla update server wired up.** Package manifest now declares `<updateservers>` pointing at `updates.xml` on the main branch and `<changelogurl>` pointing at `CHANGELOG.html`. Once a site has 1.0.1 installed, future releases will surface in **System → Manage → Update** automatically — no manual zip download from GitHub.
- New `updates.xml` and `CHANGELOG.html` at the repo root, kept in sync with `CHANGELOG.md`. SHA256 checksum included on every release so Joomla doesn't show the "no checksum" warning.

### Why a 1.0.1 so soon after 1.0.0

The targetplatform + update-server metadata was the missing piece between "v1.0 ships" and "site admins can keep up to date in one click." 1.0.0 was the milestone; 1.0.1 is what makes 1.0+ deployable at scale.

## [1.0.0] — 2026-04-25

First stable release. The core flow — *site owner pastes one prompt → Claude lists every flagged override on the site, classifies each one, writes a plain-English report, asks which to fix, applies the fixes the owner confirms, marks the rest as checked* — has been live-tested end-to-end on cybersalt.org and on a real client site (fairviewterracehoa.com) running Joomla 6.1 on a third-party template.

### What's in 1.0

- **Component (`com_cstemplateintegrity`)** — admin dashboard with copy-paste scan + cross-session fix prompts, sessions log, action log, file backups view with restore, all gated by `cstemplateintegrity.view` / `cstemplateintegrity.write` ACL actions defined in `admin/access.xml`.
- **Web Services plugin (`plg_webservices_cstemplateintegrity`)** — registers the `/api/index.php/v1/cstemplateintegrity/...` routes; auto-enabled by the package installer.
- **API endpoints** — `GET /overrides`, `GET /overrides/{id}/override-file`, `GET /overrides/{id}/core-file`, `GET /sessions/{id}`, `POST /sessions`, `POST /overrides/{id}/apply-fix`, `POST /overrides/{id}/dismiss`, `POST /overrides/dismiss-all`, `POST /backups/{id}/restore`.
- **Auto-backup** before every file write — snapshot of current contents, sha256, full content stored in `#__cstemplateintegrity_backups`. Every patch reversible from the admin or via the API.
- **Hardened against the v0.9 security review** — separator-anchored path containment (no prefix-collision bypass), PHP-extension write whitelist (only `templates/<tpl>/html/` — no other path under `JPATH_ROOT` accepts `.php` writes), `opcache_invalidate()` after every PHP write, GET-form CSRF tokens on download links, CRLF/header-injection sanitization on `Content-Disposition`, `htmlspecialchars()` on every `Text::_()` rendered into the installer frame, no free-form `file_path` accepted in any request body — backup paths are server-side derived from `#__template_overrides` rows.
- **Same-chat conversational flow** — the scan prompt ends by asking the user which findings to fix and waits, then runs fetch → patch → apply-fix → dismiss inline. The cross-session fix prompt is opt-in for the rare case of starting a fresh chat to continue an earlier review.
- **Claude.ai sandbox guidance** built into the dashboard prompt instructions — *"Host not in allowlist"* error gets a one-paragraph fix path (Settings → Capabilities → Code execution → Network access; then close the current chat, start a new one to pick up the updated allowlist).
- **Plain-English descriptions** on backup rows so non-technical viewers can tell at a glance what each stored file actually is.
- **Unknown-finding catch-all** — if a finding doesn't fit "code change" or "configuration question" (needs a database tweak, plugin reinstall, contact the third-party developer), Claude stops and explains in plain English instead of attempting a partial fix.

### Cleanup before 1.0

- Removed the `v0.1 exposes the native …` early-MVP wording from XML descriptions and language strings.
- Dashboard's version display now reads from the on-disk component manifest (`cstemplateintegrity.xml`) at render time instead of being hardcoded — won't go stale on the next bump.
- README rewritten for a release-state audience (what's in 1.0, how to install, where the API endpoints live, what the security model is).

### Migration from 0.x

There was no in-place upgrade path between 0.9 (`csintegrity`) and 0.10 (`cstemplateintegrity`) due to the rename, and 0.10/0.11 were quick iterations on top of 0.10.0's fresh-install schema. The 0.11 → 1.0 step IS in-place: install `pkg_cstemplateintegrity_v1.0.0_*.zip` over an existing 0.11.x install and Joomla will upgrade in place.

If you're still on 0.9 (`csintegrity`): uninstall the old package first (drops the `#__csintegrity_*` tables), then install 1.0.

## [0.11.2] — 2026-04-25

### Added

- **Plain-English description column on the backups list and detail view.** Each row now shows a one-liner under the path explaining what the file actually is — e.g. *"Featured articles — 'more articles' links list"* under `templates/.../html/com_content/featured/default_links.php`, or *"Article previous / next navigation links"* under `templates/.../html/plg_content_pagenavigation/default.php`. Surfaced when Tim test-restored on Rocky's site and three rows looked superficially identical (two were the same logical file in two parallel template directories with different casing — the descriptions plus the path now make that obvious at a glance, instead of having to scan three near-identical paths character-by-character).
- New `BackupDescriber` helper does the mapping. Resolves in three tiers: exact-match whitelist of common Joomla core layouts (~30 entries), pattern-based derivation from the path's first segment after `/html/` (handles any `com_*`, `mod_*`, `plg_*`, `layouts/*` we don't have an explicit row for), and a generic fallback. No schema change, no API change — derived purely from the existing `file_path` column, so retroactive on every backup row.

## [0.11.1] — 2026-04-25

### Fixed

- **Scan prompt now lists the write endpoints in the upfront *Endpoints* block, not only inside the workflow steps.** Live test on fairviewterracehoa.com (running pre-v0.11) hit the old shape: when asked to apply fixes inline, Claude scanned the *Endpoints* list, saw three GETs and concluded "the cstemplateintegrity REST API is read-only," then tried PATCH/PUT and made-up paths (`/state`, `/mark`, `/check`, `/override-file` write) before giving up and producing patched files for the user to SFTP up by hand. v0.11.0's step 6 already documents `apply-fix` / `dismiss` / `dismiss-all` further down the prompt, but if the AI never read past the endpoints block it never reached that step. The block now lists all read AND write endpoints with explicit "THIS IS HOW YOU APPLY A PATCH" / "THIS IS HOW YOU 'MARK AS CHECKED'" annotations, and the section heading itself says: *"do NOT fall back to producing a file for SFTP upload"*. Same treatment applied to the cross-session fix prompt.
- Clarification added inline that *dismiss* is the canonical "mark as checked" action — there is no separate state flag, and dismissing the override row IS the reviewed-and-accepted decision. The fairview test had Claude trying to PATCH `state` on individual rows, which doesn't exist.

## [0.11.0] — 2026-04-25

### Changed

- **Reworked the two-prompt UX into a one-prompt-plus-conversation flow.** The old model assumed users would always paste two prompts (one to scan, one to fix), but in the same Claude chat the second prompt was redundant — Claude already had the session id, the diffs, the API base, and the report in context. v0.11.0 collapses the default flow into the scan prompt and reframes the fix prompt as the cross-session helper.
  - **Scan prompt** now ends with an explicit hand-off step (#6): after POSTing the report, Claude asks the user which findings to fix and waits, then for each confirmed finding runs the same fetch → patch → apply-fix → dismiss workflow that used to live in the fix prompt. **Critically, it now also handles the unknown-finding case** — if a finding doesn't fit "code change" or "configuration question" (e.g. it needs a database tweak, a plugin reinstall, or contacting a third-party developer), Claude stops and explains in plain English instead of applying a partial fix.
  - **Fix prompt** is now scoped to the cross-session case only: starting a fresh chat to continue a review (closed the original chat overnight, handing it to a teammate, switched from claude.ai to Claude Code mid-flow). It now begins by GET-ing the prior session report (`/sessions/{id}`) so a new Claude can rebuild the context, then asks the user which findings to fix. Same unknown-finding catch-all applies.
- **Dashboard reworked to make the two paths obvious.** Each card now leads with a Bootstrap alert banner stating who it's for:
  - Scan card: green *"Start here. This is the only card you need for a normal review."*
  - Fix card: yellow *"Most users do NOT need this card. Use only if you're starting a fresh chat to apply fixes from a previous review."*
  - Card titles renamed: *"Use with Claude"* → *"Run a review with Claude"*; *"Apply fixes"* → *"Continue a previous review in a new chat"*.
- New step 4 in the scan card's instructions: *"Tell Claude what to fix — in the same chat"* with a concrete example reply, so non-technical users see the conversational hand-off they'd otherwise have to infer.

### Why

A real client test on fairviewterracehoa.com triggered the question: the existing fix prompt was tightly coupled to the three classifier categories (ALERT/REVIEW/INFO), and a finding that didn't fit either of the prompt's two action buckets ("code change" or "configuration question") would have left Claude in an awkward spot. The conversational hand-off side-steps that — Claude can ask the user before doing anything ambiguous — and the catch-all branch makes it explicit even in the cross-session prompt.

## [0.10.2] — 2026-04-25

### Changed

- **Dashboard step 2 now also tells claude.ai users to start a new chat after adding the domain to the allowlist.** The running chat caches the allowlist it had at start; only a fresh chat picks up the newly-added domain. Without this hint, users add the domain, resend the prompt, get the same "Host not in allowlist" error, and assume the allowlist setting didn't take. Note covers both the Claude desktop app and the web version.

## [0.10.1] — 2026-04-24

### Fixed

- **Uninstall flashed the "installed, click here to open the dashboard" card.** Both `com_cstemplateintegrity/script.php` and `pkg_cstemplateintegrity/script.php` had postflight handlers that ran the install-message renderer on every postflight call — including uninstall. Joomla calls `postflight($type, …)` on install, update, **and uninstall**, so without an explicit `$type` filter the uninstall path rendered an "installed" success card with a link to a route that no longer existed. Both scripts now early-return from `postflight()` for any `$type` that isn't `install` / `update` / `discover_install`. The package script also gates its `enableWebservicesPlugin()` call on the same set, so it doesn't try to enable a plugin that's about to be removed.
- **Apply HTML-escape to `Text::_()` output in pkg_/script.php.** v0.9.0 fixed this in `com_/script.php` but missed the matching code in the package-level installer; now both wrap every `Text::_()` in `htmlspecialchars()` before echoing into Joomla's installer frame.

### Changed

- **Dashboard step 2 now documents the claude.ai code-execution sandbox allowlist.** Hitting the prompt for the first time on a fresh claude.ai account fails with *"Host not in allowlist"* — the sandbox blocks every domain not on its default list. The Step 2 body now points users at *Settings → Capabilities → Code execution → Network access* in claude.ai and notes that Claude Code in a terminal has no such restriction. (Cosmetic clarification only — no code change beyond the language string.)

## [0.10.0] — 2026-04-24

### Changed

- **Renamed everywhere from `csintegrity` to `cstemplateintegrity`.** "CS Template Integrity" matches Cybersalt's existing extension family naming (`cs-disk-usage` → `com_csdiskusage`, etc.) and also locks the scope: this tool stays focused on template-override integrity. MySites Guru already covers core-file integrity, extension-file integrity, and stowaway-file detection on Joomla, so we don't intend to expand into those.
- Component slug `com_csintegrity` → `com_cstemplateintegrity`. Plugin `plg_webservices_csintegrity` → `plg_webservices_cstemplateintegrity`. Package `pkg_csintegrity` → `pkg_cstemplateintegrity`. Library and system-plugin scaffolds renamed to match.
- Namespace `Cybersalt\Component\Csintegrity\…` → `Cybersalt\Component\Cstemplateintegrity\…`. Plugin namespace `Cybersalt\Plugin\WebServices\Csintegrity\…` → `Cybersalt\Plugin\WebServices\Cstemplateintegrity\…`.
- Database tables `#__csintegrity_sessions/_actions/_backups` → `#__cstemplateintegrity_sessions/_actions/_backups`.
- API routes `/v1/csintegrity/…` → `/v1/cstemplateintegrity/…`. ACL actions `csintegrity.view/.write` → `cstemplateintegrity.view/.write`. Language keys `COM_CSINTEGRITY_*` → `COM_CSTEMPLATEINTEGRITY_*`. Component option `option=com_csintegrity` → `option=com_cstemplateintegrity`. Media destination folder likewise.
- The old v0.6.0 update SQL (which targeted `#__csintegrity_*` tables) has been dropped. v0.10.0 is the new floor; there is no in-place upgrade from v0.9.x — uninstall v0.9 first (which drops the old tables) then install v0.10.

### Migration

There is no automatic upgrade path from v0.9.x. To move a site from v0.9 to v0.10:

1. Note any sessions/backups you want to preserve (download via the admin UI). On a fresh dev site, skip this.
2. Uninstall **CS Template Integrity** from the Joomla extension manager. This drops the `#__csintegrity_*` tables and the `csintegrity` web-services plugin.
3. Install `pkg_cstemplateintegrity_v0.10.0_*.zip`. The new `#__cstemplateintegrity_*` tables are created at install time.
4. Enable the **plg_webservices_cstemplateintegrity** plugin in System → Plugins (third-party plugins install disabled by default).
5. In **System → Permissions** for **CS Template Integrity**, grant *View* and *Modify* to whatever group should be able to use the API/admin (Super Users always pass).

## [0.9.0] — 2026-04-24

### Security

This release closes the high- and medium-severity findings from the v0.8.5 security review and adds defense-in-depth around any code path that writes files under `JPATH_ROOT`. **Upgrade is strongly recommended for any installation of v0.8.x; v0.8.x exposed write-side API endpoints with no permission gate, which on a production site is an authenticated arbitrary-write-under-webroot primitive.**

- **ACL gate on every endpoint.** New `admin/access.xml` defines two component-scoped actions, `csintegrity.view` and `csintegrity.write`, alongside the standard `core.admin` / `core.manage` / `core.options`. New `PermissionHelper` is consulted at the top of every API controller method and every admin write-side controller method. Read endpoints (overrides list, override-file, core-file, sessions list, sessions item, backups list, backups item) require `csintegrity.view` or `core.manage`. Write endpoints (`apply-fix`, `dismiss`, `dismiss-all`, `backups POST`, `backups/:id/restore`, `sessions POST`, the admin restore/delete/save/rescan/markReviewed actions) require `csintegrity.write` or `core.manage`. Until an admin grants those actions to a group, only Super Users can use the extension. Previously, any valid Joomla API token reached every endpoint.
- **Backups POST no longer accepts a free-form path or contents.** The new contract requires `override_id`; the helper resolves the file path server-side from `#__template_overrides` and snapshots the live file's bytes from disk. The previous `file_path` + `contents` body would, when paired with `restore`, write arbitrary bytes to any path under `JPATH_ROOT` — a clean RCE primitive once an attacker had a write token. The new shape rules out that bridge entirely.
- **Path-traversal guard rewritten.** The old `strpos($parentReal, $rootReal) !== 0` check passed when `JPATH_ROOT` had a sibling whose real path began with the same prefix — e.g. `/var/www/joomla` and `/var/www/joomla-bak`. New `PathSafetyHelper::assertWithinRoot()` uses `str_starts_with` with a trailing `DIRECTORY_SEPARATOR` so prefix collisions cannot bypass it. Both `OverridesHelper::applyFix()` and `BackupsHelper::restore()` now route through it, plus the new backup-create flow.
- **PHP-write whitelist.** `PathSafetyHelper::assertPhpWriteAllowed()` refuses any `.php` / `.phtml` / `.phar` / `.pht` write whose path is not under `templates/<tpl>/html/` (site) or `administrator/templates/<tpl>/html/` (admin). Belt-and-braces against a hostile `#__template_overrides` row whose `hash_id` decodes to a path that escapes `/html/`. Non-PHP extensions are unrestricted (still subject to within-root).
- **opcache invalidation.** Both write paths call `opcache_invalidate()` (when available) on the target after `file_put_contents()`, so a fix that changes a file already cached by OPcache takes effect on the next request rather than silently running stale bytes.
- **CRLF / response-splitting fix on backup download.** Admin `backups.download` previously reflected `basename($row->file_path)` into the `Content-Disposition` header after only stripping double-quotes. A backup row whose path contained CR/LF could inject arbitrary HTTP headers. The filename now passes through `preg_replace('/[^A-Za-z0-9._-]/', '-', ...)` before being reflected. (`session.download` already had this guard.)
- **CSRF on download endpoints.** Admin `backups.download` and `session.download` now require a session form-token query parameter via `checkToken('get')`, plus an explicit `PermissionHelper::requireView()`. Listing templates were updated to append `Session::getFormToken()`. Without this, a logged-in admin could be tricked by a crafted cross-site link into exfiltrating a backup or session report.
- **HTML-escape installer post-message.** `script.php::showPostInstallMessage()` now wraps every `Text::_()` call in `htmlspecialchars()`. Today's strings are static, but installer output should not template translation strings as raw HTML — a future translator's mistake would otherwise become an issue at install time.
- The README and the dashboard help string previously claimed `core.manage` was enforced; that claim is now actually true.

## [0.8.5] — 2026-04-24

### Fixed
- v0.8.4 didn't actually highlight backup contents when Tim opened a backup. Two reasons: (1) Joomla's `HTMLHelper::script()` silently drops options it doesn't recognize, including `defer`, so `defer => true` in the options array ended up nowhere — moved to the fourth parameter ($attribs) so it lands on the script tag as a real HTML attribute. (2) Even with defer, script timing can be funny across browsers and proxies; `wireSyntaxHighlight()` now polls for `window.hljs` for up to 5 seconds before giving up. Either fix on its own should be enough; both together cover the long tail of edge cases. If `hljs` never loads, a `console.warn` records the failure so it's diagnosable from the browser dev tools.

## [0.8.4] — 2026-04-24

### Added
- **Syntax highlighting** on the Backup detail view's contents block. Bundled `highlight.js` v11.9.0 (~120 KB minified) plus a small `highlight-theme.css` that picks token colors from Bootstrap CSS variables (`--bs-primary`, `--bs-success`, `--bs-warning`, etc.) so it adapts to Atum's light/dark mode without needing two separate stylesheets. Language is auto-detected from the file extension — `.php`, `.html`, `.css`, `.js`, `.json`, `.yaml`, `.md`, `.sh`, `.sql`, `.ini`, plus a few aliases. Falls back to plaintext for anything unknown. If the bundled JS fails to load for any reason, the codeblock still renders fine — just unhighlighted.

### Fixed
- **Resize handle on the session report and backup contents only allowed shrinking, not growing.** CSS specificity bug — `.csintegrity-dashboard .csintegrity-codeblock { max-height: 320px }` (the dashboard prompt-card scope) was beating `.csintegrity-report { max-height: none }`, so the 320 px cap leaked onto pages it shouldn't have. Fixed via `:not()` exclusion on the dashboard rule plus `!important` on the resizable-block rule for belt-and-braces. The same `.csintegrity-backup-contents` class added on the Backup view now also resizes properly.

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

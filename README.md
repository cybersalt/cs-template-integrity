# Cybersalt Template Integrity

A Joomla 5/6 integrity monitor that pairs with Claude to review every flagged template override on your site, and apply the patches you confirm — auto-backed-up, reversible, no SFTP needed.

## 📥 Download

**[➡ Download the latest release (always points at the newest version)](https://github.com/cybersalt/cs-template-integrity/releases/latest)**

Or grab a specific version directly:

- Always-latest zip: <https://github.com/cybersalt/cs-template-integrity/releases/latest>
- Browse all versions: <https://github.com/cybersalt/cs-template-integrity/releases>

Once installed, future versions show up under **System → Manage → Update** in your Joomla admin — no need to re-download.

---

## What it does

Joomla's native "X Changes found" badge in Template Manager tells you that an override file's core ancestor has changed after a core update. It doesn't tell you whether anything bad happened — and after every Joomla point release, every template with overrides lights up at once. Signal-to-noise is zero when a site owner actually needs to know whether their site is compromised.

This extension is template-literate. It surfaces the override tracker via a Joomla Web Services API, then a single Claude prompt does the work:

1. **List** every flagged override on the site.
2. **Fetch** both sides of each one — the override file and the stock core file it's shadowing.
3. **Classify** each pair as **alert** (security regression — missing escape, removed CSRF token, etc.), **review** (legitimate template customisation that's drifted), or **info** (cosmetic — copyright year, whitespace).
4. **Report** in plain English, audience-appropriate for the site owner, not the developer.
5. **Apply patches** in place for findings the owner confirms — auto-backed-up, fully reversible from the admin.
6. **Mark the rest as checked** so Joomla's "Changes found" badges go away for everything that's been reviewed.

The whole loop runs in a single Claude conversation. Site owners don't see code, don't FTP anything, don't paste a second prompt unless they're picking up a previous review in a fresh chat.

---

## What ships in v1.0

| Extension | Role |
|---|---|
| `com_cstemplateintegrity` | Admin component — dashboard with copy-paste prompts, sessions log, action log, file backups (with restore), Web Services API endpoints |
| `plg_webservices_cstemplateintegrity` | Registers the `/api/index.php/v1/cstemplateintegrity/...` routes for the component. Auto-enabled by the package installer. |

Shipped together as `pkg_cstemplateintegrity`.

### API endpoints

Auth on every request: `X-Joomla-Token: <token>` + `Accept: application/vnd.api+json`. (NOT `Authorization: Bearer` — Joomla rejects that.)

**Read:**
- `GET /v1/cstemplateintegrity/overrides` — list flagged overrides
- `GET /v1/cstemplateintegrity/overrides/{id}/override-file` — override contents
- `GET /v1/cstemplateintegrity/overrides/{id}/core-file` — stock core file the override is shadowing
- `GET /v1/cstemplateintegrity/sessions/{id}` — fetch a previous review report

**Write:**
- `POST /v1/cstemplateintegrity/overrides/{id}/apply-fix` — patch the override file in place; auto-snapshots first
- `POST /v1/cstemplateintegrity/overrides/{id}/dismiss` — clear one tracker row
- `POST /v1/cstemplateintegrity/overrides/dismiss-all` — clear every remaining row
- `POST /v1/cstemplateintegrity/sessions` — save a review report
- `POST /v1/cstemplateintegrity/backups/{id}/restore` — roll back any patch

### Security

Every endpoint is gated by ACL actions defined in `admin/access.xml` (`cstemplateintegrity.view` for reads, `cstemplateintegrity.write` for mutations). Until an admin grants those actions to a group in **System → Permissions → CS Template Integrity**, only Super Users can use the extension. Path-traversal containment is separator-anchored; PHP-extension writes are whitelisted to `templates/<tpl>/html/` only; `opcache_invalidate()` runs after every PHP write so patches take effect on the next request. Run the `security-review` skill before tagging any future release.

---

## Install

1. Download the latest `pkg_cstemplateintegrity_v*.zip` from [Releases](https://github.com/cybersalt/cs-template-integrity/releases).
2. **Extensions → Manage → Install** in your Joomla admin, upload the zip.
3. The package script auto-enables the Web Services plugin — no extra step.
4. Open **Components → CS Template Integrity** for the dashboard, copy the prompt, and paste it into Claude.

Requires Joomla 5.0+ or Joomla 6.0+ (native to both), and PHP 8.1+.

### Updates

The package manifest registers a Joomla update server, so once 1.0.1 or later is installed, future releases show up under **System → Manage → Update** like any first-party Joomla extension. No need to re-download from GitHub each time. The updater verifies a SHA256 checksum on every download.

If you're upgrading from a release earlier than 1.0.1, install the latest zip manually once — the update server kicks in from then on.

---

## License

GPL v2 or later. See [LICENSE](LICENSE).

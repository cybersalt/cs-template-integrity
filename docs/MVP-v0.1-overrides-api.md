# MVP v0.1 — Overrides API

**Goal.** Ship three read-only REST endpoints on `com_csintegrity` that expose Joomla's native `#__template_overrides` data to an external consumer (primarily Claude / Claude Code, eventually any MCP client).

**Why three endpoints and not a scanner.** The scanner, classifier, and email alerts are all bigger pieces that need this data to exist *somewhere Claude can reach it* before they can be built or tested. Joomla's core REST API does **not** expose `#__template_overrides` data. Until `com_csintegrity` does, every classifier iteration requires a human to manually copy-paste override files out of the admin UI. The three endpoints below break that bottleneck.

---

## Endpoints

All routes live under `/api/index.php/v1/csintegrity/`. All require a valid Joomla API token via the `X-Joomla-Token` header (NOT `Authorization: Bearer` — see CLAUDE.md). All return JSON:API-formatted responses (`Accept: application/vnd.api+json`).

### 1. List flagged overrides

```
GET /api/index.php/v1/csintegrity/overrides
```

Returns the current contents of `#__template_overrides`, paginated. One row per flagged override.

**Query params:**

| Param | Type | Purpose |
|---|---|---|
| `filter[template]` | string | Filter by template element (e.g., `cassiopeia`, `fairviewtha_2025`). |
| `filter[client_id]` | int | `0` = site templates, `1` = admin templates. |
| `filter[state]` | int | Joomla's own override state flag (0, 1, 2). |
| `page[limit]` | int | Page size. Default 50, max 200. |
| `page[offset]` | int | Page offset. |

**Response shape (JSON:API):**

```json
{
  "links": {
    "self": ".../api/index.php/v1/csintegrity/overrides?page[limit]=50&page[offset]=0"
  },
  "data": [
    {
      "type": "csintegrity-overrides",
      "id": "42",
      "attributes": {
        "template": "fairviewtha_2025",
        "client_id": 0,
        "hash_override": "3f9a1c...",
        "hash_core": "8b2e4d...",
        "action": "updated",
        "modified_date": "2026-04-21T14:02:11Z",
        "state": 0,
        "override_path": "html/com_content/featured/default_links.php",
        "core_path": "components/com_content/tmpl/featured/default_links.php"
      },
      "links": {
        "override-file": ".../api/index.php/v1/csintegrity/overrides/42/override-file",
        "core-file":     ".../api/index.php/v1/csintegrity/overrides/42/core-file"
      }
    }
  ],
  "meta": {
    "total-pages": 1,
    "total-items": 11
  }
}
```

### 2. Get override file contents

```
GET /api/index.php/v1/csintegrity/overrides/{id}/override-file
```

Returns the current contents of the override file on disk (resolved from `override_path` for the override's template).

**Response:**

```json
{
  "data": {
    "type": "csintegrity-file-contents",
    "id": "42:override",
    "attributes": {
      "path": "templates/fairviewtha_2025/html/com_content/featured/default_links.php",
      "hash": "3f9a1c...",
      "size": 4218,
      "modified": "2026-04-21T14:02:11Z",
      "encoding": "utf-8",
      "contents": "<?php\ndefined('_JEXEC') or die;\n..."
    }
  }
}
```

Errors:
- `404` if the file has since been deleted on disk (override row is still in DB, file isn't).
- `413` if the file exceeds a safety cap (default 1 MB; configurable in component options).

### 3. Get core file contents

```
GET /api/index.php/v1/csintegrity/overrides/{id}/core-file
```

Same shape as `override-file`, but returns the contents of the **current core file** the override is shadowing (resolved from `core_path`).

Together, these two endpoints are what Claude needs to diff and classify a flagged override.

---

## Out of scope for v0.1

- Writes of any kind. No acknowledging, dismissing, or re-flagging.
- Classifier output — that's v0.2 once the ruleset in [CLASSIFIER-PATTERNS.md](CLASSIFIER-PATTERNS.md) is actually encoded.
- Admin UI beyond a minimal "API enabled / disabled, show endpoint URLs" status page.
- Custom DB tables — we read from Joomla's existing `#__template_overrides` only.
- Scheduler, email alerts, Claude client — all deferred to later sprints.
- Non-template drift (core files, extensions, rogue file detection) — those are v1.x and later.

---

## Implementation notes

- Routes are declared in `packages/com_csintegrity/api/src/View/Overrides/` (or equivalent — follow the Joomla 5 Web Services pattern: a dedicated `api/` folder alongside `administrator/` and `site/`).
- Model: `Cybersalt\Component\Csintegrity\Api\Model\OverrideModel` reading from `#__template_overrides`. Resolve `override_path` → absolute path via the template's client-side root (`JPATH_SITE` or `JPATH_ADMINISTRATOR`). Resolve `core_path` → `JPATH_SITE/components/...` or wherever Joomla puts the current canonical template source.
- Permissions: require `core.manage` on `com_csintegrity`. v0.1 won't have finer-grained ACLs.
- Pagination: use Joomla's built-in `ListModel` pagination rather than rolling our own.

---

## Schema (confirmed 2026-04-24 against Joomla 6.1)

The earlier guess at columns (`hash_override`, `hash_core`, `override_path`, `core_path`) was wrong. The actual schema is unchanged from the original 2018 Joomla 4.0 migration:

```
id            int unsigned auto_increment PK
template      varchar(50)            -- e.g. "fairviewtha_2025"
hash_id       varchar(255)           -- BASE64-ENCODED RELATIVE PATH, not a hash
extension_id  int                    -- #__extensions row id of the template
state         tinyint                -- Joomla's own flag (0 = open, 1 = ?)
action        varchar(50)            -- e.g. "Joomla Update"
client_id     tinyint unsigned       -- 0 = site, 1 = admin
created_date  datetime
modified_date datetime
```

**`hash_id` is the override path, not a hash.** Decode with `base64_decode($row->hash_id)` to get a string like `/html/layouts/joomla/system/message.php`. The path resolution rules for both override and core files live in [../CLAUDE.md](../CLAUDE.md) under the schema section.

There is no separate `core_path` / `override_path` column. Both paths are computed from `hash_id + template + client_id`.

---

## Test plan for v0.1

1. Install `pkg_csintegrity` on cybersalt.org (dev target).
2. Intentionally create a flagged override on a site template (e.g., modify a Cassiopeia view file while an update is pending).
3. Hit each of the three endpoints with curl using an API token.
4. Confirm response shape matches the spec above.
5. Confirm `X-Joomla-Token` auth works; confirm `Authorization: Bearer` returns 401 (regression guard).
6. Install on fairviewterracehoa.com. Confirm the 11 known flagged overrides all return from endpoint 1 and that the XSS finding (`default_links.php`) is reachable via endpoints 2 and 3.

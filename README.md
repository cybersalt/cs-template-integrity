# Cybersalt Template Integrity

A Joomla 5+ integrity monitor that knows what your template *shipped with*, so it only alerts on unexpected drift — not on every override that naturally differs from core.

> **Status:** Pre-implementation. Repo initialized 2026-04-23. See [Plan](#plan) below.

---

## Why this exists

Joomla's native "X Changes found" badge in Template Manager is informational only: it tells developers when the core source a template override was based on has changed after a core update. It does **not** check whether the override itself was tampered with, and it does nothing for core or extension files. After every Joomla point release, every template with overrides lights up — meaning the signal-to-noise is zero when a site owner actually needs to know whether something bad happened.

This extension is template-literate: it knows which files come from core, from a template framework (TCCK, Helix, Gantry, T4, custom builds), or are legitimate user overrides, and only alerts on *unexplained* drift. The differentiator vs. Akeeba Admin Tools / myJoomla / RSFirewall is low-noise alerts because the scanner understands what's supposed to differ from core.

Triggering event: 2026-04-23, Rocky Wall (Fairview Terrace HOA) emailed Tim a screenshot of the "X Changes found" badges after a Joomla auto-update, worried Joomla had overwritten his custom template. The answer was no — but there was no way for Rocky to verify that himself. This extension is the answer.

---

## Plan

### Decisions locked in

- **Joomla 5+ only.** No Joomla 4 back-compat.
- **v1 is read-only.** Detects and reports drift; never reverts, quarantines, or modifies site files. Remediation is the site owner's decision.
- **AI triage is pay-per-token.** Users paste their own Anthropic API key; the extension calls Claude directly from the site and enforces hard cost caps configured in the admin. No OAuth to Claude consumer subscriptions (Anthropic disallows this for third-party apps as of 2026-04-04).
- **Free tier:** scan + baseline + findings list + email alerts.
- **Paid tier:** Claude-assisted review of findings (user brings their own API key, pays Anthropic directly; license gating is handled by `cs-download-id-manager`).

### Planned architecture

| Extension | Role |
|---|---|
| `com_csintegrity` | Admin UI — dashboard, findings browser, baseline management, settings, API key config, cost caps |
| `plg_system_csintegrity` | Installer hooks, scheduler, admin-module-style dashboard widget |
| `lib_csintegrity` | Shared baseline / hashing / Claude-client code, consumed by component, plugin, and CLI |
| CLI command `integrity:scan` | Long scans that would exceed HTTP timeouts |

All three extensions ship together as `pkg_csintegrity`.

### Related manual workflow

Until this extension exists, Cybersalt handles these requests manually via the `/joomla-override-review` Claude Code skill. That skill produces the same diagnostic logic — enumerate overrides, map to core sources, scan for malicious patterns, spot-check core, produce per-file verdicts, draft a client-facing email — but requires Tim to do it by hand on a mounted copy of the client's site. This extension is the automation of that workflow.

---

## Repo layout (planned)

```
cs-template-integrity/
├── packages/
│   ├── com_csintegrity/        # admin component
│   ├── plg_system_csintegrity/ # system plugin
│   └── lib_csintegrity/        # shared library
├── pkg_csintegrity.xml         # package manifest
├── build-package.ps1           # produces installable ZIPs
├── bump-version.ps1            # version bumper across manifests
├── CHANGELOG.md
├── LICENSE                     # GPL v2 or later
└── README.md
```

---

## License

GPL v2 or later. See [LICENSE](LICENSE).

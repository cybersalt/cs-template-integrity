# lib_csintegrity

Shared library. Placeholder — scaffold to follow in Phase 0.

Intended consumers: `com_csintegrity`, `plg_system_csintegrity`, the `integrity:scan` CLI command.

Planned responsibilities:

- Baseline snapshot + hash store
- File classification (core / framework / override / unknown)
- Scanner and drift detection
- Anthropic API client with hard cost caps
- Finding / verdict data model

Planned structure:

```
lib_csintegrity/
├── lib_csintegrity.xml               # library manifest
├── services/provider.php
└── src/
    ├── Baseline/
    ├── Classifier/
    ├── Scanner/
    ├── Claude/
    └── Finding/
```

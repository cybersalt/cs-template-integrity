# com_csintegrity

Admin-side Joomla component. Placeholder — scaffold to follow in Phase 0.

Planned structure (Joomla 5 namespaced component):

```
com_csintegrity/
├── csintegrity.xml                  # component manifest
├── script.php                        # install/update/uninstall hooks
├── admin/
│   ├── services/provider.php         # DI service provider
│   ├── src/
│   │   ├── Extension/CsIntegrityComponent.php
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── View/
│   │   └── Helper/
│   ├── tmpl/                         # view templates
│   ├── forms/                        # XML forms
│   ├── language/en-GB/
│   ├── sql/
│   │   ├── install.mysql.utf8.sql
│   │   ├── uninstall.mysql.utf8.sql
│   │   └── updates/mysql/
│   └── config.xml                    # component options
└── media/                            # CSS/JS
```

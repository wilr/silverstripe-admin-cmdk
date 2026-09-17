# Changelog

## 1.0.0

Initial release.

- Command palette UI (&#8984;K / Ctrl+K, or the CMS menu trigger) built on [cmdk](https://github.com/pacocoursey/cmdk).
- PHP API for registering command groups/commands (`Wilr\AdminCmdk\CommandPalette`).
- Server-side permission filtering for both static commands and search results.
- Pluggable `CommandSearchProvider` interface, with a default `DatabaseSearchProvider`.
- Default "Go to" commands for Pages, Files, Settings, and Security.

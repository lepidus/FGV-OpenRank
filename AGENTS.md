# AGENTS.md

## What this is

`rankingPlugin` is a generic OJS (Open Journal Systems) plugin targeting **OJS 3.3.0**. It lives at `plugins/generic/rankingPlugin/` inside an OJS checkout — it is not a standalone project. All `import(...)` paths (`plugins.generic.rankingPlugin.*`, `lib.pkp.classes.*`, `classes.*`) resolve relative to the OJS root, so the plugin cannot be built, linted, or tested outside that checkout.

The plugin adds a homepage widget (five tabs: mostRecent, mostRead, mostCited, trending, highlight) that the OJS index template renders inside any `<div class="rankingTabs"></div>` placed in the journal's Additional Content.

## Development commands

Run all commands from the **OJS root** (`../../../` relative to this plugin), not from the plugin directory.

### Tests

The plugin ships two PHPUnit tests (`tests/AltmetricsApiClientTest.php`, `tests/CrossrefApiClientTest.php`). Tests are wired through the PKP test harness, so run them via OJS's bundled PHPUnit with the PKP env config:

```bash
# From the OJS root:
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit \
    --configuration lib/pkp/tests/phpunit-env2.xml \
    -v plugins/generic/rankingPlugin/tests

# Single test file
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit \
    --configuration lib/pkp/tests/phpunit-env2.xml \
    -v plugins/generic/rankingPlugin/tests/AltmetricsApiClientTest.php

# Single test method (filter by method name)
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit \
    --configuration lib/pkp/tests/phpunit-env2.xml \
    --filter itShouldReturnServerError \
    -v plugins/generic/rankingPlugin/tests

# The standard PKP runner also works:
lib/pkp/tools/runAllTests.sh -p     # runs every plugin's tests
```

CI (`.gitlab-ci.yml`) pulls shared templates from `documentacao-e-tarefas/modelosparaintegracaocontinua` on ref `stable-3_3_0` (`pkp_plugin.yml` + `ojs/unit_tests.yml`) — mirror those pipelines if reproducing CI locally.

### Running the scheduled cache refresh by hand

```bash
# From OJS root — executes every plugin's due scheduled tasks, including this one
php tools/runScheduledTasks.php
```

## Architecture

### Request flow

The plugin hooks OJS at four points, registered in `RankingPlugin::register` (RankingPlugin.inc.php:15-22) and dispatched through `classes/HookCallback.inc.php`:

1. **`TemplateManager::display`** (frontend) — on `frontend/pages/indexJournal.tpl`, `HookCallback::handleMetricsData` injects per-tab settings, localized titles/descriptions, the fetched `ranking.tpl` HTML, and a `window.app` JS blob; then enqueues `js/insertRankingTemplate.js` + `styles/*.css`.
2. **`Dispatcher::dispatch`** (API) — `HookCallback::setupRankingPluginAPIHandler` intercepts any path matching `api/v1/rankingPlugin`, loads `api/v1/rankingPlugin/RankingPluginHandler.inc.php`, runs its Slim app, and `exit`s. The plugin never registers through OJS's normal API discovery — **all routing for this plugin is the hook's responsibility**.
3. **`LoadComponentHandler`** (admin) — enables `RankingConfigurationGridHandler` for the settings grid.
4. **`Schema::get::submission`** — appends an `altmetricsScore` (nullable number, `apiSummary: true`) property to the submission JSON schema.

There's also an `AcronPlugin::parseCronTab` hook (registered directly on the plugin, not the callback object) that appends `scheduledTasks.xml` so Acron picks up `RankingCacheUpdateTask`.

### The four-tab pipeline

Each tab is a pair of **cache class** (in `classes/cache/`) + **factory method** on `RankingSubmission` (`classes/factory/RankingSubmission.inc.php`). All caches use OJS's `CacheManager::getFileCache` keyed on context id. The pattern is: `get*` reads the cache and, on miss, calls `refreshCache`; `refreshCache` hits the source, writes the cache, returns the fresh data.

| Tab         | Source                                  | Cache class                   | DOI-indirection |
|-------------|-----------------------------------------|-------------------------------|-----------------|
| mostRecent  | `Services::get('submission')->getMany`  | `MostRecent`                  | no              |
| mostRead    | `Services::get('stats')->getOrderedObjects` | `MostRead`                | no              |
| mostCited   | Crossref API (`clients/Crossref`)       | `MostCitedDois` → `RankingSubmissionService::getAListOfMostCitedSubmissionsByCachedDois` | **yes** — cache stores DOIs, submissions are resolved on read via `SubmissionDAO::getByPubId('doi', ...)` |
| trending    | Altmetric API (`clients/Altmetrics`)    | `BestAltmetricsScoreDois` → `TrendingSubmissions` | **yes** — same DOI-indirection as mostCited |

`RankingSubmissionService` (`classes/RankingSubmissionService.inc.php`) is the thin entry point that `RankingPluginHandler` and the scheduled task both call; it forwards to `RankingSubmission::get($functionName, $params)` which dispatches to the correct `get*` factory method. `RankingSubmission::formatSubmissionData` is the shared shape — **any field added there shows up in every tab's JSON response**, so edit it when introducing new display fields.

The **scheduled task** `classes/tasks/RankingCacheUpdateTask.inc.php` iterates enabled contexts and calls each cache's `refreshCache` directly. `scheduledTasks.xml` sets `frequency hour="0"` (once per day at midnight). The Altmetric and Crossref API calls happen only inside the scheduled path and inside the HTTP-request cache-miss path — the frontend AJAX never calls the external APIs directly.

**Trending-tab ISSN quirk**: `TrendingSubmissions::refreshCache` prefers `printIssn` and falls back to `onlineIssn`, while `RankingCacheUpdateTask::updateTrendingCache` (and `getMostCited` in the API handler) prefer `onlineIssn` with `printIssn` fallback. Keep them aligned if you touch one.

### Settings model

Settings are stored per context (journal) via `plugin->getSetting($contextId, $key)`. The tab grid writes keys with suffixes:

- `tabEnabled_{index}`, `tabSequence_{index}` — `index` is the position in `['mostRecent', 'mostRead', 'mostCited', 'trending', 'highlight']` from `HookCallback::getOrderedTabs`. Tab ordering is derived from these two per-index keys; a `tabEnabled_{index}` that's never set counts as enabled (check is `!== false`).
- `customTitle_{tabId}`, `customDescription_{tabId}`, `highlightContent_{tabId}` — localized values. `getLocalizedValue` falls back current locale → primary locale → first non-empty.
- `itemsPerTab` / `itemsPerPage` are global defaults (4); `itemsPerTab_{tabId}` / `itemsPerPage_{tabId}` override per tab.

The settings admin UI is a PKP `GridHandler` (`controllers/grid/RankingConfigurationGridHandler.inc.php`) with actions `editTab`, `updateTab`, `saveSequence`, `saveTabSetting` restricted to `ROLE_ID_MANAGER`. The "main" plugin settings form (`classes/settings/RankingPluginSettingsForm.inc.php`) is mostly a shell — `initData`/`readInputData`/`execute` are pass-throughs; actual per-tab config lives in the grid + `RankingCustomizationForm`.

### Frontend

`js/insertRankingTemplate.js` replaces the first `.rankingTabs` div with `window.app.rankingTemplate`, then fires four parallel AJAX calls to `window.app.rankingPluginApiBaseUrl + /{mostRecent,mostRead,mostCitedSubmissions,trendingSubmissions}`. Error messages per tab are pre-localized into `window.app` (mostRecentFailedMessage, …). The `highlight` tab is content-only and has no API call.

The journal operator must add `<div class="rankingTabs"></div>` to **Website → Appearance → Advanced → Additional Content**, and `allowed_hosts` in `config.inc.php` must include the journal's host (see README).

## Conventions

- OJS 3.3 PHP: no namespaces, files are `.inc.php`, classes are autoloaded via `import('plugins.generic.rankingPlugin.…')`. New files must follow this convention or they won't load.
- `version.xml` must be bumped (and `<date>` updated) for any release — OJS decides whether to run upgrade logic from that version string.
- The plugin is `lazy-load=1`: a context-level enable flag (`getEnabled()`) gates every hook; all runtime code must assume the plugin may be disabled.
- External API errors are caught in `classes/clients/*.inc.php` and re-thrown as localized `\Exception` messages keyed `plugins.generic.rankingPlugin.client.{altmetrics,crossref}.{server,client,transfer}Error`. `RankingPluginHandler` translates those into `{errorMessage: ...}` 500 JSON responses — keep that pattern when adding new endpoints so the JS error branches render the right text.
- Tests mock the HTTP client via `tests/helpers/ClientInterfaceForTests.inc.php` (a thin Guzzle-compatible interface) rather than mocking Guzzle directly.

# AGENTS.md

## What this is

**FGV OpenRank** is a generic OJS (Open Journal Systems) plugin targeting **OJS 3.3.0**. "FGV OpenRank" is a proper name and stays untranslated in every locale; `rankingPlugin` remains the technical identifier used by the directory name, locale keys, class names and CI variables. It lives at `plugins/generic/rankingPlugin/` inside an OJS checkout — it is not a standalone project. All `import(...)` paths (`plugins.generic.rankingPlugin.*`, `lib.pkp.classes.*`, `classes.*`) resolve relative to the OJS root, so the plugin cannot be built, linted, or tested outside that checkout.

The plugin adds a homepage widget (five tabs: mostRecent, mostRead, mostCited, trending, highlight) rendered inside a `<div class="rankingTabs"></div>` placeholder — either emitted by the plugin on the journal index page or placed by hand in the journal's Additional Content, depending on the `displayPosition` setting.

## Development commands

Run all commands from the **OJS root** (`../../../` relative to this plugin), not from the plugin directory.

### Tests

The plugin's PHPUnit tests live in `tests/`. Tests are wired through the PKP test harness, so run them via OJS's bundled PHPUnit with the PKP env config:

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

The plugin hooks OJS at five points, registered in `RankingPlugin::register` (RankingPlugin.inc.php:15-23) and dispatched through `classes/HookCallback.inc.php`:

1. **`TemplateManager::display`** (frontend) — on `frontend/pages/indexJournal.tpl`, `HookCallback::handleMetricsData` injects per-tab settings, localized titles/descriptions, the fetched `ranking.tpl` HTML, and a `window.app` JS blob; then enqueues `js/insertRankingTemplate.js` + `styles/*.css`.
2. **`Templates::Index::journal`** (frontend) — `HookCallback::insertRankingPlaceholder` appends `<div class="rankingTabs"></div>` to the hook output for every plugin-owned `displayPosition` (`top`, `afterSection`, `bottom`), which lands it as the first child of whatever element the theme uses for the homepage. It is the only frontend template hook on that page; themes that override `indexJournal.tpl` keep the `{call_hook}` they copied from core. When the setting is `additionalContent` (the default) the callback emits nothing and the manager's own placeholder is used.
3. **`Dispatcher::dispatch`** (API) — `HookCallback::setupRankingPluginAPIHandler` intercepts any path matching `api/v1/rankingPlugin`, loads `api/v1/rankingPlugin/RankingPluginHandler.inc.php`, runs its Slim app, and `exit`s. The plugin never registers through OJS's normal API discovery — **all routing for this plugin is the hook's responsibility**.
4. **`LoadComponentHandler`** (admin) — enables `RankingConfigurationGridHandler` for the settings grid.
5. **`Schema::get::submission`** — appends an `altmetricsScore` (nullable number, `apiSummary: true`) property to the submission JSON schema.

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
- `displayPosition` — `top`, `afterSection`, `bottom` or `additionalContent` (the default and the fallback for unknown/absent values) — and `displayPositionSection`, the 1-based section number `afterSection` counts to (`normalizeSection` clamps anything below 1 to 1). Both live in `classes/RankingDisplayPosition.inc.php`; `HookCallback` and `RankingPluginSettingsForm` both go through its `normalize`/`normalizeSection`/`needsPlaceholder`, so add new positions there.

The settings admin UI is a PKP `GridHandler` (`controllers/grid/RankingConfigurationGridHandler.inc.php`) with actions `editTab`, `updateTab`, `saveSequence`, `saveTabSetting` restricted to `ROLE_ID_MANAGER`. The "main" plugin settings form (`classes/settings/RankingPluginSettingsForm.inc.php`) holds only `displayPosition` + `displayPositionSection`; actual per-tab config lives in the grid + `RankingCustomizationForm`, which `templates/settings/form.tpl` loads below the position radios.

### Configuration guide

`classes/settings/Actions.inc.php` puts two `LinkAction`s on the plugin row when the plugin is enabled: **Settings** (verb `settings`) and **Configuration guide** (verb `configurationGuide`), in that order. `classes/settings/Manage.inc.php` dispatches both; its `default` branch calls `RankingPlugin::parentManage()`, which is the only way back to `GenericPlugin::manage()` — calling `manage()` there would route straight into `Manage::execute()` again.

The guide itself is `classes/settings/ConfigurationGuide.inc.php` rendering `templates/admin/configurationGuide.tpl` into an `AjaxModal`: eight `[data-guide-panel]` sections (intro, six steps, conclusion) toggled by `hidden` through `js/configurationGuide.js`, styled by `styles/admin/configurationGuide.css`. Both assets are injected as plain tags inside the modal, so `ConfigurationGuide::getAssetVersion()` appends the `?v=` that `addJavaScript()`/`addStyleSheet()` would otherwise add — jQuery fetches injected scripts with `cache: true`.

Things that break silently if changed carelessly:

- **`data-guide-target` is a panel index**, not an id. Inserting a step means renumbering every following button and the `total=` of `configurationGuide.progress`.
- **Every path segment is a core OJS label**, resolved from the `.po` of each locale rather than translated by hand (`manager.setup.masthead` is "Equipe Editorial" in pt_BR, `common.plugins` is "Módulos" in es_ES). The deep links are built by `Dispatcher` with the tab anchors of OJS 3.3 (`#plugins/installedPlugins`, `#appearance/advanced`, `#masthead`); confirm them against `lib/pkp/templates/management/website.tpl` and `templates/management/context.tpl` before changing.
- **A path starts where its button lands**, not at the sidebar — `Installed Plugins → FGV OpenRank → Settings → …`, because the step's link already opened that tab. So a `configurationGuide.link.*` label, the anchor of the URL behind it and the first segment of the path it sits next to are one unit: change the link and the path has to move with it.
- **Additional Content is a TinyMCE field**, so the guide sends the operator through its "Source code" button. TinyMCE ships no langs here, so that label is English in every locale. It pads `<div class="rankingTabs"></div>` to `<div class="rankingTabs">&nbsp;</div>` on save; the element survives, the class is kept.
- There is no page URL for the plugin's own settings modal — it is a component call returning JSON — so the steps about it link to **Installed Plugins** and the link labels say so.

All guide strings live under `plugins.generic.rankingPlugin.configurationGuide.*` in all three locales; the template carries no literal text.

### Frontend

`js/insertRankingTemplate.js` replaces the first `.rankingTabs` div with `window.app.rankingTemplate`, then fires four parallel AJAX calls to `window.app.rankingPluginApiBaseUrl + /{mostRecent,mostRead,mostCitedSubmissions,trendingSubmissions}`. Error messages per tab are pre-localized into `window.app` (mostRecentFailedMessage, …). The `highlight` tab is content-only and has no API call.

**Positioning is split between PHP and JS.** PHP only guarantees the placeholder exists at the start of the homepage container; `moveToConfiguredPosition` in `js/insertRankingTemplate.js` then moves it, before filling it, using `placeholder.parentElement` as the anchor and `window.app.displayPosition` / `displayPositionSection` (both injected by `HookCallback::getDisplayPositionSettings`) as the offset. `afterSection` counts **every** element child of that parent, so the homepage image counts as a section and a theme wrapper (`saudeEmDebate`'s `.saude_home_content`) collapses several visual sections into one slot — the settings help text and the READMEs say so, keep them honest if the counting changes. It deliberately matches **no** CSS classes: the local themes disagree on all of them (`rieja` has no current-issue section and renders `.additional_content` first; `saudeEmDebate` uses `.saude_announcements`/`.saude_articles` instead of `.cmp_announcements`/`.current_issue`), so counting the container's own children is the only theme-agnostic anchor. Keep it that way when adding positions.

**Scoping is what makes the widget theme-agnostic, and it has to hold on both sides.** Every rule in `styles/ranking.css` and `styles/pagination.css` is prefixed with `.rankingTabs`, and the tab click handlers in `js/insertRankingTemplate.js` query from the `rankingTabsDiv` element rather than from `document`. The placeholder div survives `innerHTML =` and is always the widget's outermost element, so both are safe anchors. The reason is that the markup in `ranking.tpl` uses Bootstrap's generic names — `.nav-tabs`, `.tab-content`, `.tab-pane`, `.article-item` — which a theme or another plugin may also use on the homepage. Unscoped, the CSS restyled their elements and, worse, the click handler bound their tab links and stripped `.active` off their `li`s and panes on every tab switch. Keep new selectors and new DOM queries scoped the same way.

Spacing in those stylesheets is deliberately **not** in `rem`: the default theme sets `html { font-size: 14px }` (`plugins/themes/default/styles/variables.less`), so a `rem` means something different in every theme. Structural values added since — the block margin, the gutter, the pagination control sizes, the `hr` rhythm — are in px; the metadata gaps are in `em` so they track the theme's font size. Several `em` paddings on `.nav-tabs`/`.tab-content` predate that split and were left alone. `.rankingTabs` declares `--ranking-block-spacing` (the outer margin that keeps the widget off its neighbours in every `displayPosition`), `--ranking-gutter`, `--ranking-cover-width`, `--ranking-details-min-width`, `--ranking-surface`, `--ranking-border-color`, `--ranking-text` and `--ranking-link`; a journal can override any of them from Additional Content. The palette is only partly tokenised — the tab-strip greys and the pagination hover/disabled colours are still literals.

The article row is fluid rather than breakpoint-driven — `.article-item` wraps and `.article-details` has a `flex-basis` of `--ranking-details-min-width` with `min-width: 0` — so the cover drops above the text on the container's width, not the viewport's, which is what makes it survive a narrow theme column. Two details there are load-bearing and easy to break: `.article-item` must keep the default `align-items: stretch`, because `.article-details.highlight { align-content: center }` only centres pasted highlight content while the column is stretched to the row height; and `.nav-tabs` needs its `row-gap: 1px` to cancel the `margin-bottom: -1px` on `li`, which is a single-row trick for lapping the strip's bottom border and would otherwise overlap every wrapped row. The one `@media` block (max-width 767px) tightens spacing, drops the tab font a point and lets the tabs share a row evenly.

With `displayPosition` left at `additionalContent`, the journal operator must add `<div class="rankingTabs"></div>` to **Website → Appearance → Advanced → Additional Content**; otherwise the plugin emits the placeholder itself. Either way `allowed_hosts` in `config.inc.php` must include the journal's host (see README).

## Conventions

- OJS 3.3 PHP: no namespaces, files are `.inc.php`, classes are autoloaded via `import('plugins.generic.rankingPlugin.…')`. New files must follow this convention or they won't load.
- `version.xml` must be bumped (and `<date>` updated) for any release — OJS decides whether to run upgrade logic from that version string.
- The plugin is `lazy-load=1`: a context-level enable flag (`getEnabled()`) gates every hook; all runtime code must assume the plugin may be disabled.
- External API errors are caught in `classes/clients/*.inc.php` and re-thrown as localized `\Exception` messages keyed `plugins.generic.rankingPlugin.client.{altmetrics,crossref}.{server,client,transfer}Error`. `RankingPluginHandler` translates those into `{errorMessage: ...}` 500 JSON responses — keep that pattern when adding new endpoints so the JS error branches render the right text.
- Tests mock the HTTP client via `tests/helpers/ClientInterfaceForTests.inc.php` (a thin Guzzle-compatible interface) rather than mocking Guzzle directly.

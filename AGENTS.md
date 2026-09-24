# AGENTS.md

## What this is

**FGV OpenRank** is a generic OJS (Open Journal Systems) plugin targeting **OJS 3.5.0**, from 3.5.0-1 on. "FGV OpenRank" is a proper name and stays untranslated in every locale; `rankingPlugin` remains the technical identifier used by the directory name, locale keys and CI variables. It lives at `plugins/generic/rankingPlugin/` inside an OJS checkout and is not a standalone project: classes are namespaced under `APP\plugins\generic\rankingPlugin` and autoloaded by OJS, so the plugin cannot be tested outside that checkout.

The main class is `RankingPlugin` in `RankingPlugin.php`, and `index.php` returns an instance of it. **Do not delete `index.php`**: `PluginRegistry::instantiatePlugin` derives the class name from the directory (`rankingPlugin` becomes `RankingPluginPlugin`), and when that class does not exist it falls back to `index.php`. Without it the plugin silently disappears from Installed Plugins. The `pflPlugin` shipped with OJS 3.5 does the same for the same reason.

The plugin adds a homepage widget (five tabs: mostRecent, mostRead, mostCited, trending, highlight) rendered inside a `<div class="rankingTabs"></div>` placeholder, either emitted by the plugin on the journal index page or placed by hand in the journal's Additional Content, depending on the `displayPosition` setting.

## Development commands

Run PHP commands from the **OJS root** (`../../../` relative to this plugin), and npm commands from the plugin directory.

### Tests

```bash
# All plugin tests
php lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml plugins/generic/rankingPlugin/tests

# Single test method
php lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml \
    --filter itShouldReturnServerError plugins/generic/rankingPlugin/tests
```

Tests use PHPUnit 11 `#[Test]` attributes. CI (`.gitlab-ci.yml`) pulls shared templates from `documentacao-e-tarefas/modelosparaintegracaocontinua` on ref `stable-3_5_0`; they also run `php-cs-fixer` with `@PSR12` and check that `package.json` versions are not older than the ones in the OJS `package.json`.

### Settings UI build

The settings screen is Vue, built by Vite (`vite.config.js`, `i18nExtractKeys.vite.js`) from `resources/js` into `public/build`. `public/build` and `registry/uiLocaleKeysBackend.json` are committed, because the release package is copied without a build step. `vue` is pinned to the version the OJS bundle ships, since it is only used as a template compiler and the runtime comes from `pkp.modules.vue`.

```bash
npm install
npm run build
```

### Running the scheduled cache refresh by hand

```bash
php lib/pkp/tools/scheduler.php test --name='APP\plugins\generic\rankingPlugin\classes\tasks\RankingCacheUpdateTask'
```

## Architecture

### Request flow

`RankingPlugin::register` registers three hooks, dispatched through `classes/HookCallback.php`, and adds the settings bundle (`public/build`) to backend pages:

1. **`TemplateManager::display`** (frontend): on `frontend/pages/indexJournal.tpl`, `HookCallback::handleMetricsData` injects per-tab settings, localized titles/descriptions, the fetched `ranking.tpl` HTML, and a `window.app` JS blob; then enqueues `js/insertRankingTemplate.js` + `styles/*.css`.
2. **`Templates::Index::journal`** (frontend): `HookCallback::insertRankingPlaceholder` appends `<div class="rankingTabs"></div>` to the hook output for every plugin-owned `displayPosition` (`top`, `afterSection`, `bottom`), which lands it as the first child of whatever element the theme uses for the homepage. When the setting is `additionalContent` (the default) the callback emits nothing and the manager's own placeholder is used.
3. **`Dispatcher::dispatch`** (API): `HookCallback::setupApiControllers` wraps one of two `PKPBaseController`s in an `APIHandler`, runs its routes and `exit`s. `RankingPluginController` (`api/v1/rankingPlugin/{mostRecent,mostRead,mostCitedSubmissions,trendingSubmissions}`) is public and only needs a context. `RankingPluginSettingsController` (`api/v1/plugins/rankingplugin/settings`) needs a logged-in manager or site admin. The settings controller is matched first, because its path also starts with the plugin name. The plugin does not use `APIHandler::endpoints::plugin`, and it does not extend `PluginSettingsController` or use `PublicAccessPolicy`, because those only exist from 3.5.0-4 on.

Route parameters must be read with `$illuminateRequest->route('name')`: the API router passes the context path as the first positional argument, so a `string $tabId` method parameter receives the journal path instead.

The scheduled task is registered through `HasTaskScheduler::registerSchedules` (daily at midnight).

### The four-tab pipeline

Each tab is a pair of **cache class** (in `classes/cache/`) + **factory method** on `RankingSubmission` (`classes/factory/RankingSubmission.php`). All caches go through `RankingCache`, a thin wrapper on Laravel's `Cache` facade keyed `rankingPlugin-{name}-{contextId}` and stored forever. The pattern is: `get*` reads the cache and, on miss or empty list, calls `refreshCache`; `refreshCache` hits the source, writes the cache, returns the fresh data.

| Tab         | Source                                  | Cache class                   | DOI-indirection |
|-------------|-----------------------------------------|-------------------------------|-----------------|
| mostRecent  | `Repo::submission()->getCollector()`    | `MostRecent`                  | no              |
| mostRead    | `app()->get('publicationStats')->getTotals` | `MostRead`                | no              |
| mostCited   | Crossref API (`clients/Crossref`)       | `MostCitedDois` → `RankingSubmissionService::getAListOfMostCitedSubmissionsByCachedDois` | **yes**: cache stores DOIs, submissions are resolved on read via `Repo::submission()->getByDoi` |
| trending    | Altmetric API (`clients/Altmetrics`) or the manual DOI list | `BestAltmetricsScoreDois` → `TrendingSubmissions` | **yes**, same DOI-indirection as mostCited |

`RankingSubmissionService` is the thin entry point that the API controller and the scheduled task both call; it forwards to `RankingSubmission::get($functionName, $params)`. `RankingSubmission::formatSubmissionData` is the shared shape: **any field added there shows up in every tab's JSON response**.

The **scheduled task** `classes/tasks/RankingCacheUpdateTask.php` iterates enabled contexts and calls each cache's `refreshCache` directly; mostCited and trending are skipped when the journal has no ISSN. The Altmetric and Crossref API calls happen only inside the scheduled path, the cache-miss path and when a tab is saved; the frontend AJAX never calls the external APIs directly.

**Trending-tab ISSN quirk**: `TrendingSubmissions::getContextIssn` prefers `printIssn` and falls back to `onlineIssn`, while the task, `TabSettings::getContextIssn` and `getMostCited` in the API controller prefer `onlineIssn` with `printIssn` fallback. Keep them aligned if you touch one.

The Altmetric API key is stored encrypted by `classes/DataEncryption.php`, which uses Laravel's `Crypt` (the OJS `app_key`). Keys stored by the 3.3 version were encrypted with `api_key_secret` and cannot be decrypted; `TrendingSubmissions` logs the failure and falls back to the manual DOI list.

### Settings model

Settings are stored per context (journal) via `plugin->getSetting($contextId, $key)`, with plugin name `rankingplugin`. `classes/RankingTabs.php` owns the tab list and the per-index keys:

- `tabEnabled_{index}`, `tabSequence_{index}`: `index` is the position in `RankingTabs::getAll()`. A `tabEnabled_{index}` that's never set counts as enabled (check is `!== false`). `RankingTabs::save` rewrites both for all tabs from the ordered list the settings table sends.
- `customTitle_{tabId}`, `customDescription_{tabId}`, `highlightContent_{tabId}`: localized values. `RankingTabs::localize` falls back current locale → primary locale → first non-empty.
- `itemsPerTab_{tabId}` / `itemsPerPage_{tabId}` (default 4), `mostReadDays_mostRead` (default 120), `altmetricsApiKey_trending`, `trendingDois_trending` (`{id: doi}` in display order).
- `displayPosition`: `top`, `afterSection`, `bottom` or `additionalContent` (the default and the fallback for unknown/absent values), and `displayPositionSection`, the 1-based section number `afterSection` counts to. Both live in `classes/RankingDisplayPosition.php`; `HookCallback`, `DisplayPositionForm` and `DisplayPositionSettings` all go through its `normalize`/`normalizeSection`/`needsPlaceholder`, so add new positions there.

The settings UI stays in the usual plugin flow: **Settings** on the plugin row is an `AjaxModal` to `manage` verb `settings`, which renders `templates/settings.tpl`. That template only mounts the `RankingPluginSettings` Vue component with `pkp.registry.init(id, 'Container', {})`, the same way core does in `assignToIssue.tpl`. The component loads everything from `GET settings`: the tab list, one `FormComponent` config per tab (`TabSettingsForm`), the position form (`DisplayPositionForm`) and the manual DOI list with its form (`TrendingDoiForm`). Editing a tab swaps the view inside the same modal (Back returns to the list) instead of opening a nested side modal, because `PkpSideModalBody`, `PkpSideModalLayoutBasic`, `PkpTableCellOrder` and `PkpFormModal` are not exposed to plugins in 3.5.0-1. Ordering uses the plugin's own `RankingOrderButtons`. Every element the tests may need carries a `data-cy` attribute. Validation lives in `TabSettings` and `TrendingDois`, which return `{field: [message]}` with status 422 so `PkpForm` shows the errors inline.


### Configuration guide

`classes/settings/Actions.php` puts two `LinkAction`s on the plugin row when the plugin is enabled: **Settings** (verb `settings`) and **Configuration guide** (verb `configurationGuide`), in that order. `classes/settings/Manage.php` dispatches both; its `default` branch calls `RankingPlugin::parentManage()`, which is the only way back to `GenericPlugin::manage()` — calling `manage()` there would route straight into `Manage::execute()` again.

The guide itself is `classes/settings/ConfigurationGuide.php` rendering `templates/admin/configurationGuide.tpl` into an `AjaxModal`: eight `[data-guide-panel]` sections (intro, six steps, conclusion) toggled by `hidden` through `js/configurationGuide.js`, styled by `styles/admin/configurationGuide.css`. Both assets are injected as plain tags inside the modal, so `RankingPlugin::getAssetVersion()` appends the `?v=` that `addJavaScript()`/`addStyleSheet()` would otherwise add — jQuery fetches injected scripts with `cache: true`.

Things that break silently if changed carelessly:

- **`data-guide-target` is a panel index**, not an id. Inserting a step means renumbering every following button and the `total=` of `configurationGuide.progress`.
- **Every path segment is a core OJS label**, resolved from the `.po` of each locale rather than translated by hand (`manager.setup.masthead` is "Expediente" in pt_BR, `common.plugins` is "Módulos" in es). The deep links are built by `Dispatcher` with the tab anchors of OJS 3.5 (`#plugins/installedPlugins`, `#appearance/advanced`, `#masthead`, `distribution#dois/doisSetup`, and the `dois` page); confirm them against `lib/pkp/templates/management/website.tpl`, `lib/pkp/templates/management/distribution.tpl` and `templates/management/context.tpl` before changing.
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

- OJS 3.5 PHP: namespace `APP\plugins\generic\rankingPlugin\...` matching the directory, one class per `.php` file. Before using a core class, check that it exists in the `3_5_0-1` tag of `lib/pkp` (`git -C lib/pkp cat-file -e 3_5_0-1:classes/...`), since that is the lowest supported version.
- `version.xml` must be bumped (and `<date>` updated) for any release — OJS decides whether to run upgrade logic from that version string.
- The plugin is `lazy-load=1`: a context-level enable flag (`getEnabled()`) gates every hook; all runtime code must assume the plugin may be disabled.
- External API errors are caught in `classes/clients/*.php` and re-thrown as localized `\Exception` messages keyed `plugins.generic.rankingPlugin.client.{altmetrics,crossref}.{server,client,transfer}Error`. `RankingPluginController` translates those into `{errorMessage: ...}` 500 JSON responses — keep that pattern when adding new endpoints so the JS error branches render the right text.
- Tests mock the HTTP client via `tests/helpers/ClientInterfaceForTests.php` (a thin Guzzle-compatible interface) rather than mocking Guzzle directly.

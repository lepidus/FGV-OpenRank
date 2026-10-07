<?php

import('plugins.generic.fgvOpenRank.classes.RankingDisplayPosition');

class HookCallback
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function setupFgvOpenRankAPIHandler(string $hookname, Request $request)
    {
        $router = $request->getRouter();
        if (!($router instanceof \APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/fgvOpenRank')) {
            $this->plugin->import('api.v1.fgvOpenRank.FgvOpenRankHandler');
            $handler = new FgvOpenRankHandler();
        }

        if (!isset($handler)) {
            return;
        }

        $router->setHandler($handler);
        $handler->getApp()->run();
        exit;
    }

    public function handleMetricsData($hookName, $args)
    {
        $template = $args[1];

        if ($template !== 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $fgvOpenRankApiBaseUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $context->getPath(),
            'fgvOpenRank/'
        );

        $contextId = $context->getId();
        $customTitles = [];
        $customDescriptions = [];
        $highlightCustomContent = [];

        $locale = AppLocale::getLocale();

        $tabs = $this->getOrderedTabs($contextId);

        foreach ($tabs as $tabId) {
            $customTitleData = $this->plugin->getSetting(
                $contextId,
                "customTitle_{$tabId}"
            );
            $customDescriptionData = $this->plugin->getSetting(
                $contextId,
                "customDescription_{$tabId}"
            );

            $customTitles[$tabId] = $this->getLocalizedValue(
                $customTitleData,
                $locale
            );
            $customDescriptions[$tabId] = $this->getLocalizedValue(
                $customDescriptionData,
                $locale
            );

            if ($tabId === 'highlight') {
                $highlightContentData = $this->plugin->getSetting(
                    $contextId,
                    "highlightContent_{$tabId}"
                );
                $highlightCustomContent[$tabId] = $this->getLocalizedValue(
                    $highlightContentData,
                    $locale
                );
            }
        }

        $templateMgr->assign('customTitles', $customTitles);
        $templateMgr->assign('customDescriptions', $customDescriptions);
        $templateMgr->assign('highlightCustomContent', $highlightCustomContent);
        $templateMgr->assign('orderedTabs', $tabs);

        $itemsPerTab = $this->plugin->getSetting($contextId, 'itemsPerTab') ?? 4;
        $itemsPerPage = $this->plugin->getSetting($contextId, 'itemsPerPage') ?? 4;

        $tabSpecificSettings = [];
        foreach ($tabs as $tabId) {
            $tabItemsPerTab = $this->plugin->getSetting(
                $contextId,
                "itemsPerTab_{$tabId}"
            );

            $tabItemsPerPage = $this->plugin->getSetting(
                $contextId,
                "itemsPerPage_{$tabId}"
            );

            $tabSpecificSettings[$tabId] = [
                'itemsPerTab' => $tabItemsPerTab !== null
                    ? (int)$tabItemsPerTab
                    : (int)$itemsPerTab,
                'itemsPerPage' => $tabItemsPerPage !== null
                    ? (int)$tabItemsPerPage
                    : (int)$itemsPerPage,
            ];
        }

        $fgvOpenRankJavaScriptVariables = $this->getDisplayPositionSettings($contextId) + [
            'currentLocale' => AppLocale::getLocale(),
            'primaryLocale' => AppLocale::getPrimaryLocale(),
            'publishedDateLocaleMessage' => __("plugins.generic.fgvOpenRank.tabs.content.publishedDate"),
            'rankingTemplate' => $templateMgr->fetch(
                $this->plugin->getTemplateResource('ranking.tpl')
            ),
            'fgvOpenRankApiBaseUrl' => $fgvOpenRankApiBaseUrl,
            'itemsPerTab' => $itemsPerTab,
            'itemsPerPage' => $itemsPerPage,
            'tabSettings' => $tabSpecificSettings,
            'previousPageLabel' => "<",
            'nextPageLabel' => ">",
            'mostRecentFailedMessage' => __(
                'plugins.generic.fgvOpenRank.tabs.mostRecentFailed'
            ),
            'mostReadFailedMessage' => __(
                'plugins.generic.fgvOpenRank.tabs.mostReadFailed'
            ),
            'mostCitedFailedMessage' => __(
                'plugins.generic.fgvOpenRank.tabs.mostCitedFailed'
            ),
            'trendingFailedMessage' => __(
                'plugins.generic.fgvOpenRank.tabs.trendingFailed'
            ),
            'noPublicationsFoundMessage' => __(
                'plugins.generic.fgvOpenRank.NoPublicationsFound'
            ),
        ];

        $this->loadResources(
            $templateMgr,
            $request,
            $fgvOpenRankJavaScriptVariables
        );

        return false;
    }

    public function insertRankingPlaceholder($hookName, $args)
    {
        $contextId = $this->getContextId();

        if ($contextId === null) {
            return false;
        }

        $settings = $this->getDisplayPositionSettings($contextId);

        if (!RankingDisplayPosition::needsPlaceholder($settings['displayPosition'])) {
            return false;
        }

        $args[2] .= RankingDisplayPosition::PLACEHOLDER;

        return false;
    }

    public function getDisplayPositionSettings($contextId): array
    {
        return [
            'displayPosition' => RankingDisplayPosition::normalize(
                $this->plugin->getSetting(
                    $contextId,
                    RankingDisplayPosition::SETTING_NAME
                )
            ),
            'displayPositionSection' => RankingDisplayPosition::normalizeSection(
                $this->plugin->getSetting(
                    $contextId,
                    RankingDisplayPosition::SECTION_SETTING_NAME
                )
            ),
        ];
    }

    public function addScoreFieldToSubmissionSchema($hookName, $args)
    {
        $schema = $args[0];

        $schema->properties->{"altmetricsScore"} = (object) [
            'type' => 'number',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];

        return false;
    }

    public function setupRankingConfigurationGridHandler($hookName, $params)
    {
        $component = &$params[0];
        $allowed = [
            'plugins.generic.fgvOpenRank.controllers.grid.RankingConfigurationGridHandler',
            'plugins.generic.fgvOpenRank.controllers.grid.TrendingDoisGridHandler',
        ];
        if (in_array($component, $allowed, true)) {
            return true;
        }
        return false;
    }


    protected function getContextId()
    {
        $context = Application::get()->getRequest()->getContext();

        return $context === null ? null : $context->getId();
    }

    private function loadResources($templateMgr, $request, $fgvOpenRankJavaScriptVariables)
    {
        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($fgvOpenRankJavaScriptVariables) . ';',
            ['inline' => true]
        );

        $templateMgr->addJavaScript(
            'momentJs',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/lib/momentjs/moment.min.js',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addJavaScript(
            'fgvOpenRankScript',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/insertRankingTemplate.js',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'fgvOpenRankStyles',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/styles/ranking.css',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'fgvOpenRankPaginationStyles',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/styles/pagination.css',
            ['priority' => STYLE_SEQUENCE_LAST]
        );
    }

    private function getLocalizedValue($data, $locale)
    {
        if (empty($data)) {
            return '';
        }

        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            if (isset($data[$locale]) && !empty($data[$locale])) {
                return $data[$locale];
            }

            $primaryLocale = AppLocale::getPrimaryLocale();
            if (isset($data[$primaryLocale]) && !empty($data[$primaryLocale])) {
                return $data[$primaryLocale];
            }

            foreach ($data as $value) {
                if (!empty($value)) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function getOrderedTabs($contextId)
    {
        $defaultTabs = ['mostRecent', 'mostRead', 'mostCited', 'trending', 'highlight'];

        $tabsWithSequence = [];
        foreach ($defaultTabs as $index => $tabId) {
            $enabled = $this->plugin->getSetting($contextId, 'tabEnabled_' . $index);
            if ($enabled !== false) {
                $sequence = $this->plugin->getSetting($contextId, 'tabSequence_' . $index);
                $tabsWithSequence[] = [
                    'id' => $tabId,
                    'sequence' => $sequence !== null ? $sequence : $index + 1
                ];
            }
        }

        usort($tabsWithSequence, function ($firstTab, $secondTab) {
            return $firstTab['sequence'] - $secondTab['sequence'];
        });

        return array_map(function ($tab) {
            return $tab['id'];
        }, $tabsWithSequence);
    }
}

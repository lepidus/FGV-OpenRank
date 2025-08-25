<?php

class HookCallback
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function setupRankingPluginAPIHandler(string $hookname, Request $request)
    {
        $router = $request->getRouter();
        if (!($router instanceof \APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/rankingPlugin')) {
            $this->plugin->import('api.v1.rankingPlugin.RankingPluginHandler');
            $handler = new RankingPluginHandler();
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

        $rankingPluginApiBaseUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $context->getPath(),
            'rankingPlugin/'
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

        $rankingPluginJavaScriptVariables = [
            'currentLocale' => AppLocale::getLocale(),
            'primaryLocale' => AppLocale::getPrimaryLocale(),
            'rankingTemplate' => $templateMgr->fetch(
                $this->plugin->getTemplateResource('ranking.tpl')
            ),
            'rankingPluginApiBaseUrl' => $rankingPluginApiBaseUrl,
            'itemsPerTab' => $itemsPerTab,
            'itemsPerPage' => $itemsPerPage,
            'tabSettings' => $tabSpecificSettings,
            'previousPageLabel' => "<",
            'nextPageLabel' => ">",
            'mostRecentFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostRecentFailed'
            ),
            'mostReadFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostReadFailed'
            ),
            'mostCitedFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.mostCitedFailed'
            ),
            'trendingFailedMessage' => __(
                'plugins.generic.rankingPlugin.tabs.trendingFailed'
            ),
            'noPublicationsFoundMessage' => __(
                'plugins.generic.rankingPlugin.NoPublicationsFound'
            ),
        ];

        $this->loadResources(
            $templateMgr,
            $request,
            $rankingPluginJavaScriptVariables
        );

        return false;
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
        if ($component == 'plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridHandler') {
            return true;
        }
        return false;
    }


    private function loadResources($templateMgr, $request, $rankingPluginJavaScriptVariables)
    {
        $templateMgr->addJavaScript(
            'AppData',
            'app = ' . json_encode($rankingPluginJavaScriptVariables) . ';',
            ['inline' => true]
        );

        $templateMgr->addJavaScript(
            'rankingPluginScript',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/insertRankingTemplate.js',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'rankingPluginStyles',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/styles/ranking.css',
            ['priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addStyleSheet(
            'rankingPluginPaginationStyles',
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

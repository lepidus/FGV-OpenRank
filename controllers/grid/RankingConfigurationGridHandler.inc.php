<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.core.JSONMessage');
import('plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridCellProvider');
import('plugins.generic.rankingPlugin.controllers.grid.form.RankingCustomizationForm');

class RankingConfigurationGridHandler extends GridHandler
{
    private $contextId;

    private $currentGridData;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            array(ROLE_ID_MANAGER),
            array(
                'fetchGrid',
                'fetchCategory',
                'fetchRow',
                'editTab',
                'updateTab',
                'saveSequence',
                'saveTabSetting',
                'clearCache'
            )
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $context = $request->getContext();
        $this->contextId = $context->getId();

        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_PKP_SUBMISSION
        );

        $cellProvider = new RankingConfigurationGridCellProvider();

        $columnsInfo = [
            1 => [
                'id' => 'enabled',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.enabled',
                'template' => 'controllers/grid/common/cell/selectStatusCell.tpl'
            ],
            2 => [
                'id' => 'defaultTitle',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.defaultTitle',
                'template' => null
            ],
            3 => [
                'id' => 'customTitle',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.customTitle',
                'template' => null
            ],
            4 => [
                'id' => 'customDescription',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.customDescription',
                'template' => null
            ],
        ];

        foreach ($columnsInfo as $columnInfo) {
            $this->addColumn(
                new GridColumn(
                    $columnInfo['id'],
                    $columnInfo['title'],
                    null,
                    $columnInfo['template'],
                    $cellProvider
                )
            );
        }

        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.LinkAction');
        import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

        $this->addAction(
            new LinkAction(
                'clearCache',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    __('plugins.generic.rankingPlugin.configuration.clearCacheConfirm'),
                    __('plugins.generic.rankingPlugin.configuration.clearCache'),
                    $request->url(null, null, 'clearCache', null, array(
                        'csrfToken' => $request->getSession()->getCSRFToken()
                    ))
                ),
                __('plugins.generic.rankingPlugin.configuration.clearCache'),
                'delete'
            )
        );
    }

    public function editTab($args, $request)
    {
        $tabId = isset($args['tabId']) ? $args['tabId'] : null;
        $context = $request->getContext();
        $this->setupTemplate($request);

        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        $rankingCustomizationForm = new RankingCustomizationForm(
            $plugin,
            $context->getId(),
            $tabId
        );
        $rankingCustomizationForm->initData();
        return new JSONMessage(true, $rankingCustomizationForm->fetch($request));
    }

    public function updateTab($args, $request)
    {
        $tabId = isset($args['tabId']) ? $args['tabId'] : null;
        $context = $request->getContext();
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        $rankingCustomizationForm = new RankingCustomizationForm(
            $plugin,
            $context->getId(),
            $tabId
        );
        $rankingCustomizationForm->readInputData();
        if ($rankingCustomizationForm->validate()) {
            $rankingCustomizationForm->execute();
            return new JSONMessage(true);
        } else {
            return new JSONMessage(
                false,
                $rankingCustomizationForm->fetch($request)
            );
        }
    }

    public function saveTabSetting($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $rowId = (string) $request->getUserVar('rowId');
        $settingValue = (bool) $request->getUserVar('value');
        $context = $request->getContext();
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        if (!isset($rowId)) {
            return new JSONMessage(false);
        }

        if (!$this->currentGridData) {
            $this->loadData($request, array());
        }

        $rowIndex = (int) $rowId;
        if (!isset($this->currentGridData[$rowIndex]['id'])) {
            return new JSONMessage(false);
        }

        $tabId = $this->currentGridData[$rowIndex]['id'];
        $tabIndex = $this->getTabIndex($tabId);
        $settingName = 'tabEnabled_' . $tabIndex;

        $plugin->updateSetting($context->getId(), $settingName, $settingValue);

        import('classes.notification.NotificationManager');
        $notificationManager = new NotificationManager();
        $user = $request->getUser();
        $notificationManager->createTrivialNotification(
            $user->getId(),
            NOTIFICATION_TYPE_SUCCESS,
            array(
                'contents' => __(
                    'form.saved'
                )
            )
        );

        return DAO::getDataChangedEvent($rowId);
    }

    public function clearCache($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $context = $request->getContext();
        $contextId = $context->getId();

        $cacheManager = CacheManager::getManager();

        $caches = [
            $cacheManager->getFileCache($contextId, 'most_recent_submissions', [$this, 'cacheDismiss']),
            $cacheManager->getFileCache($contextId, 'most_read_submissions', [$this, 'cacheDismiss']),
            $cacheManager->getFileCache($contextId, 'most_cited_dois', [$this, 'cacheDismiss']),
            $cacheManager->getFileCache($contextId, 'trending_submissions', [$this, 'cacheDismiss'])
        ];

        foreach ($caches as $cache) {
            $cache->flush();
        }

        return new JSONMessage(true);
    }

    protected function loadData($request, $filter)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        $locale = AppLocale::getLocale();
        $defaultTabs = [
            [
                'id' => 'mostRecent',
                'label' => __(
                    "plugins.generic.rankingPlugin.tabs.mostRecent.defaultTitle"
                ),
                'customTitle' => $this->getLocalizedSetting(
                    $plugin,
                    'customTitle_mostRecent',
                    $locale
                ),
                'customDescription' => $this->getLocalizedSetting(
                    $plugin,
                    'customDescription_mostRecent',
                    $locale
                ),
                'enabled' => $plugin->getSetting($this->contextId, 'tabEnabled_0') !== false,
            ],
            [
                'id' => 'mostRead',
                'label' => __(
                    "plugins.generic.rankingPlugin.tabs.mostRead.defaultTitle"
                ),
                'customTitle' => $this->getLocalizedSetting(
                    $plugin,
                    'customTitle_mostRead',
                    $locale
                ),
                'customDescription' => $this->getLocalizedSetting(
                    $plugin,
                    'customDescription_mostRead',
                    $locale
                ),
                'enabled' => $plugin->getSetting($this->contextId, 'tabEnabled_1') !== false,
            ],
            [
                'id' => 'mostCited',
                'label' => __(
                    "plugins.generic.rankingPlugin.tabs.mostCited.defaultTitle"
                ),
                'customTitle' => $this->getLocalizedSetting(
                    $plugin,
                    'customTitle_mostCited',
                    $locale
                ),
                'customDescription' => $this->getLocalizedSetting(
                    $plugin,
                    'customDescription_mostCited',
                    $locale
                ),
                'enabled' => $plugin->getSetting($this->contextId, 'tabEnabled_2') !== false,
            ],
            [
                'id' => 'trending',
                'label' => __(
                    "plugins.generic.rankingPlugin.tabs.trending.defaultTitle"
                ),
                'customTitle' => $this->getLocalizedSetting(
                    $plugin,
                    'customTitle_trending',
                    $locale
                ),
                'customDescription' => $this->getLocalizedSetting(
                    $plugin,
                    'customDescription_trending',
                    $locale
                ),
                'enabled' => $plugin->getSetting($this->contextId, 'tabEnabled_3') !== false,
            ],
            [
                'id' => 'highlight',
                'label' => __(
                    "plugins.generic.rankingPlugin.tabs.highlight.defaultTitle"
                ),
                'customTitle' => $this->getLocalizedSetting(
                    $plugin,
                    'customTitle_highlight',
                    $locale
                ),
                'customDescription' => $this->getLocalizedSetting(
                    $plugin,
                    'customDescription_highlight',
                    $locale
                ),
                'enabled' => $plugin->getSetting($this->contextId, 'tabEnabled_4') !== false,
            ]
        ];

        usort($defaultTabs, [$this, 'compareTabsBySequence']);

        $this->currentGridData = $defaultTabs;

        return $defaultTabs;
    }

    protected function getRowInstance()
    {
        import('plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridRow');
        return new RankingConfigurationGridRow();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
        return array(new OrderGridItemsFeature());
    }

    public function getDataElementSequence($row)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        if (is_array($row) && isset($row['id'])) {
            $tabIndex = $this->getTabIndex($row['id']);
        } else {
            $position = (int)$row;
            if (isset($this->currentGridData[$position]['id'])) {
                $tabIndex = $this->getTabIndex($this->currentGridData[$position]['id']);
            } else {
                $tabIndex = $position;
            }
        }

        $sequence = $plugin->getSetting(
            $this->getContextId(),
            'tabSequence_' . $tabIndex
        );

        return $sequence !== null ? (int)$sequence : $tabIndex + 1;
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        $position = (int)$rowId;
        if (isset($this->currentGridData[$position]['id'])) {
            $tabIndex = $this->getTabIndex($this->currentGridData[$position]['id']);
        } else {
            $tabIndex = $position;
        }

        $normalizedSequence = max(1, min(5, (int)$newSequence));

        $plugin->updateSetting(
            $this->getContextId(),
            'tabSequence_' . $tabIndex,
            $normalizedSequence
        );
    }

    public function saveSequence($args, $request)
    {
        $data = json_decode($request->getUserVar('data'));
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        if (!$this->currentGridData) {
            $this->loadData($request, array());
        }

        $gridElements = $this->getGridDataElements($request);

        $firstSeqValue = $this->getDataElementSequence(reset($gridElements));

        foreach ($gridElements as $rowId => $element) {
            $rowPosition = array_search($rowId, $data);
            if ($rowPosition !== false) {
                $newSequence = $firstSeqValue + $rowPosition;

                if (isset($this->currentGridData[(int)$rowId]['id'])) {
                    $tabIndex = $this->getTabIndex($this->currentGridData[(int)$rowId]['id']);
                } else {
                    $tabIndex = (int)$rowId;
                }

                $normalizedSequence = max(1, min(5, (int)$newSequence));

                $plugin->updateSetting(
                    $this->getContextId(),
                    'tabSequence_' . $tabIndex,
                    $normalizedSequence
                );

                $saved = $plugin->getSetting($this->getContextId(), 'tabSequence_' . $tabIndex);
            }
        }

        return new JSONMessage(true);
    }

    private function compareTabsBySequence($firstTab, $secondTab)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');

        $firstTabSequence = $this->getTabDisplaySequence($firstTab, $plugin);

        $secondTabSequence = $this->getTabDisplaySequence($secondTab, $plugin);

        return $firstTabSequence - $secondTabSequence;
    }

    private function getTabDisplaySequence($tab, $plugin)
    {
        $tabIndex = $this->getTabIndex($tab['id']);

        $customSequence = $plugin->getSetting(
            $this->getContextId(),
            'tabSequence_' . $tabIndex
        );

        return $customSequence !== null ? (int)$customSequence : $tabIndex + 1;
    }

    private function getContextId()
    {
        return $this->contextId;
    }

    private function getLocalizedSetting($plugin, $settingName, $locale)
    {
        $settings = $plugin->getSetting($this->getContextId(), $settingName);
        if (is_array($settings) && isset($settings[$locale])) {
            return $settings[$locale];
        }
        return null;
    }

    private function getTabIndex($tabId)
    {
        $tabIds = ['mostRecent', 'mostRead', 'mostCited', 'trending', 'highlight'];
        $index = array_search($tabId, $tabIds);
        return $index !== false ? $index : 0;
    }

    public function cacheDismiss()
    {
        return null;
    }
}

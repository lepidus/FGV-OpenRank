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
                'id' => 'defaultTitle',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.defaultTitle',
                'template' => null
            ],
            2 => [
                'id' => 'customTitle',
                'title' => 'plugins.generic.rankingPlugin.configuration.grid.'.
                    'column.customTitle',
                'template' => null
            ],
            3 => [
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
}

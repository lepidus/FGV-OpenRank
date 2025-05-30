<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridCellProvider');
import('plugins.generic.rankingPlugin.controllers.grid.form.RankingCustomizationForm');

class RankingConfigurationGridHandler extends GridHandler
{
    private $contextId;

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
            1 => ['id' => 'defaultTitle', 'title' => 'plugins.generic.rankingPlugin.configuration.grid.column.defaultTitle', 'template' => null],
            2 => ['id' => 'customTitle', 'title' => 'plugins.generic.rankingPlugin.configuration.grid.column.customTitle', 'template' => null],
            3 => ['id' => 'customDescription', 'title' => 'plugins.generic.rankingPlugin.configuration.grid.column.customDescription', 'template' => null],
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
        $rankingCustomizationForm = new RankingCustomizationForm($plugin, $context->getId(), $tabId);
        $rankingCustomizationForm->initData();
        return new JSONMessage(true, $rankingCustomizationForm->fetch($request));
    }

    public function updateTab($args, $request)
    {
        $tabId = isset($args['tabId']) ? $args['tabId'] : null;
        $context = $request->getContext();
        $plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        $rankingCustomizationForm = new RankingCustomizationForm($plugin, $context->getId(), $tabId);
        $rankingCustomizationForm->readInputData();
        if ($rankingCustomizationForm->validate()) {
            $rankingCustomizationForm->execute();
            return new JSONMessage(true);
        } else {
            return new JSONMessage(false, $rankingCustomizationForm->fetch($request));
        }
    }

    protected function loadData($request, $filter)
    {
        $defaultTabs = [
            [
                'id' => 'mostRecent',
                'label' => __("plugins.generic.rankingPlugin.tabs.mostRecent.defaultTitle")
            ],
            [
                'id' => 'mostRead',
                'label' => __("plugins.generic.rankingPlugin.tabs.mostRead.defaultTitle")
            ],
            [
                'id' => 'mostCited',
                'label' => __("plugins.generic.rankingPlugin.tabs.mostCited.defaultTitle")
            ],
            [
                'id' => 'trending',
                'label' => __("plugins.generic.rankingPlugin.tabs.trending.defaultTitle")
            ],
            [
                'id' => 'highlight',
                'label' => __("plugins.generic.rankingPlugin.tabs.highlight.defaultTitle")
            ]
        ];
        return $defaultTabs;
    }

    protected function getRowInstance()
    {
        import('plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridRow');
        return new RankingConfigurationGridRow();
    }


    private function getContextId()
    {
        return $this->contextId;
    }
}

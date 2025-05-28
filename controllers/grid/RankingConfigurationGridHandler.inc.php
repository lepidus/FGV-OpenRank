<?php

import('lib.pkp.classes.controllers.grid.GridHandler');

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

    private function getContextId()
    {
        return $this->contextId;
    }
}

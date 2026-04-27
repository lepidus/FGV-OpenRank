<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.GridColumn');
import('lib.pkp.classes.core.JSONMessage');
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
import('plugins.generic.rankingPlugin.controllers.grid.form.TrendingDoiForm');
import('plugins.generic.rankingPlugin.controllers.grid.TrendingDoisGridCellProvider');

class TrendingDoisGridHandler extends GridHandler
{
    public const SETTING_NAME = 'trendingDois_trending';

    private $contextId;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [ROLE_ID_MANAGER],
            [
                'fetchGrid', 'fetchRow',
                'addDoi', 'editDoi', 'updateDoi', 'deleteDoi',
                'saveSequence',
            ]
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

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON);

        $this->setTitle('plugins.generic.rankingPlugin.trendingDois.title');

        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'addDoi',
                new AjaxModal(
                    $router->url($request, null, null, 'addDoi'),
                    __('plugins.generic.rankingPlugin.trendingDois.add'),
                    'modal_add_item',
                    true
                ),
                __('plugins.generic.rankingPlugin.trendingDois.add'),
                'add_item'
            )
        );

        $this->addColumn(
            new GridColumn(
                'doi',
                'plugins.generic.rankingPlugin.trendingDois.doi',
                null,
                null,
                new TrendingDoisGridCellProvider()
            )
        );
    }

    protected function loadData($request, $filter)
    {
        $plugin = $this->getPlugin();
        $stored = $plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
        return self::buildGridData($stored);
    }

    protected function getRowInstance()
    {
        import('plugins.generic.rankingPlugin.controllers.grid.TrendingDoisGridRow');
        return new TrendingDoisGridRow();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
        return [new OrderGridItemsFeature()];
    }

    public function getDataElementSequence($row)
    {
        if (is_array($row) && isset($row['sequence'])) {
            return (int) $row['sequence'];
        }
        return 0;
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
    }

    public function saveSequence($args, $request)
    {
        $data = json_decode($request->getUserVar('data'));
        if (!is_array($data)) {
            $data = [];
        }

        $plugin = $this->getPlugin();
        $stored = $plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
        $reordered = self::reorderDois($stored, $data);
        $plugin->updateSetting($this->contextId, self::SETTING_NAME, $reordered);

        $this->refreshTrendingCache();

        return new JSONMessage(true);
    }

    public function addDoi($args, $request)
    {
        $this->setupTemplate($request);
        $form = new TrendingDoiForm($this->getPlugin(), $this->contextId);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function editDoi($args, $request)
    {
        $this->setupTemplate($request);
        $optionId = (string) $request->getUserVar('rowId');
        $form = new TrendingDoiForm($this->getPlugin(), $this->contextId, $optionId);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function updateDoi($args, $request)
    {
        $optionId = $request->getUserVar('rowId');
        $optionId = $optionId !== null && $optionId !== '' ? (string) $optionId : null;
        $form = new TrendingDoiForm($this->getPlugin(), $this->contextId, $optionId);
        $form->readInputData();
        if ($form->validate()) {
            $form->execute();
            $this->refreshTrendingCache();
            return DAO::getDataChangedEvent();
        }

        return new JSONMessage(true, $form->fetch($request));
    }

    public function deleteDoi($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $optionId = (string) $request->getUserVar('rowId');
        $plugin = $this->getPlugin();
        $stored = $plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
        $updated = self::removeDoi($stored, $optionId);
        $plugin->updateSetting($this->contextId, self::SETTING_NAME, $updated);

        $this->refreshTrendingCache();

        return DAO::getDataChangedEvent($optionId);
    }

    public static function buildGridData(array $storedDois): array
    {
        $rows = [];
        $position = 0;
        foreach ($storedDois as $optionId => $doi) {
            $rows[$optionId] = [
                'id' => $optionId,
                'doi' => $doi,
                'sequence' => $position++,
            ];
        }
        return $rows;
    }

    public static function reorderDois(array $storedDois, array $orderedIds): array
    {
        $result = [];
        foreach ($orderedIds as $id) {
            $id = (string) $id;
            if (isset($storedDois[$id])) {
                $result[$id] = $storedDois[$id];
            }
        }
        foreach ($storedDois as $id => $doi) {
            if (!isset($result[$id])) {
                $result[$id] = $doi;
            }
        }
        return $result;
    }

    public static function removeDoi(array $storedDois, string $optionId): array
    {
        unset($storedDois[$optionId]);
        return $storedDois;
    }

    protected function getPlugin()
    {
        return PluginRegistry::getPlugin('generic', 'rankingplugin');
    }

    protected function refreshTrendingCache(): void
    {
        import('plugins.generic.rankingPlugin.classes.cache.TrendingSubmissions');
        $plugin = $this->getPlugin();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($this->contextId);
        if (!$context) {
            return;
        }
        $limit = $plugin->getSetting($this->contextId, 'itemsPerTab_trending') ?? 4;
        $trending = new TrendingSubmissions();
        $trending->refreshCache($this->contextId, $context->getPath(), $limit);
    }
}

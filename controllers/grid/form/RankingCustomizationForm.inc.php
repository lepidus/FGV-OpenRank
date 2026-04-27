<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.rankingPlugin.lib.APIKeyEncryption.APIKeyEncryption');

class RankingCustomizationForm extends Form
{
    private const API_KEY_SETTING = 'altmetricsApiKey_trending';

    private $plugin;
    private $contextId;
    private $tabId;
    private $apiKeyEncryption;

    public function __construct($plugin, $contextId, $tabId = null, $apiKeyEncryption = null)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->tabId = $tabId;
        $this->apiKeyEncryption = $apiKeyEncryption;
        $this->addFormValidators();

        $template = 'customization/form.tpl';
        parent::__construct($plugin->getTemplateResource($template));
    }

    public function addFormValidators()
    {
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign('tabId', $this->tabId);

        $this->initData();
        $templateMgr->assign('customTitle', $this->getData('customTitle'));
        $templateMgr->assign('description', $this->getData('description'));
        $templateMgr->assign('itemsPerTab', $this->getData('itemsPerTab'));
        $templateMgr->assign('itemsPerPage', $this->getData('itemsPerPage'));

        if ($this->tabId === 'mostRead') {
            $templateMgr->assign('mostReadDays', $this->getData('mostReadDays'));
        }

        if ($this->tabId === 'highlight') {
            $templateMgr->assign(
                'highlightContent',
                $this->getData('highlightContent')
            );
        }

        if ($this->tabId === 'trending') {
            $templateMgr->assign('hasAltmetricsApiKey', (bool) $this->getData('hasAltmetricsApiKey'));
        }

        return parent::fetch($request);
    }

    public function readInputData()
    {
        $userVars = [
            'customTitle',
            'description',
            'itemsPerTab',
            'itemsPerPage',
        ];
        if ($this->tabId === 'mostRead') {
            $userVars[] = 'mostReadDays';
        }
        if ($this->tabId === 'highlight') {
            $userVars[] = 'highlightContent';
        }
        if ($this->tabId === 'trending') {
            $userVars = array_merge($userVars, $this->getApiKeyUserVars());
        }
        $this->readUserVars($userVars);
        parent::readInputData();
    }

    public function getApiKeyUserVars(): array
    {
        if ($this->tabId !== 'trending') {
            return [];
        }
        return ['altmetricsApiKey', 'removeAltmetricsApiKey'];
    }

    public function initData()
    {
        if ($this->tabId) {
            $customTitle = $this->plugin->getSetting(
                $this->contextId,
                "customTitle_{$this->tabId}"
            );
            $description = $this->plugin->getSetting(
                $this->contextId,
                "customDescription_{$this->tabId}"
            );
            $itemsPerTab = $this->plugin->getSetting(
                $this->contextId,
                "itemsPerTab_{$this->tabId}"
            ) ?? 4;
            $itemsPerPage = $this->plugin->getSetting(
                $this->contextId,
                "itemsPerPage_{$this->tabId}"
            ) ?? 4;

            $this->setData('customTitle', $customTitle);
            $this->setData('description', $description);
            $this->setData('itemsPerTab', $itemsPerTab);
            $this->setData('itemsPerPage', $itemsPerPage);

            if ($this->tabId === 'mostRead') {
                $mostReadDays = $this->plugin->getSetting(
                    $this->contextId,
                    "mostReadDays_{$this->tabId}"
                ) ?? 120;
                $this->setData('mostReadDays', $mostReadDays);
            }

            if ($this->tabId === 'highlight') {
                $highlightContent = $this->plugin->getSetting(
                    $this->contextId,
                    "highlightContent_{$this->tabId}"
                );
                $this->setData('highlightContent', $highlightContent);
            }

            if ($this->tabId === 'trending') {
                $storedKey = $this->plugin->getSetting($this->contextId, self::API_KEY_SETTING);
                $this->setData('hasAltmetricsApiKey', !empty($storedKey));
            }
        }
        parent::initData();
    }

    public function validate($callHooks = true)
    {
        if ($this->tabId === 'trending') {
            $this->validateTrendingApiKey();
        }
        return parent::validate($callHooks);
    }

    private function validateTrendingApiKey(): void
    {
        $apiKey = trim((string) $this->getData('altmetricsApiKey'));
        $remove = (bool) $this->getData('removeAltmetricsApiKey');
        if ($apiKey === '' || $remove) {
            return;
        }
        if (!$this->getApiKeyEncryption()->secretConfigExists()) {
            $this->addError(
                'altmetricsApiKey',
                __('plugins.generic.rankingPlugin.settings.altmetricsApiKey.secretMissing')
            );
            $this->addErrorField('altmetricsApiKey');
        }
    }

    public function execute(...$functionArgs)
    {
        $customTitle = $this->getData('customTitle');
        $description = $this->getData('description');
        $itemsPerTab = (int) $this->getData('itemsPerTab');
        $itemsPerPage = (int) $this->getData('itemsPerPage');

        if ($itemsPerTab < 1) {
            $itemsPerTab = 4;
        }
        if ($itemsPerPage < 1) {
            $itemsPerPage = 4;
        }

        if ($this->tabId) {
            $this->plugin->updateSetting(
                $this->contextId,
                "customTitle_{$this->tabId}",
                $customTitle,
                'object'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "customDescription_{$this->tabId}",
                $description,
                'object'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "itemsPerTab_{$this->tabId}",
                $itemsPerTab,
                'int'
            );

            $this->plugin->updateSetting(
                $this->contextId,
                "itemsPerPage_{$this->tabId}",
                $itemsPerPage,
                'int'
            );

            if ($this->tabId === 'mostRead') {
                $mostReadDays = (int) $this->getData('mostReadDays');
                if ($mostReadDays < 1) {
                    $mostReadDays = 120;
                }
                $this->plugin->updateSetting(
                    $this->contextId,
                    "mostReadDays_{$this->tabId}",
                    $mostReadDays,
                    'int'
                );
            }

            if ($this->tabId === 'highlight') {
                $highlightContent = $this->getData('highlightContent');
                $this->plugin->updateSetting(
                    $this->contextId,
                    "highlightContent_{$this->tabId}",
                    $highlightContent,
                    'object'
                );
            }

            if ($this->tabId === 'trending') {
                $this->executeTrendingApiKey();
            }

            $this->refreshCache($this->tabId, $this->contextId, $itemsPerTab);
        }

        parent::execute(...$functionArgs);
    }

    private function executeTrendingApiKey(): void
    {
        $apiKey = trim((string) $this->getData('altmetricsApiKey'));
        $remove = (bool) $this->getData('removeAltmetricsApiKey');

        if ($remove) {
            $this->plugin->updateSetting($this->contextId, self::API_KEY_SETTING, '', 'string');
            return;
        }

        if ($apiKey === '') {
            return;
        }

        $encryption = $this->getApiKeyEncryption();
        if (!$encryption->secretConfigExists()) {
            return;
        }

        $encrypted = $encryption->encryptString($apiKey);
        $this->plugin->updateSetting($this->contextId, self::API_KEY_SETTING, $encrypted, 'string');
    }

    private function getApiKeyEncryption(): APIKeyEncryption
    {
        if ($this->apiKeyEncryption === null) {
            $this->apiKeyEncryption = new APIKeyEncryption();
        }
        return $this->apiKeyEncryption;
    }

    protected function refreshCache($tabId, $contextId, $limit)
    {
        $request = Application::get()->getRequest();

        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($contextId);
        $issn = $context->getData('onlineIssn') ?: $context->getData('printIssn');

        switch ($tabId) {
            case 'mostRecent':
                import('plugins.generic.rankingPlugin.classes.cache.MostRecent');
                $cache = new MostRecent();
                $cache->refreshCache($context, $request, $limit);
                break;
            case 'mostRead':
                import('plugins.generic.rankingPlugin.classes.cache.MostRead');
                $mostRead = new MostRead($this->plugin);
                $mostRead->refreshCache($context, $request, $limit);
                break;
            case 'mostCited':
                import('plugins.generic.rankingPlugin.classes.cache.MostCitedDois');
                $mostCited = new MostCitedDois();
                $mostCited->refreshCache($contextId, $issn, $limit);
                break;
            case 'trending':
                import('plugins.generic.rankingPlugin.classes.cache.TrendingSubmissions');
                $trending = new TrendingSubmissions();
                $trending->refreshCache($contextId, $context->getPath(), $limit);
                break;
            case 'highlight':
                import('plugins.generic.rankingPlugin.classes.cache.BestAltmetricsScoreDois');
                $cache = new BestAltmetricsScoreDois();
                $cache->refreshCache($contextId, $issn, $limit);
                break;
            default:
                throw new Exception('Invalid tab ID');
                break;
        }
    }
}

<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\cache\MostCitedDois;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRead;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRecent;
use APP\plugins\generic\rankingPlugin\classes\cache\TrendingSubmissions;
use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use Exception;

class TabSettings
{
    public const API_KEY_SETTING = 'altmetricsApiKey_trending';

    private $dataEncryption;
    private $altmetricsClient;

    public function __construct(
        private $plugin,
        private int $contextId,
        private string $tabId,
        $dataEncryption = null,
        $altmetricsClient = null
    ) {
        $this->dataEncryption = $dataEncryption;
        $this->altmetricsClient = $altmetricsClient;
    }

    public function getValues(): array
    {
        $rankingTabs = new RankingTabs($this->plugin, $this->contextId);
        $values = [
            'customTitle' => $this->plugin->getSetting($this->contextId, "customTitle_{$this->tabId}"),
            'description' => $this->plugin->getSetting($this->contextId, "customDescription_{$this->tabId}"),
            'itemsPerTab' => $rankingTabs->getItemsPerTab($this->tabId),
            'itemsPerPage' => $rankingTabs->getItemsPerPage($this->tabId),
        ];

        if ($this->tabId === RankingTabs::MOST_READ) {
            $values['mostReadDays'] = $rankingTabs->getMostReadDays();
        }

        if ($this->tabId === RankingTabs::HIGHLIGHT) {
            $values['highlightContent'] = $this->plugin->getSetting($this->contextId, "highlightContent_{$this->tabId}");
        }

        if ($this->tabId === RankingTabs::TRENDING) {
            $values['hasAltmetricsApiKey'] = $this->hasApiKey();
        }

        return $values;
    }

    public function hasApiKey(): bool
    {
        return !empty($this->plugin->getSetting($this->contextId, self::API_KEY_SETTING));
    }

    public function validate(array $input): array
    {
        if ($this->tabId !== RankingTabs::TRENDING) {
            return [];
        }

        $apiKey = trim((string) ($input['altmetricsApiKey'] ?? ''));
        if ($apiKey === '' || $this->isRemovingApiKey($input)) {
            return [];
        }

        $issn = $this->getContextIssn();
        if (empty($issn)) {
            return [];
        }

        try {
            $this->getAltmetricsClient()->fetchBestScoreSubmissions($issn, 1, $apiKey);
        } catch (Exception $error) {
            error_log($error->getMessage());
            return ['altmetricsApiKey' => [__('plugins.generic.rankingPlugin.settings.altmetricsApiKey.invalid')]];
        }

        return [];
    }

    public function save(array $input): void
    {
        $itemsPerTab = $this->toPositiveInt($input['itemsPerTab'] ?? null, RankingTabs::DEFAULT_ITEMS);

        $this->plugin->updateSetting($this->contextId, "customTitle_{$this->tabId}", $input['customTitle'] ?? null, 'object');
        $this->plugin->updateSetting($this->contextId, "customDescription_{$this->tabId}", $input['description'] ?? null, 'object');
        $this->plugin->updateSetting($this->contextId, "itemsPerTab_{$this->tabId}", $itemsPerTab, 'int');
        $this->plugin->updateSetting(
            $this->contextId,
            "itemsPerPage_{$this->tabId}",
            $this->toPositiveInt($input['itemsPerPage'] ?? null, RankingTabs::DEFAULT_ITEMS),
            'int'
        );

        if ($this->tabId === RankingTabs::MOST_READ) {
            $this->plugin->updateSetting(
                $this->contextId,
                "mostReadDays_{$this->tabId}",
                $this->toPositiveInt($input['mostReadDays'] ?? null, RankingTabs::DEFAULT_MOST_READ_DAYS),
                'int'
            );
        }

        if ($this->tabId === RankingTabs::HIGHLIGHT) {
            $this->plugin->updateSetting($this->contextId, "highlightContent_{$this->tabId}", $input['highlightContent'] ?? null, 'object');
        }

        if ($this->tabId === RankingTabs::TRENDING) {
            $this->saveApiKey($input);
        }

        $this->refreshCache($itemsPerTab);
    }

    private function saveApiKey(array $input): void
    {
        if ($this->isRemovingApiKey($input)) {
            $this->plugin->updateSetting($this->contextId, self::API_KEY_SETTING, '', 'string');
            return;
        }

        $apiKey = trim((string) ($input['altmetricsApiKey'] ?? ''));
        if ($apiKey === '') {
            return;
        }

        $this->plugin->updateSetting(
            $this->contextId,
            self::API_KEY_SETTING,
            $this->getDataEncryption()->encryptString($apiKey),
            'string'
        );
    }

    private function isRemovingApiKey(array $input): bool
    {
        return filter_var($input['removeAltmetricsApiKey'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function toPositiveInt($value, int $default): int
    {
        $value = (int) $value;
        return $value < 1 ? $default : $value;
    }

    protected function getContextIssn(): ?string
    {
        $context = app()->get('context')->get($this->contextId);
        if (!$context) {
            return null;
        }
        return $context->getData('onlineIssn') ?: $context->getData('printIssn');
    }

    protected function refreshCache(int $limit): void
    {
        $request = Application::get()->getRequest();
        $context = app()->get('context')->get($this->contextId);
        $issn = $this->getContextIssn();

        try {
            switch ($this->tabId) {
                case RankingTabs::MOST_RECENT:
                    (new MostRecent())->refreshCache($context, $request, $limit);
                    break;
                case RankingTabs::MOST_READ:
                    (new MostRead($this->plugin))->refreshCache($context, $request, $limit);
                    break;
                case RankingTabs::MOST_CITED:
                    if ($issn) {
                        (new MostCitedDois())->refreshCache($this->contextId, $issn, $limit);
                    }
                    break;
                case RankingTabs::TRENDING:
                    (new TrendingSubmissions($this->plugin))->refreshCache($this->contextId, $context->getPath(), $limit);
                    break;
            }
        } catch (Exception $error) {
            error_log($error->getMessage());
        }
    }

    private function getAltmetricsClient()
    {
        return $this->altmetricsClient ??= new Altmetrics(Application::get()->getHttpClient());
    }

    private function getDataEncryption(): DataEncryption
    {
        return $this->dataEncryption ??= new DataEncryption();
    }
}

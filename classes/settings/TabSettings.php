<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\plugins\generic\rankingPlugin\classes\RankingTabs;

class TabSettings
{
    private AltmetricsApiKey $altmetricsApiKey;

    public function __construct(
        private $plugin,
        private int $contextId,
        private string $tabId,
        ?AltmetricsApiKey $altmetricsApiKey = null
    ) {
        $this->altmetricsApiKey = $altmetricsApiKey ?? new AltmetricsApiKey($plugin, $contextId);
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
            $values['hasAltmetricsApiKey'] = $this->altmetricsApiKey->has();
        }

        return $values;
    }

    public function validate(array $input, ?string $altmetricsIssn): array
    {
        if ($this->tabId !== RankingTabs::TRENDING) {
            return [];
        }

        return $this->altmetricsApiKey->validate($input, $altmetricsIssn);
    }

    public function save(array $input): void
    {
        $this->plugin->updateSetting($this->contextId, "customTitle_{$this->tabId}", $input['customTitle'] ?? null, 'object');
        $this->plugin->updateSetting($this->contextId, "customDescription_{$this->tabId}", $input['description'] ?? null, 'object');
        $this->plugin->updateSetting(
            $this->contextId,
            "itemsPerTab_{$this->tabId}",
            $this->toPositiveInt($input['itemsPerTab'] ?? null, RankingTabs::DEFAULT_ITEMS),
            'int'
        );
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
            $this->altmetricsApiKey->save($input);
        }
    }

    private function toPositiveInt($value, int $default): int
    {
        $value = (int) $value;
        return $value < 1 ? $default : $value;
    }
}

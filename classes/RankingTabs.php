<?php

namespace APP\plugins\generic\rankingPlugin\classes;

use PKP\plugins\Plugin;

class RankingTabs
{
    public const MOST_RECENT = 'mostRecent';
    public const MOST_READ = 'mostRead';
    public const MOST_CITED = 'mostCited';
    public const TRENDING = 'trending';
    public const HIGHLIGHT = 'highlight';

    public const DEFAULT_ITEMS = 4;
    public const DEFAULT_MOST_READ_DAYS = 120;

    public function __construct(
        private Plugin $plugin,
        private int $contextId
    ) {
    }

    public static function getAll(): array
    {
        return [
            self::MOST_RECENT,
            self::MOST_READ,
            self::MOST_CITED,
            self::TRENDING,
            self::HIGHLIGHT,
        ];
    }

    public static function isValid(?string $tabId): bool
    {
        return in_array($tabId, self::getAll(), true);
    }

    public static function getIndex(string $tabId): int
    {
        $index = array_search($tabId, self::getAll(), true);
        return $index !== false ? $index : 0;
    }

    public function isEnabled(string $tabId): bool
    {
        return $this->plugin->getSetting($this->contextId, 'tabEnabled_' . self::getIndex($tabId)) !== false;
    }

    public function getSequence(string $tabId): int
    {
        $index = self::getIndex($tabId);
        $sequence = $this->plugin->getSetting($this->contextId, 'tabSequence_' . $index);

        return $sequence !== null ? (int) $sequence : $index + 1;
    }

    public function getOrdered(): array
    {
        $tabs = self::getAll();
        usort($tabs, fn (string $first, string $second) => $this->getSequence($first) - $this->getSequence($second));

        return $tabs;
    }

    public function getOrderedEnabled(): array
    {
        return array_values(array_filter($this->getOrdered(), fn (string $tabId) => $this->isEnabled($tabId)));
    }

    public function save(array $tabs): void
    {
        $sequence = 1;
        foreach ($tabs as $tab) {
            $tabId = $tab['id'] ?? null;
            if (!self::isValid($tabId)) {
                continue;
            }

            $index = self::getIndex($tabId);
            $this->plugin->updateSetting($this->contextId, 'tabSequence_' . $index, $sequence++, 'int');
            $this->plugin->updateSetting(
                $this->contextId,
                'tabEnabled_' . $index,
                filter_var($tab['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'bool'
            );
        }
    }

    public function getItemsPerTab(string $tabId): int
    {
        return $this->getPositiveInt("itemsPerTab_{$tabId}", self::DEFAULT_ITEMS);
    }

    public function getItemsPerPage(string $tabId): int
    {
        return $this->getPositiveInt("itemsPerPage_{$tabId}", self::DEFAULT_ITEMS);
    }

    public function getMostReadDays(): int
    {
        return $this->getPositiveInt('mostReadDays_' . self::MOST_READ, self::DEFAULT_MOST_READ_DAYS);
    }

    public function getLocalizedSetting(string $settingName, string $locale, string $primaryLocale): string
    {
        return self::localize($this->plugin->getSetting($this->contextId, $settingName), $locale, $primaryLocale);
    }

    public static function localize($data, string $locale, string $primaryLocale): string
    {
        if (empty($data)) {
            return '';
        }

        if (is_string($data)) {
            return $data;
        }

        if (!is_array($data)) {
            return '';
        }

        if (!empty($data[$locale])) {
            return $data[$locale];
        }

        if (!empty($data[$primaryLocale])) {
            return $data[$primaryLocale];
        }

        foreach ($data as $value) {
            if (!empty($value)) {
                return $value;
            }
        }

        return '';
    }

    private function getPositiveInt(string $settingName, int $default): int
    {
        $value = (int) $this->plugin->getSetting($this->contextId, $settingName);
        return $value > 0 ? $value : $default;
    }
}

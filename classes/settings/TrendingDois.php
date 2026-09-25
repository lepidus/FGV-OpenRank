<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\facades\Repo;

class TrendingDois
{
    public const SETTING_NAME = 'trendingDois_trending';
    private const DOI_REGEX = '/^10\.\d{4,}\/\S+$/';

    private $submissionRepository;

    public function __construct(
        private $plugin,
        private int $contextId,
        $submissionRepository = null
    ) {
        $this->submissionRepository = $submissionRepository;
    }

    public function getStored(): array
    {
        return $this->plugin->getSetting($this->contextId, self::SETTING_NAME) ?: [];
    }

    public function has(string $doiId): bool
    {
        return array_key_exists($doiId, $this->getStored());
    }

    public function getItems(): array
    {
        return self::buildItems($this->getStored());
    }

    public function validate(string $doi): ?string
    {
        $doi = trim($doi);
        if (!preg_match(self::DOI_REGEX, $doi)) {
            return __('plugins.generic.rankingPlugin.trendingDois.invalidDoi');
        }

        if (!$this->getSubmissionRepository()->getByDoi($doi, $this->contextId)) {
            return __('plugins.generic.rankingPlugin.trendingDois.doiNotInJournal');
        }

        return null;
    }

    public function save(string $doi, ?string $doiId = null): string
    {
        $dois = $this->getStored();

        if ($doiId === null || !isset($dois[$doiId])) {
            do {
                $doiId = uniqid();
            } while (isset($dois[$doiId]));
        }

        $dois[$doiId] = trim($doi);
        $this->plugin->updateSetting($this->contextId, self::SETTING_NAME, $dois);

        return $doiId;
    }

    public function remove(string $doiId): void
    {
        $this->plugin->updateSetting($this->contextId, self::SETTING_NAME, self::removeDoi($this->getStored(), $doiId));
    }

    public function reorder(array $orderedIds): void
    {
        $this->plugin->updateSetting($this->contextId, self::SETTING_NAME, self::reorderDois($this->getStored(), $orderedIds));
    }

    public static function buildItems(array $storedDois): array
    {
        $items = [];
        foreach ($storedDois as $doiId => $doi) {
            $items[] = ['id' => (string) $doiId, 'doi' => $doi];
        }
        return $items;
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

    public static function removeDoi(array $storedDois, string $doiId): array
    {
        unset($storedDois[$doiId]);
        return $storedDois;
    }

    private function getSubmissionRepository()
    {
        return $this->submissionRepository ??= Repo::submission();
    }
}

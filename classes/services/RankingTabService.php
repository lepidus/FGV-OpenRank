<?php

namespace APP\plugins\generic\rankingPlugin\classes\services;

use APP\plugins\generic\rankingPlugin\classes\cache\MostCitedDois;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRead;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRecent;
use APP\plugins\generic\rankingPlugin\classes\cache\TrendingSubmissions;
use APP\plugins\generic\rankingPlugin\classes\factory\RankingSubmission;
use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;

class RankingTabService
{
    private RankingTabs $rankingTabs;
    private RankingSubmission $rankingSubmission;

    public function __construct(
        private $plugin,
        private $context,
        $request
    ) {
        $this->rankingTabs = new RankingTabs($plugin, $context->getId());
        $this->rankingSubmission = new RankingSubmission($context->getId(), $context->getPath(), $request);
    }

    public function getSubmissions(string $tabId): array
    {
        $limit = $this->rankingTabs->getItemsPerTab($tabId);

        switch ($tabId) {
            case RankingTabs::MOST_RECENT:
                return $this->getMostRecent()->getMostRecentSubmissions($limit);
            case RankingTabs::MOST_READ:
                return $this->getMostRead()->getMostReadSubmissions($limit, $this->rankingTabs->getMostReadDays());
            case RankingTabs::MOST_CITED:
                $issn = $this->getCrossrefIssn();
                if (!$issn) {
                    return [];
                }
                $dois = (new MostCitedDois())->getMostCitedSubmissionsDois($this->context->getId(), $issn, $limit);
                return $this->rankingSubmission->getMostCited($dois);
            case RankingTabs::TRENDING:
                return $this->getTrending()->getTrendingSubmissions($limit);
        }

        return [];
    }

    public function refresh(string $tabId): void
    {
        $limit = $this->rankingTabs->getItemsPerTab($tabId);

        switch ($tabId) {
            case RankingTabs::MOST_RECENT:
                $this->getMostRecent()->refreshCache($limit);
                break;
            case RankingTabs::MOST_READ:
                $this->getMostRead()->refreshCache($limit, $this->rankingTabs->getMostReadDays());
                break;
            case RankingTabs::MOST_CITED:
                $issn = $this->getCrossrefIssn();
                if ($issn) {
                    (new MostCitedDois())->refreshCache($this->context->getId(), $issn, $limit);
                }
                break;
            case RankingTabs::TRENDING:
                $this->getTrending()->refreshCache($limit);
                break;
        }
    }

    public function refreshAll(): void
    {
        foreach (RankingTabs::getAll() as $tabId) {
            $this->refresh($tabId);
        }
    }

    public function getAltmetricsIssn(): ?string
    {
        return $this->context->getData('printIssn') ?: $this->context->getData('onlineIssn') ?: null;
    }

    private function getCrossrefIssn(): ?string
    {
        return $this->context->getData('onlineIssn') ?: $this->context->getData('printIssn') ?: null;
    }

    private function getMostRecent(): MostRecent
    {
        return new MostRecent($this->context->getId(), $this->rankingSubmission);
    }

    private function getMostRead(): MostRead
    {
        return new MostRead($this->context->getId(), $this->rankingSubmission);
    }

    private function getTrending(): TrendingSubmissions
    {
        $contextId = $this->context->getId();

        return new TrendingSubmissions(
            $contextId,
            $this->getAltmetricsIssn(),
            new AltmetricsApiKey($this->plugin, $contextId),
            new TrendingDois($this->plugin, $contextId),
            $this->rankingSubmission
        );
    }
}

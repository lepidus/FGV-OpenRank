<?php

namespace APP\plugins\generic\rankingPlugin\classes\factory;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Collector;
use APP\submission\Submission;
use PKP\security\Role;
use PKP\statistics\PKPStatisticsHelper;
use PKP\userGroup\UserGroup;

class RankingSubmission
{
    public function __construct(
        private int $contextId,
        private string $contextPath,
        private $request
    ) {
    }

    public function getMostRecent(int $limit): array
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_DESC)
            ->limit($limit)
            ->getMany();

        $mostRecentSubmissions = [];
        foreach ($submissions as $submission) {
            $mostRecentSubmissions[] = $this->formatSubmissionData($submission);
        }
        return $mostRecentSubmissions;
    }

    public function getMostRead(int $limit, int $mostReadDays): array
    {
        $topSubmissions = app()->get('publicationStats')->getTotals([
            'contextIds' => [$this->contextId],
            'dateStart' => date('Y-m-d', strtotime('-' . $mostReadDays . ' days')),
            'dateEnd' => date('Y-m-d'),
            'count' => $limit + 1,
            'offset' => 0,
        ]);

        $mostReadSubmissions = [];
        foreach ($topSubmissions as $topSubmission) {
            $submission = Repo::submission()->get((int) $topSubmission->{PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID});

            if ($submission && $submission->getData('status') == Submission::STATUS_PUBLISHED) {
                $mostReadSubmissions[] = $this->formatSubmissionData(
                    $submission,
                    ['metric' => (int) $topSubmission->{PKPStatisticsHelper::STATISTICS_METRIC}]
                );
            }
        }

        return $mostReadSubmissions;
    }

    public function getMostCited(array $dois): array
    {
        $mostCitedSubmissions = [];
        foreach ($dois as $doi) {
            $submission = Repo::submission()->getByDoi($doi, $this->contextId);
            if ($submission) {
                $mostCitedSubmissions[] = $this->formatSubmissionData($submission);
            }
        }
        return $mostCitedSubmissions;
    }

    public function getTrending(array $dois): array
    {
        $trendingSubmissions = [];
        foreach ($dois as $doi) {
            $submission = Repo::submission()->getByDoi($doi, $this->contextId);
            if ($submission) {
                $trendingSubmissions[] = $this->formatSubmissionData($submission, ['doi' => $doi]);
            }
        }

        return $trendingSubmissions;
    }

    private function formatSubmissionData(Submission $submission, array $additionalData = []): array
    {
        $publication = $submission->getCurrentPublication();

        $submissionData = [
            'submissionUrl' => $this->request->getDispatcher()->url(
                $this->request,
                Application::ROUTE_PAGE,
                $this->contextPath,
                'article',
                'view',
                [$submission->getBestId()]
            ),
            'title' => $publication->getData('title'),
            'authorString' => $publication->getAuthorString(self::getAuthorUserGroups($this->contextId)),
            'datePublished' => $publication->getData('datePublished'),
        ];

        $issue = Repo::issue()->getBySubmissionId($submission->getId());

        if ($publication->getLocalizedData('coverImage')) {
            $submissionData['coverImage'] = $publication->getLocalizedData('coverImage');
            $submissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($this->contextId);
        } elseif ($issue && $issue->getLocalizedCoverImage()) {
            $submissionData['coverImage'] = [
                'name' => $issue->getLocalizedCoverImage(),
                'coverImageUrl' => $issue->getLocalizedCoverImageUrl(),
            ];
        }

        return array_merge($submissionData, $additionalData);
    }

    private static function getAuthorUserGroups(int $contextId)
    {
        static $userGroups = [];

        return $userGroups[$contextId] ??= UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])
            ->withContextIds([$contextId])
            ->get();
    }
}

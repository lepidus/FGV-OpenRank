<?php

namespace APP\plugins\generic\rankingPlugin\classes\factory;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Collector;
use APP\submission\Submission;
use Exception;
use PKP\security\Role;
use PKP\statistics\PKPStatisticsHelper;
use PKP\userGroup\UserGroup;

class RankingSubmission
{
    public static function get($functionName, $params = [])
    {
        $availableFunctionNames = [
            'mostRecent' => 'getMostRecent',
            'mostRead' => 'getMostRead',
            'mostCited' => 'getMostCited',
            'trending' => 'getTrending',
        ];
        if (!array_key_exists($functionName, $availableFunctionNames)) {
            throw new Exception('Invalid argument provided');
        }

        return self::{$availableFunctionNames[$functionName]}($params);
    }

    public static function getMostRecent($params)
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$params['contextId']])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_DESC)
            ->limit($params['limit'])
            ->getMany();

        $mostRecentSubmissionsData = [];
        foreach ($submissions as $submission) {
            $mostRecentSubmissionsData[] = self::formatSubmissionData(
                $submission,
                $params['request'],
                $params['contextId'],
                $params['contextPath']
            );
        }
        return $mostRecentSubmissionsData;
    }

    public static function getMostRead($params)
    {
        $contextId = $params['contextId'];
        $mostReadDays = $params['mostReadDays'] ?? 120;

        $topSubmissions = app()->get('publicationStats')->getTotals([
            'contextIds' => [$contextId],
            'dateStart' => date('Y-m-d', strtotime('-' . $mostReadDays . ' days')),
            'dateEnd' => date('Y-m-d'),
            'count' => $params['limit'] + 1,
            'offset' => 0,
        ]);

        $mostReadSubmissions = [];
        foreach ($topSubmissions as $topSubmission) {
            $submission = Repo::submission()->get((int) $topSubmission->{PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID});

            if ($submission && $submission->getData('status') == Submission::STATUS_PUBLISHED) {
                $mostReadSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $params['request'],
                    $contextId,
                    $params['contextPath'],
                    ['metric' => (int) $topSubmission->{PKPStatisticsHelper::STATISTICS_METRIC}]
                );
            }
        }

        return $mostReadSubmissions;
    }

    public static function getMostCited($params)
    {
        $contextId = $params['contextId'];
        $mostCitedSubmissions = [];
        foreach ($params['mostCitedDois'] as $doi) {
            $submission = Repo::submission()->getByDoi($doi, $contextId);
            if ($submission) {
                $mostCitedSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $params['request'],
                    $contextId,
                    $params['contextPath']
                );
            }
        }
        return $mostCitedSubmissions;
    }

    public static function getTrending($params)
    {
        $contextId = $params['contextId'];
        $trendingSubmissions = [];
        foreach ($params['bestScoreDois'] ?? [] as $doi) {
            $submission = Repo::submission()->getByDoi($doi, $contextId);
            if ($submission) {
                $trendingSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $params['request'],
                    $contextId,
                    $params['contextPath'],
                    ['doi' => $doi]
                );
            }
        }

        return $trendingSubmissions;
    }

    private static function formatSubmissionData(
        Submission $submission,
        $request,
        $contextId,
        $contextPath,
        $additionalData = []
    ) {
        $publication = $submission->getCurrentPublication();

        $submissionData = [
            'submissionUrl' => $request->getDispatcher()->url(
                $request,
                Application::ROUTE_PAGE,
                $contextPath,
                'article',
                'view',
                [$submission->getBestId()]
            ),
            'title' => $publication->getData('title'),
            'authorString' => $publication->getAuthorString(self::getAuthorUserGroups($contextId)),
            'datePublished' => $publication->getData('datePublished'),
        ];

        $issue = Repo::issue()->getBySubmissionId($submission->getId());

        if ($publication->getLocalizedData('coverImage')) {
            $submissionData['coverImage'] = $publication->getLocalizedData('coverImage');
            $submissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
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

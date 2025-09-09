<?php

import('classes.submission.Submission');

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
        $request = $params['request'];
        $contextId = $params['contextId'];
        $contextPath = $params['contextPath'];
        $limit = $params['limit'];

        $submissions = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED,
            'orderBy' => 'datePublished',
            'orderDirection' => 'DESC',
            'count' => $limit
        ]);
        $mostRecentSubmissionsData = [];
        foreach ($submissions as $submission) {
            $mostRecentSubmissionsData[] = self::formatSubmissionData(
                $submission,
                $request,
                $contextId,
                $contextPath
            );
        }
        return $mostRecentSubmissionsData;
    }

    public static function getMostRead($params)
    {
        $request = $params['request'];
        $contextId = $params['contextId'];
        $limit = $params['limit'] + 1;
        $contextPath = $params['contextPath'];
        $mostReadDays = $params['mostReadDays'] ?? 120;

        $dayString = '-' . $mostReadDays . ' days';
        $dateStart = date('Y-m-d', strtotime($dayString));
        $currentDate = date('Y-m-d');

        $topSubmissions = Services::get('stats')->getOrderedObjects(
            STATISTICS_DIMENSION_SUBMISSION_ID,
            STATISTICS_ORDER_DESC,
            [
                'contextIds' => [$contextId],
                'dateStart' => $dateStart,
                'dateEnd' => $currentDate,
                'count' => $limit,
                'offset' => 0,
            ]
        );

        $mostReadSubmissions = [];
        $submissionService = Services::get('submission');

        foreach ($topSubmissions as $topSubmission) {
            $submissionId = $topSubmission['id'];
            $submission = $submissionService->get($submissionId);

            if ($submission && $submission->getStatus() == STATUS_PUBLISHED) {
                $mostReadSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $request,
                    $contextId,
                    $contextPath,
                    ['metric' => $topSubmission['total']]
                );
            }
        }

        return $mostReadSubmissions;
    }

    public static function getMostCited($params)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $request = $params['request'];
        $mostCitedDois = $params['mostCitedDois'];
        $contextId = $params['contextId'];
        $contextPath = $params['contextPath'];
        $mostCitedSubmissions = [];
        foreach ($mostCitedDois as $doi) {
            $submission = $submissionDao->getByPubId('doi', $doi, $contextId);
            if ($submission) {
                $mostCitedSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $request,
                    $contextId,
                    $contextPath
                );
            }
        }
        return $mostCitedSubmissions;
    }

    public static function getTrending($params)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $request = $params['request'];
        $contextId = $params['contextId'];
        $contextPath = $params['contextPath'];
        $bestScoreDois = $params['bestScoreDois'] ?? [];

        $trendingSubmissions = [];
        foreach ($bestScoreDois as $doi) {
            $submission = $submissionDao->getByPubId('doi', $doi, $contextId);
            if ($submission) {
                $trendingSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $request,
                    $contextId,
                    $contextPath,
                    ['doi' => $doi]
                );
            }
        }

        return $trendingSubmissions;
    }

    private static function formatSubmissionData(
        $submission,
        $request,
        $contextId,
        $contextPath,
        $additionalData = []
    ) {
        $publication = $submission->getCurrentPublication();

        $submissionUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            $contextPath,
            'article',
            'view',
            $submission->getBestId()
        );
        $submissionData = [
            'submissionUrl' => $submissionUrl,
            'title' => $publication->getData('title'),
            'authorString' => $submission->getAuthorString(),
            'datePublished' => $submission->getDatePublished()
        ];

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issue = $issueDao->getBySubmissionId($submission->getId(), $contextId);

        if ($publication->getLocalizedData('coverImage')) {
            $submissionData['coverImage'] = $publication->getLocalizedData(
                'coverImage'
            );
            $submissionData['coverImage']['coverImageUrl']
                = $publication->getLocalizedCoverImageUrl($contextId);
        } elseif ($issue && $issue->getLocalizedCoverImage()) {
            $submissionData['coverImage'] = [
                'name' => $issue->getLocalizedCoverImage(),
                'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
            ];
        }

        return array_merge($submissionData, $additionalData);
    }
}

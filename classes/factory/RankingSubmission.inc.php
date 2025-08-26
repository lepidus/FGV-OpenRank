<?php

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
            $mostRecentSubmissionsData[] = self::formatSubmissionData($submission, $request, $contextId, $contextPath);
        }
        return $mostRecentSubmissionsData;
    }

    public static function getMostRead($params)
    {
        $request = $params['request'];
        $contextId = $params['contextId'];
        $limit = $params['limit'];
        $contextPath = $params['contextPath'];
        $mostReadDays = $params['mostReadDays'] ?? 120;

        $dayString = "-" . $mostReadDays . " days";
        $daysAgo = date('Ymd', strtotime($dayString));
        $currentDate = date('Ymd');

        $filter = [
            STATISTICS_DIMENSION_CONTEXT_ID => $contextId,
            STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_SUBMISSION_FILE,
            STATISTICS_DIMENSION_DAY => ['from' => $daysAgo, 'to' => $currentDate],
        ];

        $orderBy = [STATISTICS_METRIC => STATISTICS_ORDER_DESC];
        $column = [STATISTICS_DIMENSION_SUBMISSION_ID];

        import('lib.pkp.classes.db.DBResultRange');
        $dbResultRange = new DBResultRange($limit);

        $metricsDao = DAORegistry::getDAO('MetricsDAO');
        $result = $metricsDao->getMetrics(
            OJS_METRIC_TYPE_COUNTER,
            $column,
            $filter,
            $orderBy,
            $dbResultRange
        );

        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $mostReadSubmissions = [];

        foreach ($result as $resultRecord) {
            $submissionId = $resultRecord[STATISTICS_DIMENSION_SUBMISSION_ID];
            $submission = $submissionDao->getById($submissionId);

            if ($submission && $submission->getStatus() == STATUS_PUBLISHED) {
                $mostReadSubmissions[] = self::formatSubmissionData(
                    $submission,
                    $request,
                    $contextId,
                    $contextPath,
                    ['metric' => $resultRecord[STATISTICS_METRIC]]
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
                $mostCitedSubmissions[] = self::formatSubmissionData($submission, $request, $contextId, $contextPath);
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

    private static function formatSubmissionData($submission, $request, $contextId, $contextPath, $additionalData = [])
    {
        $publication = $submission->getCurrentPublication();

        $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
        $submissionData = [
            'submissionUrl' => $submissionUrl,
            'title' => $publication->getData('title'),
            'authorString' => $submission->getAuthorString(),
            'datePublished' => $submission->getDatePublished()
        ];

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issue = $issueDao->getBySubmissionId($submission->getId(), $contextId);

        if ($publication->getLocalizedData('coverImage')) {
            $submissionData['coverImage'] = $publication->getLocalizedData('coverImage');
            $submissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
        } elseif ($issue && $issue->getLocalizedCoverImage()) {
            $submissionData['coverImage'] = [
                'name' => $issue->getLocalizedCoverImage(),
                'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
            ];
        }

        return array_merge($submissionData, $additionalData);
    }
}

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
            $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
            $submissionData = [
                'submissionUrl' => $submissionUrl,
                'title' => $submission->getLocalizedTitle(),
                'authorString' => $submission->getAuthorString(),
                'datePublishedLabel' => __("plugins.generic.rankingPlugin.tabs.content.publishedDate", ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]),
            ];
            $publication = $submission->getCurrentPublication();
            $issueDao = DAORegistry::getDAO('IssueDAO');
            $issue = $issueDao->getBySubmissionId($submission->getId(), $contextId);

            if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                if ($publication->getLocalizedData('coverImage')) {
                    $submissionData['coverImage'] = $publication->getLocalizedData('coverImage');
                    $submissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
                } elseif ($issue && $issue->getLocalizedCoverImage()) {
                    $submissionData['coverImage'] = [
                        'name' => $issue->getLocalizedCoverImage(),
                        'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
                    ];
                }
            }
            $mostRecentSubmissionsData[] = $submissionData;
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

        $filter = array(
            STATISTICS_DIMENSION_CONTEXT_ID => $contextId,
            STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_SUBMISSION_FILE,
        );
        $filter[STATISTICS_DIMENSION_DAY]['from'] = $daysAgo;
        $filter[STATISTICS_DIMENSION_DAY]['to'] = $currentDate;

        $orderBy = array(STATISTICS_METRIC => STATISTICS_ORDER_DESC);
        $column = array(STATISTICS_DIMENSION_SUBMISSION_ID);

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
                $submissionUrl = $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    $contextPath,
                    'article',
                    'view',
                    $submission->getBestId()
                );
                $mostReadSubmissionsData = [
                    'submissionUrl' => $submissionUrl,
                    'title' => $submission->getLocalizedTitle(),
                    'authorString' => $submission->getAuthorString(),
                    'datePublishedLabel' => __(
                        "plugins.generic.rankingPlugin.tabs.content.publishedDate",
                        ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]
                    ),
                    'metric' => $resultRecord[STATISTICS_METRIC],
                ];

                $publication = $submission->getCurrentPublication();
                $issueDao = DAORegistry::getDAO('IssueDAO');
                $issue = $issueDao->getBySubmissionId($submission->getId());

                if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                    if ($publication->getLocalizedData('coverImage')) {
                        $mostReadSubmissionsData['coverImage'] = $publication->getLocalizedData('coverImage');
                        $mostReadSubmissionsData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
                    } elseif ($issue && $issue->getLocalizedCoverImage()) {
                        $mostReadSubmissionsData['coverImage'] = [
                            'name' => $issue->getLocalizedCoverImage(),
                            'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
                        ];
                    }
                }
                $mostReadSubmissions[] = $mostReadSubmissionsData;
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
                $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
                $mostCitedSubmissionData = [
                    'submissionUrl' => $submissionUrl,
                    'title' => $submission->getLocalizedTitle(),
                    'authorString' => $submission->getAuthorString(),
                    'datePublishedLabel' => __("plugins.generic.rankingPlugin.tabs.content.publishedDate", ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]),
                ];
                $publication = $submission->getCurrentPublication();
                $issueDao = DAORegistry::getDAO('IssueDAO');
                $issue = $issueDao->getBySubmissionId($submission->getId());

                if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                    if ($publication->getLocalizedData('coverImage')) {
                        $mostCitedSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage');
                        $mostCitedSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
                    } elseif ($issue && $issue->getLocalizedCoverImage()) {
                        $mostCitedSubmissionData['coverImage'] = [
                            'name' => $issue->getLocalizedCoverImage(),
                            'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
                        ];
                    }
                }
                $mostCitedSubmissions[] = $mostCitedSubmissionData;
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
                $submissionUrl = $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    $contextPath,
                    'article',
                    'view',
                    $submission->getBestId()
                );
                $trendingSubmissionData = [
                    'submissionUrl' => $submissionUrl,
                    'title' => $submission->getLocalizedTitle(),
                    'authorString' => $submission->getAuthorString(),
                    'datePublishedLabel' => __(
                        "plugins.generic.rankingPlugin.tabs.content.publishedDate",
                        ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]
                    ),
                    'doi' => $doi,
                ];

                $publication = $submission->getCurrentPublication();
                $issueDao = DAORegistry::getDAO('IssueDAO');
                $issue = $issueDao->getBySubmissionId($submission->getId());

                if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                    if ($publication->getLocalizedData('coverImage')) {
                        $trendingSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage');
                        $trendingSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
                    } elseif ($issue && $issue->getLocalizedCoverImage()) {
                        $trendingSubmissionData['coverImage'] = [
                            'name' => $issue->getLocalizedCoverImage(),
                            'coverImageUrl' => $issue->getLocalizedCoverImageUrl()
                        ];
                    }
                }

                $trendingSubmissions[] = $trendingSubmissionData;
            }
        }

        return $trendingSubmissions;
    }
}

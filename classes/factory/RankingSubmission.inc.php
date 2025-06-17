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
        $topSubmissions = Services::get('stats')->getOrderedObjects(
            STATISTICS_DIMENSION_SUBMISSION_ID,
            STATISTICS_ORDER_DESC,
            [
                'contextIds' => [$contextId],
                'count' => $limit
            ]
        );

        $mostReadSubmissions = [];
        foreach ($topSubmissions as $topSubmission) {
            $submissionId = $topSubmission['id'];
            $submission = Services::get('submission')->get($submissionId);
            if ($submission && $submission->getStatus() == STATUS_PUBLISHED) {
                $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
                $mostReadSubmissionsData = [
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
        $limit = $params['limit'];

        $sqlParams = [
            'altmetricsScore',
            STATUS_PUBLISHED,
            $contextId
        ];
        $range = new \DBResultRange($limit);

        $sql = 'SELECT s.*, MAX(ssas.setting_value) as max_score 
            FROM submissions s 
            LEFT JOIN submission_settings ssas ON (s.submission_id = ssas.submission_id AND ssas.setting_name = ?) 
            WHERE s.status = ? 
            AND s.context_id = ? 
            AND ssas.setting_value IS NOT NULL 
            GROUP BY s.submission_id 
            ORDER BY max_score DESC';
        $result = $submissionDao->retrieveRange(
            $sql,
            $sqlParams,
            $range
        );
        $queryResults = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $range);
        $submissions = $queryResults->toAssociativeArray();

        $trendingSubmissions = [];
        foreach ($submissions as $submission) {
            $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
            $trendingSubmissionData = [
                'submissionUrl' => $submissionUrl,
                'title' => $submission->getLocalizedTitle(),
                'authorString' => $submission->getAuthorString(),
                'datePublishedLabel' => __("plugins.generic.rankingPlugin.tabs.content.publishedDate", ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]),
                'altmetricsScore' => $submission->getData('altmetricsScore'),
                'doi' => $submission->getCurrentPublication()->getData('pub-id::doi'),
            ];

            $trendingSubmissions[] = $trendingSubmissionData;
        }

        return $trendingSubmissions;
    }
}

<?php

class RankingSubmission
{
    public static function get($functionName, $params = [])
    {
        $availableFunctionNames = [
            'mostRecent' => 'getMostRecent',
            'mostViewed' => 'getMostViewed',
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
        return Services::get('submission')->getMany([
            'contextId' => $params['contextId'],
            'status' => STATUS_PUBLISHED,
            'orderBy' => 'datePublished',
            'orderDirection' => 'DESC',
            'count' => $params['limit']
        ]);
    }

    public static function getMostViewed($params)
    {
        $topSubmissions = Services::get('stats')->getOrderedObjects(
            STATISTICS_DIMENSION_SUBMISSION_ID,
            STATISTICS_ORDER_DESC,
            [
                'contextIds' => [$params['contextId']],
                'count' => $params['limit']
            ]
        );

        $submissions = [];
        foreach ($topSubmissions as $topSubmission) {
            $submissionId = $topSubmission['id'];
            $submission = Services::get('submission')->get($submissionId);
            if ($submission && $submission->getStatus() == STATUS_PUBLISHED) {
                $submissions[] = $submission;
            }
        }

        return $submissions;
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
                    $mostCitedSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage') ?: $issue->getLocalizedCoverImage();
                    $mostCitedSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
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
            $publication = $submission->getCurrentPublication();
            $issueDao = DAORegistry::getDAO('IssueDAO');
            $issue = $issueDao->getBySubmissionId($submission->getId());

            if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                $trendingSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage') ?: $issue->getLocalizedCoverImage();
                $trendingSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
            }
            $trendingSubmissions[] = $trendingSubmissionData;
        }

        return $trendingSubmissions;
    }
}

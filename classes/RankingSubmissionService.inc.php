<?php

class RankingSubmissionService
{
    private $contextId;
    private const LIMIT = 4;

    public function __construct($contextId)
    {
        $this->contextId = $contextId;
    }

    public function getMostRecent()
    {
        return Services::get('submission')->getMany([
            'contextId' => $this->contextId,
            'status' => STATUS_PUBLISHED,
            'orderBy' => 'datePublished',
            'orderDirection' => 'DESC',
            'count' => self::LIMIT
        ]);
    }

    public function getMostViewed()
    {
        $topSubmissions = Services::get('stats')->getOrderedObjects(
            STATISTICS_DIMENSION_SUBMISSION_ID,
            STATISTICS_ORDER_DESC,
            [
                'contextIds' => [$this->contextId],
                'count' => self::LIMIT
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

    public function getAListOfMostCitedSubmissionsByCachedDois($mostCitedDois, $contextPath, $request)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $mostCitedSubmissions = [];
        foreach ($mostCitedDois as $doi) {
            $submission = $submissionDao->getByPubId('doi', $doi, $this->contextId);
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
                    $mostCitedSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($this->contextId);
                }
                $mostCitedSubmissions[] = $mostCitedSubmissionData;
            }
        }
        return $mostCitedSubmissions;
    }

    public function updatePublishedSubmissionsAltmetricsScore($publishedSubmissions, $altmetricsClient, $request)
    {
        foreach ($publishedSubmissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if (!empty($publication->getData('pub-id::doi'))) {
                $submissionDoi = $publication->getData('pub-id::doi');
                $submissionMetrics = [];
                try {
                    $submissionMetrics = $altmetricsClient->fetchAltmetrics($submissionDoi);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
                if (isset($submissionMetrics["score"])) {
                    $score = (float) $submissionMetrics["score"];
                    Services::get('submission')->edit($submission, ['altmetricsScore' => $score], $request);
                }

            }
        }
    }

    public function retrieveTrendingSubmissions($contextPath, $request)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');

        $params = [
            'altmetricsScore',
            STATUS_PUBLISHED,
            $this->contextId
        ];
        $range = new \DBResultRange(self::LIMIT);

        $sql = 'SELECT s.* FROM submissions s LEFT JOIN submission_settings ssas ON (s.submission_id = ssas.submission_id AND ssas.setting_name = ?) WHERE s.status = ? AND s.context_id = ? AND ssas.setting_value IS NOT NULL GROUP BY s.submission_id ORDER BY ssas.setting_value DESC';
        $result = $submissionDao->retrieveRange(
            $sql,
            $params,
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

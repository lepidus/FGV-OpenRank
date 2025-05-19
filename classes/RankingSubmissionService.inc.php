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
}

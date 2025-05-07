<?php

class RankingSubmission
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
}

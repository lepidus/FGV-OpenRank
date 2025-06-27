<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');
import('plugins.generic.rankingPlugin.classes.factory.RankingSubmission');

class RankingSubmissionService
{
    private $contextId;
    private $contextPath;
    private $limit;
    private const DEFAULT_LIMIT = 4;

    public function __construct($contextId, $contextPath, $limit = null)
    {
        $this->contextId = $contextId;
        $this->contextPath = $contextPath;
        $this->limit = $limit ?? self::DEFAULT_LIMIT;
    }

    public function getMostRecent($request)
    {
        return RankingSubmission::get('mostRecent', [
            'contextId' => $this->contextId,
            'limit' => $this->limit,
            'request' => $request,
            'contextPath' => $this->contextPath
        ]);
    }

    public function getMostRead($request, $mostReadDays = null)
    {
        return RankingSubmission::get('mostRead', [
            'contextId' => $this->contextId,
            'limit' => $this->limit,
            'request' => $request,
            'contextPath' => $this->contextPath,
            'mostReadDays' => $mostReadDays
        ]);
    }

    public function getAListOfMostCitedSubmissionsByCachedDois($mostCitedDois, $request)
    {
        return RankingSubmission::get('mostCited', [
            'contextId' => $this->contextId,
            'contextPath' => $this->contextPath,
            'mostCitedDois' => $mostCitedDois,
            'request' => $request,
            'limit' => $this->limit
        ]);
    }

    public function retrieveTrendingSubmissions($request)
    {
        return RankingSubmission::get('trending', [
            'contextId' => $this->contextId,
            'contextPath' => $this->contextPath,
            'request' => $request,
            'limit' => $this->limit
        ]);
    }

    public function updatePublishedSubmissionsAltmetricsScore($publishedSubmissions, $request)
    {
        $altmetricsClient = new Altmetrics(Application::get()->getHttpClient());
        $submissionsArray = iterator_to_array($publishedSubmissions);
        $batchSize = 50;
        $delayInSeconds = 1;

        $submissionChunks = array_chunk($submissionsArray, $batchSize);

        foreach ($submissionChunks as $chunk) {
            foreach ($chunk as $submission) {
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
            sleep($delayInSeconds);
        }
    }
}

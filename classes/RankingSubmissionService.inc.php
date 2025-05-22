<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');
import('plugins.generic.rankingPlugin.classes.factory.RankingSubmission');

class RankingSubmissionService
{
    private $contextId;
    private $contextPath;
    private const LIMIT = 4;

    public function __construct($contextId, $contextPath)
    {
        $this->contextId = $contextId;
        $this->contextPath = $contextPath;
    }

    public function getMostRecent($request)
    {
        return RankingSubmission::get('mostRecent', ['contextId' => $this->contextId, 'limit' => self::LIMIT, 'request' => $request]);
    }

    public function getMostRead($request)
    {
        return RankingSubmission::get('mostRead', ['contextId' => $this->contextId, 'limit' => self::LIMIT, 'request' => $request]);
    }

    public function getAListOfMostCitedSubmissionsByCachedDois($mostCitedDois, $request)
    {
        return RankingSubmission::get('mostCited', [
            'contextId' => $this->contextId,
            'contextPath' => $this->contextPath,
            'mostCitedDois' => $mostCitedDois,
            'request' => $request
        ]);
    }

    public function retrieveTrendingSubmissions($request)
    {
        return RankingSubmission::get('trending', [
            'contextId' => $this->contextId,
            'contextPath' => $this->contextPath,
            'request' => $request,
            'limit' => self::LIMIT
        ]);
    }

    public function updatePublishedSubmissionsAltmetricsScore($publishedSubmissions, $request)
    {
        $altmetricsClient = new Altmetrics(Application::get()->getHttpClient());
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
}

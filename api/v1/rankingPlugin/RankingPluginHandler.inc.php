<?php

import('lib.pkp.classes.handler.APIHandler');
import('plugins.generic.rankingPlugin.classes.cache.MostCitedDois');

class RankingPluginHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'rankingPlugin';
        $roles = [ROLE_ID_MANAGER];
        $this->_endpoints = array(
            'GET' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/mostCitedSubmissions',
                    'handler' => array($this, 'getMostCited')
                )
            ),
        );
        parent::__construct();
    }

    public function getMostCited($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return $this->handleError(new Exception('Context not found'), $request);
        }
        $issn = $context->getData('onlineIssn') ?: $context->getData('printIssn');
        $mostCitedDoisCache = new MostCitedDois();
        $mostCitedDois = $mostCitedDoisCache->getMostCitedSubmissionsDois(
            $context->getId(),
            $issn,
            4
        );
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submissions = [];
        foreach ($mostCitedDois as $doi) {
            $submission = $submissionDao->getByPubId('doi', $doi, $context->getId());
            if ($submission) {
                $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $context->getPath(), 'article', 'view', $submission->getBestId());
                $mostRecentSubmissionData = [
                    'submissionUrl' => $submissionUrl,
                    'title' => $submission->getLocalizedTitle(),
                    'authorString' => $submission->getAuthorString(),
                    'datePublishedLabel' => __("plugins.generic.rankingPlugin.tabs.content.publishedDate", ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]),
                ];
                $publication = $submission->getCurrentPublication();
                $issueDao = DAORegistry::getDAO('IssueDAO');
                $issue = $issueDao->getBySubmissionId($submission->getId());

                if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                    $mostRecentSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage') ?: $issue->getLocalizedCoverImage();
                    $mostRecentSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($context->getId());
                }
                $submissions[] = $mostRecentSubmissionData;
            }
        }

        return $response->withJson(['mostCitedSubmissions' => $submissions], 200);
    }
}

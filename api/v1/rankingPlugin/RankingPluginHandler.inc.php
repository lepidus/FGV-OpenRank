<?php

import('lib.pkp.classes.handler.APIHandler');
import('plugins.generic.rankingPlugin.classes.cache.MostCitedDois');

class RankingPluginHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'rankingPlugin';
        $roles = [ROLE_ID_MANAGER];
        error_log($this->getEndpointPattern());
        $this->_endpoints = array(
            'GET' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/validate/caches',
                    'handler' => array($this, 'hasMostCitedDoisCache')
                )
            ),
        );
        parent::__construct();
    }

    public function hasMostCitedDoisCache($slimRequest, $response, $args)
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
                $submissions[] = $submission;
            }
        }

        return $response->withJson(['hasCache' => $submissions], 200);
    }
}

<?php

import('lib.pkp.classes.handler.APIHandler');
import('plugins.generic.rankingPlugin.classes.cache.MostCitedDois');
import('plugins.generic.rankingPlugin.classes.cache.TrendingSubmissions');
import('plugins.generic.rankingPlugin.classes.cache.MostRecent');
import('plugins.generic.rankingPlugin.classes.cache.MostRead');
import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

define('SESSION_DISABLE_INIT', true);

class RankingPluginHandler extends APIHandler
{
    private const LIMIT = 4;

    public function __construct()
    {
        $this->_handlerPath = 'rankingPlugin';
        $this->_endpoints = array(
            'GET' => array(
                array(
                    'pattern' => $this->getEndpointPattern() . '/mostRecent',
                    'handler' => array($this, 'getMostRecentSubmissions')
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/mostRead',
                    'handler' => array($this, 'getMostReadSubmissions')
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/mostCitedSubmissions',
                    'handler' => array($this, 'getMostCited')
                ),
                array(
                    'pattern' => $this->getEndpointPattern() . '/trendingSubmissions',
                    'handler' => array($this, 'getTrendingSubmissions')
                ),
            ),
        );
        parent::__construct();
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getMostRecentSubmissions($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $mostRecent = new MostRecent();
        try {
            $mostRecentSubmissions = $mostRecent->getMostRecentSubmissions($context, $request);
        } catch (\Exception $e) {
            return $response->withJson(['errorMessage' => $e->getMessage()], 500);
        }

        return $response->withJson(['mostRecentSubmissions' => $mostRecentSubmissions], 200);
    }

    public function getMostReadSubmissions($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $mostRead = new MostRead();
        try {
            $mostReadSubmissions = $mostRead->getMostReadSubmissions($context, $request);
        } catch (\Exception $e) {
            return $response->withJson(['errorMessage' => $e->getMessage()], 500);
        }

        return $response->withJson(['mostReadSubmissions' => $mostReadSubmissions], 200);
    }

    public function getMostCited($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $rankingSubmissionService = new RankingSubmissionService($context->getId(), $context->getPath());

        $issn = $context->getData('onlineIssn') ?: $context->getData('printIssn');
        if ($issn) {
            $mostCitedDoisCache = new MostCitedDois();
            $mostCitedDois = $mostCitedDoisCache->getMostCitedSubmissionsDois(
                $context->getId(),
                $issn,
                self::LIMIT
            );
            $submissions = $rankingSubmissionService->getAListOfMostCitedSubmissionsByCachedDois($mostCitedDois, $request);
            return $response->withJson(['mostCitedSubmissions' => $submissions], 200);
        }
    }

    public function getTrendingSubmissions($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $trendingSubmissions = new TrendingSubmissions();
        try {
            $trendingSubmissions = $trendingSubmissions->getTrendingSubmissions($context->getId(), $context->getPath());
            return $response->withJson(['trendingSubmissions' => $trendingSubmissions], 200);
        } catch (\Exception $e) {
            return $response->withJson(['errorMessage' => $e->getMessage()], 500);
        }
    }
}

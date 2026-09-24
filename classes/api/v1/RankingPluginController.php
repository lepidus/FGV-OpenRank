<?php

namespace APP\plugins\generic\rankingPlugin\classes\api\v1;

use APP\plugins\generic\rankingPlugin\classes\cache\MostCitedDois;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRead;
use APP\plugins\generic\rankingPlugin\classes\cache\MostRecent;
use APP\plugins\generic\rankingPlugin\classes\cache\TrendingSubmissions;
use APP\plugins\generic\rankingPlugin\classes\RankingSubmissionService;
use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\security\authorization\ContextRequiredPolicy;

class RankingPluginController extends PKPBaseController
{
    public function __construct(
        private $plugin
    ) {
    }

    public function getHandlerPath(): string
    {
        return 'rankingPlugin';
    }

    public function getRouteGroupMiddleware(): array
    {
        return ['has.context'];
    }

    public function getGroupRoutes(): void
    {
        Route::get('mostRecent', $this->getMostRecentSubmissions(...))
            ->name('rankingPlugin.mostRecent');
        Route::get('mostRead', $this->getMostReadSubmissions(...))
            ->name('rankingPlugin.mostRead');
        Route::get('mostCitedSubmissions', $this->getMostCited(...))
            ->name('rankingPlugin.mostCited');
        Route::get('trendingSubmissions', $this->getTrendingSubmissions(...))
            ->name('rankingPlugin.trending');
    }

    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new ContextRequiredPolicy($request));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getMostRecentSubmissions(): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $limit = $this->getRankingTabs()->getItemsPerTab(RankingTabs::MOST_RECENT);

        return $this->respond('mostRecentSubmissions', fn () => (new MostRecent())->getMostRecentSubmissions($context, $request, $limit));
    }

    public function getMostReadSubmissions(): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $limit = $this->getRankingTabs()->getItemsPerTab(RankingTabs::MOST_READ);

        return $this->respond('mostReadSubmissions', fn () => (new MostRead($this->plugin))->getMostReadSubmissions($context, $request, $limit));
    }

    public function getMostCited(): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $limit = $this->getRankingTabs()->getItemsPerTab(RankingTabs::MOST_CITED);
        $issn = $context->getData('onlineIssn') ?: $context->getData('printIssn');

        return $this->respond('mostCitedSubmissions', function () use ($request, $context, $limit, $issn) {
            if (!$issn) {
                return [];
            }

            $mostCitedDois = (new MostCitedDois())->getMostCitedSubmissionsDois($context->getId(), $issn, $limit);
            $rankingSubmissionService = new RankingSubmissionService($context->getId(), $context->getPath(), $limit);

            return $rankingSubmissionService->getAListOfMostCitedSubmissionsByCachedDois($mostCitedDois, $request);
        });
    }

    public function getTrendingSubmissions(): JsonResponse
    {
        $context = $this->getRequest()->getContext();
        $limit = $this->getRankingTabs()->getItemsPerTab(RankingTabs::TRENDING);

        return $this->respond(
            'trendingSubmissions',
            fn () => (new TrendingSubmissions($this->plugin))->getTrendingSubmissions($context->getId(), $context->getPath(), $limit)
        );
    }

    private function respond(string $key, callable $getSubmissions): JsonResponse
    {
        try {
            return response()->json([$key => $getSubmissions()], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getRankingTabs(): RankingTabs
    {
        return new RankingTabs($this->plugin, $this->getRequest()->getContext()->getId());
    }
}

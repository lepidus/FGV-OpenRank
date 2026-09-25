<?php

namespace APP\plugins\generic\rankingPlugin\classes\api\v1;

use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use APP\plugins\generic\rankingPlugin\classes\services\RankingTabService;
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
        return $this->respond('mostRecentSubmissions', RankingTabs::MOST_RECENT);
    }

    public function getMostReadSubmissions(): JsonResponse
    {
        return $this->respond('mostReadSubmissions', RankingTabs::MOST_READ);
    }

    public function getMostCited(): JsonResponse
    {
        return $this->respond('mostCitedSubmissions', RankingTabs::MOST_CITED);
    }

    public function getTrendingSubmissions(): JsonResponse
    {
        return $this->respond('trendingSubmissions', RankingTabs::TRENDING);
    }

    private function respond(string $key, string $tabId): JsonResponse
    {
        $request = $this->getRequest();
        $rankingTabService = new RankingTabService($this->plugin, $request->getContext(), $request);

        try {
            return response()->json([$key => $rankingTabService->getSubmissions($tabId)], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

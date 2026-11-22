<?php

use APP\core\Request;
use APP\journal\Journal;
use APP\plugins\generic\fgvOpenRank\classes\api\v1\FgvOpenRankSettingsController;
use APP\plugins\generic\fgvOpenRank\FgvOpenRankPlugin;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use PKP\core\PKPComponentRouter;
use PKP\handler\PKPHandler;
use PKP\middleware\PolicyAuthorizer;
use PKP\security\Role;
use PKP\tests\PKPTestCase;
use PKP\user\User;

class RankingSettingsAuthorizationTest extends PKPTestCase
{
    public function testSettingsRequireAJournalManagerWithSettingsAccess(): void
    {
        DB::beginTransaction();
        try {
            $journal = new Journal();
            $journal->setId((int) DB::table('journals')->insertGetId([
                'path' => 'ranking-settings-test', 'primary_locale' => 'en',
            ], 'journal_id'));
            $user = new User();
            $user->setId((int) DB::table('users')->insertGetId([
                'username' => 'ranking-settings-test',
                'password' => '',
                'email' => 'ranking-settings-test@example.test',
                'date_registered' => '2020-01-01 00:00:00',
            ], 'user_id'));
            $group = DB::table('user_groups')->insertGetId([
                'context_id' => $journal->getId(), 'role_id' => Role::ROLE_ID_MANAGER, 'permit_settings' => 0,
            ], 'user_group_id');
            $siteAdminGroup = DB::table('user_groups')->insertGetId([
                'context_id' => null, 'role_id' => Role::ROLE_ID_SITE_ADMIN,
            ], 'user_group_id');
            DB::table('user_user_groups')->insert(['user_group_id' => $siteAdminGroup, 'user_id' => $user->getId()]);
            self::assertFalse($this->canManageSettings($journal, $user));
            DB::table('user_user_groups')->where('user_group_id', $siteAdminGroup)->delete();

            DB::table('user_user_groups')->insert(['user_group_id' => $group, 'user_id' => $user->getId()]);

            self::assertFalse($this->canManageSettings($journal, $user));
            DB::table('user_groups')->where('user_group_id', $group)->update(['permit_settings' => 1]);
            self::assertTrue($this->canManageSettings($journal, $user));
        } finally {
            DB::rollBack();
        }
    }

    private function canManageSettings(Journal $journal, User $user): bool
    {
        $router = $this->createStub(PKPComponentRouter::class);
        $router->method('getContext')->willReturn($journal);
        $router->method('getRequestedOp')->willReturn('get');
        $request = $this->createStub(Request::class);
        $request->method('getRouter')->willReturn($router);
        $request->method('getUser')->willReturn($user);

        $handler = new FgvOpenRankSettingsController($this->createStub(FgvOpenRankPlugin::class));
        $router->method('getHandler')->willReturn($this->createStub(PKPHandler::class));
        $routes = new RouteCollection();
        $routes->add((new Route('GET', 'settings', $handler->get(...)))->middleware($handler->getRouteGroupMiddleware()));
        $policyAuthorizer = new class () extends PolicyAuthorizer {
            public function roleAssignments(RouteCollection $routes): array
            {
                return $this->getRoleAssignmentMap($routes)->toArray();
            }
        };

        $args = [];
        return $handler->authorize($request, $args, $policyAuthorizer->roleAssignments($routes));
    }
}

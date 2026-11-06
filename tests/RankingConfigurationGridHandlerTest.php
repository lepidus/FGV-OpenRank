<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.PKPRequest');
import('plugins.generic.fgvOpenRank.controllers.grid.RankingConfigurationGridHandler');
import('plugins.generic.fgvOpenRank.FgvOpenRankPlugin');

class RankingConfigurationGridHandlerTest extends PKPTestCase
{
    protected function getMockedRegistryKeys()
    {
        return array_merge(parent::getMockedRegistryKeys(), ['plugins']);
    }

    /**
     * @test
     */
    public function itShouldNotReorderTabsWithoutAValidCsrfToken()
    {
        $plugin = $this->createMock(FgvOpenRankPlugin::class);
        $plugin->expects($this->never())->method('updateSetting');
        $plugins = ['generic' => ['fgvopenrankplugin' => $plugin]];
        Registry::set('plugins', $plugins);

        $request = $this->createMock(PKPRequest::class);
        $request->method('checkCSRF')->willReturn(false);
        $request->method('getUserVar')->willReturnMap([['data', json_encode(['2', '1'])]]);

        $response = (new RankingConfigurationGridHandler())->saveSequence([], $request);

        $this->assertFalse($response->getStatus());
    }
}

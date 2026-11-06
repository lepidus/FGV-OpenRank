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

    /**
     * @test
     */
    public function itShouldReorderTabsWithAValidCsrfToken()
    {
        $savedSequences = [];
        $plugin = $this->createMock(FgvOpenRankPlugin::class);
        $plugin->method('updateSetting')
            ->willReturnCallback(function ($contextId, $name, $value) use (&$savedSequences) {
                $savedSequences[$name] = $value;
            });
        $plugins = ['generic' => ['fgvopenrankplugin' => $plugin]];
        Registry::set('plugins', $plugins);

        $request = $this->createMock(PKPRequest::class);
        $request->method('checkCSRF')->willReturn(true);
        $request->method('getUserVar')->willReturnMap([['data', json_encode(['1', '0'])]]);

        $response = (new RankingConfigurationGridHandler())->saveSequence([], $request);

        $this->assertTrue($response->getStatus());
        $this->assertSame(1, $savedSequences['tabSequence_1'] ?? null);
        $this->assertSame(2, $savedSequences['tabSequence_0'] ?? null);
    }
}

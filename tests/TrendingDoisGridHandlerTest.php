<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.controllers.grid.TrendingDoisGridHandler');

class TrendingDoisGridHandlerTest extends PKPTestCase
{
    /**
     * @test
     */
    public function itShouldBuildGridDataFromStoredDois()
    {
        $stored = [
            'uidA' => '10.1/a',
            'uidB' => '10.1/b',
        ];

        $result = TrendingDoisGridHandler::buildGridData($stored);

        $this->assertSame([
            'uidA' => ['id' => 'uidA', 'doi' => '10.1/a', 'sequence' => 0],
            'uidB' => ['id' => 'uidB', 'doi' => '10.1/b', 'sequence' => 1],
        ], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyGridWhenNoDoisStored()
    {
        $this->assertSame([], TrendingDoisGridHandler::buildGridData([]));
    }

    /**
     * @test
     */
    public function itShouldReorderDoisAccordingToProvidedOrder()
    {
        $stored = [
            'uidA' => '10.1/a',
            'uidB' => '10.1/b',
            'uidC' => '10.1/c',
        ];

        $result = TrendingDoisGridHandler::reorderDois($stored, ['uidC', 'uidA', 'uidB']);

        $this->assertSame(['uidC', 'uidA', 'uidB'], array_keys($result));
        $this->assertSame(['10.1/c', '10.1/a', '10.1/b'], array_values($result));
    }

    /**
     * @test
     */
    public function itShouldPreserveUnlistedDoisAtTheEndWhenReordering()
    {
        $stored = [
            'uidA' => '10.1/a',
            'uidB' => '10.1/b',
            'uidC' => '10.1/c',
        ];

        $result = TrendingDoisGridHandler::reorderDois($stored, ['uidC']);

        $this->assertSame(['uidC', 'uidA', 'uidB'], array_keys($result));
    }

    /**
     * @test
     */
    public function itShouldRemoveDoiByOptionId()
    {
        $stored = [
            'uidA' => '10.1/a',
            'uidB' => '10.1/b',
            'uidC' => '10.1/c',
        ];

        $result = TrendingDoisGridHandler::removeDoi($stored, 'uidB');

        $this->assertSame([
            'uidA' => '10.1/a',
            'uidC' => '10.1/c',
        ], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnUnchangedWhenRemovingNonexistentDoi()
    {
        $stored = ['uidA' => '10.1/a'];

        $result = TrendingDoisGridHandler::removeDoi($stored, 'missingUid');

        $this->assertSame($stored, $result);
    }
}

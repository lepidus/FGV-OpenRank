<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.rankingPlugin.controllers.grid.TrendingDoisGridHandler');

class TrendingDoisGridHandlerTest extends PKPTestCase
{
    private const FIRST_OPTION_ID = '64f1a0b7c2d31';
    private const SECOND_OPTION_ID = '64f1a0b7c2d32';
    private const THIRD_OPTION_ID = '64f1a0b7c2d33';
    private const ABSENT_OPTION_ID = '64ffffffffffe';

    private const FIRST_DOI = '10.4322/2179-7560.2024.001';
    private const SECOND_DOI = '10.4322/2179-7560.2024.002';
    private const THIRD_DOI = '10.4322/2179-7560.2024.003';

    /**
     * @test
     */
    public function itShouldBuildGridDataFromStoredDois()
    {
        $stored = [
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::SECOND_OPTION_ID => self::SECOND_DOI,
        ];

        $result = TrendingDoisGridHandler::buildGridData($stored);

        $this->assertSame([
            self::FIRST_OPTION_ID => ['id' => self::FIRST_OPTION_ID, 'doi' => self::FIRST_DOI, 'sequence' => 0],
            self::SECOND_OPTION_ID => ['id' => self::SECOND_OPTION_ID, 'doi' => self::SECOND_DOI, 'sequence' => 1],
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
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::SECOND_OPTION_ID => self::SECOND_DOI,
            self::THIRD_OPTION_ID => self::THIRD_DOI,
        ];

        $result = TrendingDoisGridHandler::reorderDois(
            $stored,
            [self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID]
        );

        $this->assertSame(
            [self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID],
            array_keys($result)
        );
        $this->assertSame(
            [self::THIRD_DOI, self::FIRST_DOI, self::SECOND_DOI],
            array_values($result)
        );
    }

    /**
     * @test
     */
    public function itShouldPreserveUnlistedDoisAtTheEndWhenReordering()
    {
        $stored = [
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::SECOND_OPTION_ID => self::SECOND_DOI,
            self::THIRD_OPTION_ID => self::THIRD_DOI,
        ];

        $result = TrendingDoisGridHandler::reorderDois($stored, [self::THIRD_OPTION_ID]);

        $this->assertSame(
            [self::THIRD_OPTION_ID, self::FIRST_OPTION_ID, self::SECOND_OPTION_ID],
            array_keys($result)
        );
    }

    /**
     * @test
     */
    public function itShouldRemoveDoiByOptionId()
    {
        $stored = [
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::SECOND_OPTION_ID => self::SECOND_DOI,
            self::THIRD_OPTION_ID => self::THIRD_DOI,
        ];

        $result = TrendingDoisGridHandler::removeDoi($stored, self::SECOND_OPTION_ID);

        $this->assertSame([
            self::FIRST_OPTION_ID => self::FIRST_DOI,
            self::THIRD_OPTION_ID => self::THIRD_DOI,
        ], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnUnchangedWhenRemovingNonexistentDoi()
    {
        $stored = [self::FIRST_OPTION_ID => self::FIRST_DOI];

        $result = TrendingDoisGridHandler::removeDoi($stored, self::ABSENT_OPTION_ID);

        $this->assertSame($stored, $result);
    }
}

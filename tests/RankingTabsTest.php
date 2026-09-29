<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\classes\RankingTabs;
use APP\plugins\generic\rankingPlugin\RankingPlugin;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class RankingTabsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private function buildRankingTabs(array &$settings): RankingTabs
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getSetting')
            ->willReturnCallback(function ($contextId, $key) use (&$settings) {
                return $settings[$key] ?? null;
            });
        $plugin->method('updateSetting')
            ->willReturnCallback(function ($contextId, $key, $value) use (&$settings) {
                $settings[$key] = $value;
                return true;
            });

        return new RankingTabs($plugin, self::CONTEXT_ID);
    }

    #[Test]
    public function itShouldListEveryTabInTheDefaultOrderWhenNothingIsStored()
    {
        $settings = [];

        $this->assertSame(
            ['mostRecent', 'mostRead', 'mostCited', 'trending', 'highlight'],
            $this->buildRankingTabs($settings)->getOrderedEnabled()
        );
    }

    #[Test]
    public function itShouldPersistTheOrderAndTheEnabledFlags()
    {
        $settings = [];
        $rankingTabs = $this->buildRankingTabs($settings);

        $rankingTabs->save([
            ['id' => 'trending', 'enabled' => true],
            ['id' => 'mostRecent', 'enabled' => 'false'],
            ['id' => 'highlight', 'enabled' => true],
            ['id' => 'mostRead', 'enabled' => true],
            ['id' => 'mostCited', 'enabled' => false],
        ]);

        $this->assertSame(['trending', 'mostRecent', 'highlight', 'mostRead', 'mostCited'], $rankingTabs->getOrdered());
        $this->assertSame(['trending', 'highlight', 'mostRead'], $rankingTabs->getOrderedEnabled());
    }

    #[Test]
    public function itShouldIgnoreUnknownTabs()
    {
        $settings = [];
        $this->buildRankingTabs($settings)->save([['id' => 'sidebar', 'enabled' => true]]);

        $this->assertSame([], $settings);
    }

    #[Test]
    public function itShouldReadSettingsStoredByTheLegacyGrid()
    {
        $settings = [
            'tabSequence_0' => 5,
            'tabSequence_1' => 1,
            'tabSequence_2' => 2,
            'tabSequence_3' => 3,
            'tabSequence_4' => 4,
            'tabEnabled_2' => false,
        ];

        $this->assertSame(
            ['mostRead', 'trending', 'highlight', 'mostRecent'],
            $this->buildRankingTabs($settings)->getOrderedEnabled()
        );
    }

    #[Test]
    public function itShouldLocalizeWithCurrentThenPrimaryThenAnyLocale()
    {
        $this->assertSame('Título', RankingTabs::localize(['pt_BR' => 'Título', 'en' => 'Title'], 'pt_BR', 'en'));
        $this->assertSame('Title', RankingTabs::localize(['pt_BR' => '', 'en' => 'Title'], 'pt_BR', 'en'));
        $this->assertSame('Título', RankingTabs::localize(['es' => '', 'pt_BR' => 'Título'], 'fr', 'es'));
        $this->assertSame('', RankingTabs::localize(null, 'en', 'en'));
    }
}

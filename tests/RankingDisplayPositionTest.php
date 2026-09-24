<?php

namespace APP\plugins\generic\rankingPlugin\tests;

use APP\plugins\generic\rankingPlugin\RankingPlugin;
use APP\plugins\generic\rankingPlugin\classes\RankingDisplayPosition;
use APP\plugins\generic\rankingPlugin\tests\helpers\HookCallbackForTests;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class RankingDisplayPositionTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;

    private function buildPluginMock(array $settings)
    {
        $plugin = $this->createMock(RankingPlugin::class);
        $plugin->method('getSetting')
            ->willReturnCallback(function ($contextId, $key) use ($settings) {
                return $settings[$key] ?? null;
            });
        return $plugin;
    }

    private function callIndexHook($plugin, $contextId, &$output)
    {
        $hookCallback = new HookCallbackForTests($plugin, $contextId);
        $params = ['name' => 'Templates::Index::journal'];
        $args = [&$params, null, &$output];

        return $hookCallback->insertRankingPlaceholder(
            'Templates::Index::journal',
            $args
        );
    }

    #[Test]
    public function itShouldDefaultToAdditionalContentWhenValueIsAbsent()
    {
        $this->assertSame(
            RankingDisplayPosition::ADDITIONAL_CONTENT,
            RankingDisplayPosition::normalize(null)
        );
    }

    #[Test]
    public function itShouldFallBackToAdditionalContentForUnknownValues()
    {
        $this->assertSame(
            RankingDisplayPosition::ADDITIONAL_CONTENT,
            RankingDisplayPosition::normalize('sidebar')
        );
    }

    #[Test]
    public function itShouldKeepKnownValues()
    {
        foreach (RankingDisplayPosition::getAll() as $position) {
            $this->assertSame(
                $position,
                RankingDisplayPosition::normalize($position)
            );
        }
    }

    #[Test]
    public function itShouldDefaultTheSectionNumberToTheFirstOne()
    {
        $this->assertSame(1, RankingDisplayPosition::normalizeSection(null));
        $this->assertSame(1, RankingDisplayPosition::normalizeSection(''));
        $this->assertSame(1, RankingDisplayPosition::normalizeSection('not a number'));
    }

    #[Test]
    public function itShouldRejectSectionNumbersBelowTheFirstOne()
    {
        $this->assertSame(1, RankingDisplayPosition::normalizeSection(0));
        $this->assertSame(1, RankingDisplayPosition::normalizeSection(-4));
    }

    #[Test]
    public function itShouldKeepValidSectionNumbers()
    {
        $this->assertSame(3, RankingDisplayPosition::normalizeSection(3));
        $this->assertSame(3, RankingDisplayPosition::normalizeSection('3'));
    }

    #[Test]
    public function itShouldOnlyNeedAPlaceholderWhenThePluginOwnsThePosition()
    {
        $this->assertFalse(
            RankingDisplayPosition::needsPlaceholder(RankingDisplayPosition::ADDITIONAL_CONTENT)
        );
        $this->assertFalse(RankingDisplayPosition::needsPlaceholder(null));
        $this->assertTrue(
            RankingDisplayPosition::needsPlaceholder(RankingDisplayPosition::TOP)
        );
        $this->assertTrue(
            RankingDisplayPosition::needsPlaceholder(RankingDisplayPosition::AFTER_SECTION)
        );
        $this->assertTrue(
            RankingDisplayPosition::needsPlaceholder(RankingDisplayPosition::BOTTOM)
        );
    }

    #[Test]
    public function itShouldNotOutputThePlaceholderWhenPositionIsAdditionalContent()
    {
        $plugin = $this->buildPluginMock([
            RankingDisplayPosition::SETTING_NAME
                => RankingDisplayPosition::ADDITIONAL_CONTENT
        ]);

        $output = null;
        $this->callIndexHook($plugin, self::CONTEXT_ID, $output);

        $this->assertNull($output);
    }

    #[Test]
    public function itShouldNotOutputThePlaceholderWhenPositionWasNeverConfigured()
    {
        $plugin = $this->buildPluginMock([]);

        $output = null;
        $this->callIndexHook($plugin, self::CONTEXT_ID, $output);

        $this->assertNull($output);
    }

    #[Test]
    public function itShouldOutputThePlaceholderForEveryPluginOwnedPosition()
    {
        $positions = [
            RankingDisplayPosition::TOP,
            RankingDisplayPosition::AFTER_SECTION,
            RankingDisplayPosition::BOTTOM,
        ];

        foreach ($positions as $position) {
            $plugin = $this->buildPluginMock([
                RankingDisplayPosition::SETTING_NAME => $position
            ]);

            $output = null;
            $this->callIndexHook($plugin, self::CONTEXT_ID, $output);

            $this->assertSame('<div class="rankingTabs"></div>', $output, $position);
        }
    }

    #[Test]
    public function itShouldHandTheNormalizedPositionToTheFrontend()
    {
        $plugin = $this->buildPluginMock([
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::AFTER_SECTION,
            RankingDisplayPosition::SECTION_SETTING_NAME => '2',
        ]);
        $hookCallback = new HookCallbackForTests($plugin, self::CONTEXT_ID);

        $this->assertSame(
            [
                'displayPosition' => RankingDisplayPosition::AFTER_SECTION,
                'displayPositionSection' => 2,
            ],
            $hookCallback->getDisplayPositionSettings(self::CONTEXT_ID)
        );
    }

    #[Test]
    public function itShouldHandSafeDefaultsToTheFrontendWhenNothingIsConfigured()
    {
        $plugin = $this->buildPluginMock([]);
        $hookCallback = new HookCallbackForTests($plugin, self::CONTEXT_ID);

        $this->assertSame(
            [
                'displayPosition' => RankingDisplayPosition::ADDITIONAL_CONTENT,
                'displayPositionSection' => 1,
            ],
            $hookCallback->getDisplayPositionSettings(self::CONTEXT_ID)
        );
    }

    #[Test]
    public function itShouldPreserveOutputFromOtherPluginsOnTheSameHook()
    {
        $plugin = $this->buildPluginMock([
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP
        ]);

        $output = '<p>another plugin</p>';
        $this->callIndexHook($plugin, self::CONTEXT_ID, $output);

        $this->assertSame(
            '<p>another plugin</p><div class="rankingTabs"></div>',
            $output
        );
    }

    #[Test]
    public function itShouldNotOutputThePlaceholderWithoutAContext()
    {
        $plugin = $this->buildPluginMock([
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP
        ]);

        $output = null;
        $this->callIndexHook($plugin, null, $output);

        $this->assertNull($output);
    }

    #[Test]
    public function itShouldNotStopOtherCallbacksRegisteredOnTheSameHook()
    {
        $plugin = $this->buildPluginMock([
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::TOP
        ]);

        $output = null;
        $result = $this->callIndexHook($plugin, self::CONTEXT_ID, $output);

        $this->assertFalse($result);
    }
}

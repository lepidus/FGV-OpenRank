<?php

namespace APP\plugins\generic\fgvOpenRank\tests;

use APP\template\TemplateManager;
use PHPUnit\Framework\Attributes\Test;
use PKP\tests\PKPTestCase;

class RankingTemplateTest extends PKPTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(document.domain)>';
    private const RAW_EVENT_HANDLER = '/<[^>]*\sonerror\s*=/i';

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'templateManager'];
    }

    private function render(array $customTitles, array $customDescriptions, array $highlightCustomContent): string
    {
        $templateMgr = TemplateManager::getManager($this->mockRequest());
        $templateMgr->assign([
            'orderedTabs' => ['mostRecent', 'highlight'],
            'customTitles' => $customTitles,
            'customDescriptions' => $customDescriptions,
            'highlightCustomContent' => $highlightCustomContent,
        ]);

        return $templateMgr->fetch('file:' . dirname(__DIR__) . '/templates/ranking.tpl');
    }

    #[Test]
    public function itShouldEscapeCustomTitlesAndDescriptions()
    {
        $output = $this->render(
            ['mostRecent' => 'Recent ' . self::PAYLOAD, 'highlight' => 'Highlight ' . self::PAYLOAD],
            ['mostRecent' => 'Description ' . self::PAYLOAD, 'highlight' => null],
            ['highlight' => null]
        );

        $this->assertDoesNotMatchRegularExpression(self::RAW_EVENT_HANDLER, $output);
        $this->assertStringContainsString('Recent &lt;img src=x onerror=alert(document.domain)&gt;', $output);
        $this->assertStringContainsString('Highlight &lt;img src=x onerror=alert(document.domain)&gt;', $output);
        $this->assertStringContainsString('Description &lt;img src=x onerror=alert(document.domain)&gt;', $output);
    }

    #[Test]
    public function itShouldRenderTheHighlightHtmlAsTheJournalManagerSavedIt()
    {
        $highlight = '<blockquote><p>Editor\'s pick</p></blockquote>'
            . '<p><img src="https://journal.test/cover.png" alt="Cover" width="300" height="200"></p>'
            . '<p><span style="text-decoration: underline;">Read</span> <code>DOI</code></p>';

        $output = $this->render(
            ['mostRecent' => null, 'highlight' => null],
            ['mostRecent' => null, 'highlight' => null],
            ['highlight' => $highlight]
        );

        $this->assertStringContainsString($highlight, $output);
    }
}

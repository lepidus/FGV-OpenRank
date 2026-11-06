<?php

import('lib.pkp.tests.PKPTestCase');
import('classes.template.TemplateManager');
import('classes.core.PageRouter');
import('lib.pkp.classes.core.Dispatcher');

class RankingTemplateTest extends PKPTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(document.domain)>';
    private const RAW_EVENT_HANDLER = '/<[^>]*\sonerror\s*=/i';

    protected function getMockedRegistryKeys()
    {
        return array_merge(parent::getMockedRegistryKeys(), ['request', 'templateManager']);
    }

    /**
     * Builds a page request like PKPTestCase::mockRequest() does, without
     * opening a session, which cannot start after PHPUnit printed output.
     */
    private function buildRequest()
    {
        Registry::delete('request');
        Registry::delete('templateManager');
        $application = Application::get();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['PATH_INFO'] = 'index/index/index';
        $request = $application->getRequest();

        $router = new PageRouter();
        $router->setApplication($application);
        $dispatcher = new Dispatcher();
        $dispatcher->setApplication($application);
        $router->setDispatcher($dispatcher);
        $request->setRouter($router);

        return $request;
    }

    private function render(array $customTitles, array $customDescriptions, array $highlightCustomContent)
    {
        $templateMgr = TemplateManager::getManager($this->buildRequest());
        $templateMgr->assign([
            'orderedTabs' => ['mostRecent', 'highlight'],
            'customTitles' => $customTitles,
            'customDescriptions' => $customDescriptions,
            'highlightCustomContent' => $highlightCustomContent,
        ]);

        return $templateMgr->fetch('file:' . dirname(__DIR__) . '/templates/ranking.tpl');
    }

    /**
     * @test
     */
    public function itShouldEscapeCustomTitlesAndDescriptions()
    {
        $output = $this->render(
            ['mostRecent' => 'Recent ' . self::PAYLOAD, 'highlight' => 'Highlight ' . self::PAYLOAD],
            ['mostRecent' => 'Description ' . self::PAYLOAD, 'highlight' => null],
            ['highlight' => null]
        );

        $this->assertSame(0, preg_match(self::RAW_EVENT_HANDLER, $output), $output);
        $this->assertStringContainsString('Recent &lt;img src=x onerror=alert(document.domain)&gt;', $output);
        $this->assertStringContainsString('Highlight &lt;img src=x onerror=alert(document.domain)&gt;', $output);
        $this->assertStringContainsString('Description &lt;img src=x onerror=alert(document.domain)&gt;', $output);
    }

    /**
     * @test
     */
    public function itShouldKeepSafeHighlightHtmlAndDropUnsafeMarkup()
    {
        $output = $this->render(
            ['mostRecent' => null, 'highlight' => null],
            ['mostRecent' => null, 'highlight' => null],
            ['highlight' => '<p><strong>Editor\'s pick</strong></p>' . self::PAYLOAD . '<script>alert(1)</script>']
        );

        $this->assertStringContainsString('<p><strong>Editor\'s pick</strong></p>', $output);
        $this->assertSame(0, preg_match(self::RAW_EVENT_HANDLER, $output), $output);
        $this->assertStringNotContainsString('<script', $output);
    }
}

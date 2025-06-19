<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        {foreach from=$orderedTabs item="tabId" name="tabLoop"}
            <li{if $smarty.foreach.tabLoop.first} class="active"{/if} role="presentation">
                <a href="#{if $tabId == 'mostRecent'}mostRecentSubmissions{elseif $tabId == 'mostRead'}mostReadSubmissions{elseif $tabId == 'mostCited'}mostCitedSubmissions{elseif $tabId == 'trending'}trendingSubmissions{else}{$tabId}{/if}" aria-controls="{if $tabId == 'mostRecent'}mostRecentSubmissions{elseif $tabId == 'mostRead'}mostReadSubmissions{elseif $tabId == 'mostCited'}mostCitedSubmissions{elseif $tabId == 'trending'}trendingSubmissions{else}{$tabId}{/if}" role="tab" data-toggle="tab">
                    {if $customTitles.$tabId}
                        {$customTitles.$tabId}
                    {else}
                        {translate key="plugins.generic.rankingPlugin.tabs.{$tabId}.defaultTitle"}
                    {/if}
                </a>
            </li>
        {/foreach}
    </ul>

    <div class="tab-content">
        {foreach from=$orderedTabs item="tabId" name="contentLoop"}
            <div role="tabpanel" class="tab-pane{if $smarty.foreach.contentLoop.first} active{/if}" id="{if $tabId == 'mostRecent'}mostRecentSubmissions{elseif $tabId == 'mostRead'}mostReadSubmissions{elseif $tabId == 'mostCited'}mostCitedSubmissions{elseif $tabId == 'trending'}trendingSubmissions{else}{$tabId}{/if}">
                <div>
                    {if $tabId == 'highlight'}
                        <div class="article-item">
                            <div class="article-cover">
                                <div class="item cover_image">
                                    <div class="sub_item">
                                        <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/RhmSruZR2Kw" style="width: 30rem; height: 15rem; border: 0;"></iframe>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="article-details highlight">
                                {if $highlightCustomContent.highlight}
                                    {$highlightCustomContent.highlight}
                                {elseif $customDescriptions.highlight}
                                    <p>{$customDescriptions.highlight}</p>
                                {else}
                                    <p>{translate key="plugins.generic.rankingPlugin.tabs.highlight.content.videoDescription"}</p>
                                {/if}
                            </div>
                        </div>
                    {else}
                        <div>
                            <p><em>
                                {if $customDescriptions.$tabId}
                                    {$customDescriptions.$tabId}
                                {else}
                                    {translate key="plugins.generic.rankingPlugin.tabs.{$tabId}.content.description"}
                                {/if}
                            </em></p>
                            <hr>
                        </div>
                        <div id="{if $tabId == 'mostRecent'}mostRecentSubmissions{elseif $tabId == 'mostRead'}mostReadSubmissions{elseif $tabId == 'mostCited'}mostCitedSubmissions{elseif $tabId == 'trending'}trendingSubmissions{else}{$tabId}{/if}Container">
                            <div class="loading-message">
                                <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                            </div>
                        </div>
                        <div id="{if $tabId == 'mostRecent'}mostRecentSubmissions{elseif $tabId == 'mostRead'}mostReadSubmissions{elseif $tabId == 'mostCited'}mostCitedSubmissions{elseif $tabId == 'trending'}trendingSubmissions{else}{$tabId}{/if}Pagination" class="ranking-pagination" role="navigation" aria-label="{translate key="common.pagination.label"}"></div>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>
</div>

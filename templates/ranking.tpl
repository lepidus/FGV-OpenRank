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
                    <div>
                        <p><em>
                            {if $customDescriptions.$tabId}
                                {$customDescriptions.$tabId}
                                <hr>
                            {else}
                                {if $tabId != 'highlight'}
                                    {translate key="plugins.generic.rankingPlugin.tabs.{$tabId}.content.description"}
                                    <hr>
                                {/if}
                            {/if}
                        </em></p>
                    </div>
                    {if $tabId == 'highlight'}
                        {$highlightCustomContent.highlight}
                    {else}
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

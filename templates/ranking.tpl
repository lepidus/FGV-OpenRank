<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active" role="presentation">
            <a href="#highlight" aria-controls="highlight" role="tab" data-toggle="tab">Highlight</a>
        </li>
        <li role="presentation">
            <a href="#mostRecentsSubmissions" aria-controls="mostRecentsSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostReadSubmissions" aria-controls="mostReadSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRead.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostCitedSubmissions" aria-controls="mostCitedSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostCited.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#trendingSubmissions" aria-controls="trendingSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.trending.defaultTitle"}</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="highlight">
            <div>
                <div class="article-item">
                    <div class="article-cover">
                        <div class="item cover_image">
                            <div class="sub_item">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/RhmSruZR2Kw"></iframe>
                            </div>
                        </div>
                    </div>
                    
                    <div class="article-details">
                        <p>Launched in 1961, RAE supported the development of administrative thinking in Brazil and the consolidation of the profession of administrator.</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostRecentsSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.content.description"}</em></p>
                    <hr>
                </div>
                {foreach from=$mostRecentSubmissions item="submission"}
                    {assign var="publication" value=$submission->getCurrentPublication()}
                    <div class="article-item">
                        {if ($publication && $publication->getLocalizedData('coverImage')) || ($issue && $issue->getLocalizedCoverImage())}
                            <div class="article-cover">
                                <div class="item cover_image">
                                    <div class="sub_item">
                                        {if $publication->getLocalizedData('coverImage')}
                                            {assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
                                            <img
                                                src="{$publication->getLocalizedCoverImageUrl($context->getId())|escape}"
                                                alt="{$coverImage.altText|escape|default:''}"
                                            >
                                        {else}
                                            <a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
                                                <img 
                                                    src="{$issue->getLocalizedCoverImageUrl()|escape}" 
                                                    alt="{$issue->getLocalizedCoverImageAltText()|escape|default:''}"
                                                >
                                            </a>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <div class="article-details">
                            <h3><a href="{url journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId()}">{$submission->getLocalizedTitle()|escape}</a></h3>
                            <div class="article-authors">
                                <div>{$submission->getAuthorString()|escape}</div>
                            </div>
                            <div class="article-date-published">
                                <p>{translate key="plugins.generic.rankingPlugin.tabs.content.publishedDate" datePublished=strftime('%b %e, %Y', strtotime($submission->getDatePublished()))}</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                {/foreach}
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostReadSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostRead.content.description"}</em></p>
                    <hr>
                </div>
                {foreach from=$mostViewedSubmissions item="submission"}
                    {assign var="publication" value=$submission->getCurrentPublication()}
                    <div class="article-item">
                        {if ($publication && $publication->getLocalizedData('coverImage')) || ($issue && $issue->getLocalizedCoverImage())}
                            <div class="article-cover">
                                <div class="item cover_image">
                                    <div class="sub_item">
                                        {if $publication->getLocalizedData('coverImage')}
                                            {assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
                                            <img
                                                src="{$publication->getLocalizedCoverImageUrl($context->getId())|escape}"
                                                alt="{$coverImage.altText|escape|default:''}"
                                            >
                                        {else}
                                            <a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
                                                <img 
                                                    src="{$issue->getLocalizedCoverImageUrl()|escape}" 
                                                    alt="{$issue->getLocalizedCoverImageAltText()|escape|default:''}"
                                                >
                                            </a>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <div class="article-details">
                            <h3><a href="{url journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId()}">{$submission->getLocalizedTitle()|escape}</a></h3>
                            <div class="article-authors">
                                <div>{$submission->getAuthorString()|escape}</div>
                            </div>
                            <div class="article-date-published">
                                <p>{translate key="plugins.generic.rankingPlugin.tabs.content.publishedDate" datePublished=strftime('%b %e, %Y', strtotime($submission->getDatePublished()))}</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                {/foreach}
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostCitedSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostCited.content.description"}</a></em></p>
                    <hr>
                </div>
                <div id="mostCitedSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="trendingSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.trending.content.description"}</em></p>
                    <hr>
                </div>
                <div id="trendingSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
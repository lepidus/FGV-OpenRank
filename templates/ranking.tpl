<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active" role="presentation">
            <a href="#mostRecentsSubmissions" aria-controls="mostRecentsSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostReadSubmissions" aria-controls="mostReadSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRead.defaultTitle"}</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="mostRecentsSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.content.description"}</em></p>
                    <hr>
                </div>
                {foreach from=$mostRecentSubmissions item="submission"}
                    {assign var="publication" value=$submission->getCurrentPublication()}
                    <div class="article-item">
                        {if $publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())}
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
                        {if $publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())}
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
    </div>
</div>
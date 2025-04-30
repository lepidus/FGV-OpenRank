<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active" role="presentation">
            <a href="#mostRecentsSubmissions" aria-controls="mostRecentsSubmissions" role="tab" data-toggle="tab">Mais recentes</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="mostRecentsSubmissions">
        <div>
            <div style="border: 1px 0px 10px;">
                <p style="margin: 15px 0px 10px;"><em>Articles most recently published for this journal.</em></p>
                <hr>
            </div>
            {foreach from=$mostRecentSubmissions item="submission"}
                <div>
                    <h3><a href="{url journal=$currentContext->getPath() page="article" op="view" path=$submission->getBestId()}">{$submission->getLocalizedTitle()}</a></h3>
                    <div>
                        <div>{$submission->getAuthorString()|escape}</div>
                    </div>
                    <p>&nbsp;</p>
                    <div>
                        <p>Published: {strftime('%b %e, %Y', strtotime($submission->getDatePublished()))}</p>
                    </div>
                </div>
                <hr>
            {/foreach}
        </div>
    </div>
</div>
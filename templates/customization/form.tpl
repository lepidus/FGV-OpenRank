<script>
    $(function() {ldelim}
    $('#RankingCustomizationForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="RankingCustomization">
    <input type="hidden" id="tabId" value="{$tabId|escape}" />
    <form class="pkp_form" id="RankingCustomizationForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.fgvOpenRank.controllers.grid.RankingConfigurationGridHandler" op="updateTab" tabId=$tabId}">
        {csrf}

        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="RankingCustomizationFormNotification"}

        {fbvFormArea id="customizationForm"}
            {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.grid.column.customTitle"}
                {fbvElement type="text" id="customTitle" value=$customTitle size=$fbvStyles.size.MEDIUM multilingual=true}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.grid.column.customDescription"}
                {fbvElement type="textarea" id="description" value=$description size=$fbvStyles.size.MEDIUM multilingual=true}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.settings.itemsPerTab"}
                {fbvElement type="text" id="itemsPerTab" value=$itemsPerTab size=$fbvStyles.size.SMALL}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.settings.itemsPerPage"}
                {fbvElement type="text" id="itemsPerPage" value=$itemsPerPage size=$fbvStyles.size.SMALL}
            {/fbvFormSection}

            {if $tabId == 'mostRead'}
                {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.settings.mostReadDays"}
                    {fbvElement type="text" id="mostReadDays" value=$mostReadDays size=$fbvStyles.size.SMALL}
                {/fbvFormSection}
            {/if}

            {if $tabId == 'highlight'}
                {fbvFormSection label="plugins.generic.fgvOpenRank.configuration.settings.highlightContent"}
                    {fbvElement type="textarea" id="highlightContent" value=$highlightContent size=$fbvStyles.size.MEDIUM multilingual=true rich=true height=$fbvStyles.height.TALL}
                {/fbvFormSection}
            {/if}

            {if $tabId == 'trending'}
                {fbvFormSection label="plugins.generic.fgvOpenRank.settings.altmetricsApiKey"}
                    {fbvElement type="text" password="true" id="altmetricsApiKey" value="" size=$fbvStyles.size.MEDIUM}
                    <span class="description">
                        {if $hasAltmetricsApiKey}
                            {translate key="plugins.generic.fgvOpenRank.settings.altmetricsApiKey.stored"}
                        {else}
                            {translate key="plugins.generic.fgvOpenRank.settings.altmetricsApiKey.description"}
                        {/if}
                    </span>
                {/fbvFormSection}

                {if $hasAltmetricsApiKey}
                    {fbvFormSection list=true}
                        {fbvElement type="checkbox" id="removeAltmetricsApiKey" label="plugins.generic.fgvOpenRank.settings.altmetricsApiKey.remove"}
                    {/fbvFormSection}
                {/if}

                {if $hasAltmetricsApiKey}
                    <div class="pkp_notification">
                        <span class="description">
                            {translate key="plugins.generic.fgvOpenRank.trendingDois.inactiveWhileApiKeySet"}
                        </span>
                    </div>
                {/if}

                {capture assign=trendingDoisGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.fgvOpenRank.controllers.grid.TrendingDoisGridHandler" op="fetchGrid" escape=false}{/capture}
                {load_url_in_div id="trendingDoisGridContainer" url=$trendingDoisGridUrl}
            {/if}

            {fbvFormButtons submitText="common.save"}

        {/fbvFormArea}
    </form>
</div>
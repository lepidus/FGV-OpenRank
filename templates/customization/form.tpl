<script>
    $(function() {ldelim}
    $('#RankingCustomizationForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="RankingCustomization">
    <input type="hidden" id="tabId" value="{$tabId|escape}" />
    <form class="pkp_form" id="RankingCustomizationForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridHandler" op="updateTab" tabId=$tabId}">
        {csrf}

        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="RankingCustomizationFormNotification"}

        {fbvFormArea id="customizationForm"}
            {fbvFormSection label="plugins.generic.rankingPlugin.configuration.grid.column.customTitle"}
                {fbvElement type="text" id="customTitle" value=$customTitle size=$fbvStyles.size.MEDIUM multilingual=true}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.rankingPlugin.configuration.grid.column.customDescription"}
                {fbvElement type="textarea" id="description" value=$description size=$fbvStyles.size.MEDIUM multilingual=true}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.rankingPlugin.configuration.settings.itemsPerTab"}
                {fbvElement type="text" id="itemsPerTab" value=$itemsPerTab size=$fbvStyles.size.SMALL}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.rankingPlugin.configuration.settings.itemsPerPage"}
                {fbvElement type="text" id="itemsPerPage" value=$itemsPerPage size=$fbvStyles.size.SMALL}
            {/fbvFormSection}

            {if $tabId == 'mostRead'}
                {fbvFormSection label="plugins.generic.rankingPlugin.configuration.settings.mostReadDays"}
                    {fbvElement type="text" id="mostReadDays" value=$mostReadDays size=$fbvStyles.size.SMALL}
                {/fbvFormSection}
            {/if}

            {fbvFormButtons submitText="common.save"}

        {/fbvFormArea}
    </form>
</div>
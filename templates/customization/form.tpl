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
                {fbvElement type="text" id="customTitle" value=$customTitle|escape size=$fbvStyles.size.MEDIUM}
            {/fbvFormSection}

            {fbvFormSection label="plugins.generic.rankingPlugin.configuration.grid.column.customDescription"}
                {fbvElement type="text" id="description" value=$description|escape size=$fbvStyles.size.MEDIUM}
            {/fbvFormSection}

            {fbvFormButtons submitText="common.save"}

        {/fbvFormArea}
    </form>
</div>
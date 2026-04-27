<script>
    $(function() {ldelim}
        $('#trendingDoiForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<form class="pkp_form" id="trendingDoiForm" method="post"
      action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.rankingPlugin.controllers.grid.TrendingDoisGridHandler" op="updateDoi" rowId=$optionId|default:""}">
    {csrf}

    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="trendingDoiFormNotification"}

    {fbvFormArea id="trendingDoiFormArea"}
        {fbvFormSection label="plugins.generic.rankingPlugin.trendingDois.doi"}
            {fbvElement type="text" id="doi" value=$doi|escape size=$fbvStyles.size.MEDIUM required=true}
        {/fbvFormSection}

        {fbvFormButtons submitText="common.save"}
    {/fbvFormArea}
</form>

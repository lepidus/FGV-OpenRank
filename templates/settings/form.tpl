<script>
    $(function() {ldelim}
        $('#rankingPluginSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');

        var $positionForm = $('#rankingPluginSettingsForm');
        var $sectionField = $positionForm.find('#rankingPluginDisplayPositionSection');

        function toggleSectionField() {ldelim}
            var position = $positionForm
                .find('input[name="displayPosition"]:checked')
                .val();
            $sectionField.toggle(position === 'afterSection');
        {rdelim}

        $positionForm.on('change', 'input[name="displayPosition"]', toggleSectionField);
        toggleSectionField();
    {rdelim});
</script>

<link rel="stylesheet" href="{$pluginUrl}/styles/admin/settingsIntro.css?v={$assetVersion|escape}">

<div class="cmp_rankingPlugin_intro">
    <h2>{translate key="plugins.generic.rankingPlugin.settings.intro.title"}</h2>
    <p>{translate key="plugins.generic.rankingPlugin.settings.intro.description"}</p>
    <p>{translate key="plugins.generic.rankingPlugin.settings.intro.update"}</p>
    <p>{translate key="plugins.generic.rankingPlugin.settings.intro.configured"}</p>
    <p class="cmp_rankingPlugin_intro_note">{translate key="plugins.generic.rankingPlugin.settings.intro.guide"}</p>
</div>

{capture assign=rankingConfigurationUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridHandler" op="fetchGrid" escape=false}{/capture}
{load_url_in_div id="rankingConfigurationGridContainer" url=$rankingConfigurationUrl}

<form class="pkp_form" id="rankingPluginSettingsForm" method="post"
    action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    {csrf}
    {include file="common/formErrors.tpl"}

    {fbvFormArea id="rankingPluginDisplayPositionArea"}
        {fbvFormSection label="plugins.generic.rankingPlugin.settings.displayPosition" description="plugins.generic.rankingPlugin.settings.displayPosition.description" list=true}
            {foreach from=$displayPositionOptions key=positionValue item=positionLabel}
                {assign var="positionElementId" value="displayPosition-"|cat:$positionValue}
                {fbvElement type="radio" id=$positionElementId name="displayPosition" value=$positionValue checked=$displayPosition|compare:$positionValue label=$positionLabel}
            {/foreach}
        {/fbvFormSection}

        {fbvFormSection id="rankingPluginDisplayPositionSection" label="plugins.generic.rankingPlugin.settings.displayPositionSection" description="plugins.generic.rankingPlugin.settings.displayPositionSection.description"}
            {fbvElement type="text" id="displayPositionSection" value=$displayPositionSection size=$fbvStyles.size.SMALL}
        {/fbvFormSection}

        {fbvFormButtons submitText="common.save" hideCancel=true}
    {/fbvFormArea}
</form>

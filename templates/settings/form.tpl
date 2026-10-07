<script>
    $(function() {ldelim}
        $('#fgvOpenRankSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');

        var $positionForm = $('#fgvOpenRankSettingsForm');
        var $sectionField = $positionForm.find('#fgvOpenRankDisplayPositionSection');

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

<div class="cmp_fgvOpenRank_intro">
    <h2>{translate key="plugins.generic.fgvOpenRank.settings.intro.title"}</h2>
    <p>{translate key="plugins.generic.fgvOpenRank.settings.intro.description"}</p>
    <p>{translate key="plugins.generic.fgvOpenRank.settings.intro.update"}</p>
    <p>{translate key="plugins.generic.fgvOpenRank.settings.intro.configured"}</p>
    <p class="cmp_fgvOpenRank_intro_note">{translate key="plugins.generic.fgvOpenRank.settings.intro.guide"}</p>
</div>

{capture assign=rankingConfigurationUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.fgvOpenRank.controllers.grid.RankingConfigurationGridHandler" op="fetchGrid" escape=false}{/capture}
{load_url_in_div id="rankingConfigurationGridContainer" url=$rankingConfigurationUrl}

<form class="pkp_form" id="fgvOpenRankSettingsForm" method="post"
    action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    {csrf}
    {include file="common/formErrors.tpl"}

    {fbvFormArea id="fgvOpenRankDisplayPositionArea"}
        {fbvFormSection label="plugins.generic.fgvOpenRank.settings.displayPosition" description="plugins.generic.fgvOpenRank.settings.displayPosition.description" list=true}
            {foreach from=$displayPositionOptions key=positionValue item=positionLabel}
                {assign var="positionElementId" value="displayPosition-"|cat:$positionValue}
                {fbvElement type="radio" id=$positionElementId name="displayPosition" value=$positionValue checked=$displayPosition|compare:$positionValue label=$positionLabel}
            {/foreach}
        {/fbvFormSection}

        {fbvFormSection id="fgvOpenRankDisplayPositionSection" label="plugins.generic.fgvOpenRank.settings.displayPositionSection" description="plugins.generic.fgvOpenRank.settings.displayPositionSection.description"}
            {fbvElement type="text" id="displayPositionSection" value=$displayPositionSection size=$fbvStyles.size.SMALL}
        {/fbvFormSection}

        {fbvFormButtons submitText="common.save" hideCancel=true}
    {/fbvFormArea}
</form>

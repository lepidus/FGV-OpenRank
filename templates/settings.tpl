{**
 * templates/settings.tpl
 *
 * Copyright (c) 2025-2026 Lepidus Tecnologia
 * Copyright (c) 2025-2026 Fundação Getulio Vargas
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * FGV OpenRank settings.
 *}
{assign var="uuid" value=""|uniqid|escape}
<div id="fgvOpenRankSettings-{$uuid}">
	<fgv-open-rank-settings
		settings-api-url="{$settingsApiUrl|escape}"
		tab-settings-url="{$tabSettingsUrl|escape}"
	></fgv-open-rank-settings>
</div>
<script type="text/javascript">
	pkp.registry.init('fgvOpenRankSettings-{$uuid}', 'Container', {ldelim}{rdelim});
</script>

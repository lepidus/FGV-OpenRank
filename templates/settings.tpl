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
<div id="rankingPluginSettings-{$uuid}">
	<ranking-plugin-settings settings-api-url="{$settingsApiUrl|escape}"></ranking-plugin-settings>
</div>
<script type="text/javascript">
	pkp.registry.init('rankingPluginSettings-{$uuid}', 'Container', {ldelim}{rdelim});
</script>

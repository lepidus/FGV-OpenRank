{**
 * templates/tabSettings.tpl
 *
 * Copyright (c) 2025-2026 Lepidus Tecnologia
 * Copyright (c) 2025-2026 Fundação Getulio Vargas
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings of one FGV OpenRank tab.
 *}
{assign var="uuid" value=""|uniqid|escape}
<div id="rankingTabSettings-{$uuid}">
	<ranking-tab-settings
		settings-api-url="{$settingsApiUrl|escape}"
		trending-doi-url="{$trendingDoiUrl|escape}"
		tab-id="{$tabId|escape}"
	></ranking-tab-settings>
</div>
<script type="text/javascript">
	pkp.registry.init('rankingTabSettings-{$uuid}', 'Container', {ldelim}{rdelim});
</script>

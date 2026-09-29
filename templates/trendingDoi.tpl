{**
 * templates/trendingDoi.tpl
 *
 * Copyright (c) 2025-2026 Lepidus Tecnologia
 * Copyright (c) 2025-2026 Fundação Getulio Vargas
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form to add or edit a DOI of the Trending tab manual list.
 *}
{assign var="uuid" value=""|uniqid|escape}
<div id="rankingTrendingDoi-{$uuid}">
	<ranking-trending-doi-form
		settings-api-url="{$settingsApiUrl|escape}"
		doi-id="{$doiId|escape}"
	></ranking-trending-doi-form>
</div>
<script type="text/javascript">
	pkp.registry.init('rankingTrendingDoi-{$uuid}', 'Container', {ldelim}{rdelim});
</script>

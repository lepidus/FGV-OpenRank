{capture assign=rankingConfigurationUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.rankingPlugin.controllers.grid.RankingConfigurationGridHandler" op="fetchGrid" escape=false}{/capture}
{load_url_in_div id="rankingConfigurationGridContainer" url=$rankingConfigurationUrl}

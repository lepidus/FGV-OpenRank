<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active" role="presentation">
            <a href="#highlight" aria-controls="highlight" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.highlight.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostRecentSubmissions" aria-controls="mostRecentSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostReadSubmissions" aria-controls="mostReadSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostRead.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#mostCitedSubmissions" aria-controls="mostCitedSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.mostCited.defaultTitle"}</a>
        </li>
        <li role="presentation">
            <a href="#trendingSubmissions" aria-controls="trendingSubmissions" role="tab" data-toggle="tab">{translate key="plugins.generic.rankingPlugin.tabs.trending.defaultTitle"}</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="highlight">
            <div>
                <div class="article-item">
                    <div class="article-cover">
                        <div class="item cover_image">
                            <div class="sub_item">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/RhmSruZR2Kw" style="width: 30rem; height: 15rem; border: 0;"></iframe>
                            </div>
                        </div>
                    </div>
                    
                    <div class="article-details highlight">
                        <p>{translate key="plugins.generic.rankingPlugin.tabs.highlight.content.videoDescription"}</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostRecentSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostRecent.content.description"}</a></em></p>
                    <hr>
                </div>
                <div id="mostRecentSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostReadSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostRead.content.description"}</a></em></p>
                    <hr>
                </div>
                <div id="mostReadSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="mostCitedSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.mostCited.content.description"}</a></em></p>
                    <hr>
                </div>
                <div id="mostCitedSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="trendingSubmissions">
            <div>
                <div>
                    <p><em>{translate key="plugins.generic.rankingPlugin.tabs.trending.content.description"}</em></p>
                    <hr>
                </div>
                <div id="trendingSubmissionsContainer">
                    <div class="loading-message">
                        <p>{translate key="plugins.generic.rankingPlugin.loading"}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
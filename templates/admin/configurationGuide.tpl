{**
 * templates/admin/configurationGuide.tpl
 *
 * Copyright (c) 2025-2026 Lepidus Tecnologia
 * Copyright (c) 2025-2026 Fundação Getulio Vargas
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Step by step configuration guide for FGV OpenRank.
 *}
<link rel="stylesheet" href="{$pluginUrl}/styles/admin/configurationGuide.css?v={$assetVersion|escape}">

<div class="cmp_fgvOpenRank_guide" data-fgvopenrank-guide>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel>
        <p class="cmp_fgvOpenRank_guide_eyebrow">{translate key="plugins.generic.fgvOpenRank.configurationGuide.eyebrow"}</p>
        <h2 tabindex="-1">{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.description"}</p>
        <div class="cmp_fgvOpenRank_guide_summary" role="group" aria-label="{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.summaryLabel"}">
            <strong>{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.summary"}</strong>
            <span>{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.requirements"}</span>
            <span>{translate key="plugins.generic.fgvOpenRank.configurationGuide.intro.note"}</span>
        </div>
        <div class="cmp_fgvOpenRank_guide_actions cmp_fgvOpenRank_guide_actions_end">
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="1">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.start"}
            </button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress1" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=1 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress1">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step1.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step1.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step1.pathPosition"}</p>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step1.pathAdditionalContent"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step1.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="installedPlugins" href="{$guideInstalledPluginsUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.installedPlugins"}
            </a>
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="additionalContent" href="{$guideAdditionalContentUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.additionalContent"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="0">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="2">{translate key="plugins.generic.fgvOpenRank.configurationGuide.next"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress2" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=2 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress2">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step2.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step2.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step2.pathEnabled"}</p>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step2.pathEdit"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step2.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="installedPlugins" href="{$guideInstalledPluginsUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.installedPlugins"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="1">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="3">{translate key="plugins.generic.fgvOpenRank.configurationGuide.next"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress3" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=3 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress3">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step3.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step3.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step3.pathIssn"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step3.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="masthead" href="{$guideMastheadUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.masthead"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="2">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="4">{translate key="plugins.generic.fgvOpenRank.configurationGuide.next"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress4" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=4 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress4">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step4.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step4.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step4.pathEnable"}</p>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step4.pathPrefix"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step4.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="installedPlugins" href="{$guideInstalledPluginsUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.installedPlugins"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="3">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="5">{translate key="plugins.generic.fgvOpenRank.configurationGuide.next"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress5" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=5 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress5">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step5.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step5.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step5.pathApiKey"}</p>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step5.pathDoiList"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step5.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="installedPlugins" href="{$guideInstalledPluginsUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.installedPlugins"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="4">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="6">{translate key="plugins.generic.fgvOpenRank.configurationGuide.next"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel" data-guide-panel data-guide-step hidden>
        <p id="fgvOpenRankGuideProgress6" class="cmp_fgvOpenRank_guide_progress">
            {translate key="plugins.generic.fgvOpenRank.configurationGuide.progress" current=6 total=6}
        </p>
        <h2 tabindex="-1" aria-describedby="fgvOpenRankGuideProgress6">{translate key="plugins.generic.fgvOpenRank.configurationGuide.step6.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step6.description"}</p>

        <div class="cmp_fgvOpenRank_guide_instruction">
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.where"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step6.pathAcron"}</p>
            <h3>{translate key="plugins.generic.fgvOpenRank.configurationGuide.expected"}</h3>
            <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.step6.expected"}</p>
        </div>

        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="installedPlugins" href="{$guideInstalledPluginsUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.installedPlugins"}
            </a>
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="homepage" href="{$guideHomepageUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.homepage"}
            </a>
        </div>

        <div class="cmp_fgvOpenRank_guide_actions">
            <button type="button" class="pkp_button" data-guide-target="5">{translate key="plugins.generic.fgvOpenRank.configurationGuide.previous"}</button>
            <button type="button" class="pkp_button pkp_button_primary" data-guide-target="7">{translate key="plugins.generic.fgvOpenRank.configurationGuide.finish"}</button>
        </div>
    </section>

    <section class="cmp_fgvOpenRank_guide_panel cmp_fgvOpenRank_guide_complete" data-guide-panel hidden>
        <p class="cmp_fgvOpenRank_guide_eyebrow">{translate key="plugins.generic.fgvOpenRank.configurationGuide.complete.eyebrow"}</p>
        <h2 tabindex="-1">{translate key="plugins.generic.fgvOpenRank.configurationGuide.complete.title"}</h2>
        <p>{translate key="plugins.generic.fgvOpenRank.configurationGuide.complete.description"}</p>
        <div class="cmp_fgvOpenRank_guide_links">
            <a class="cmp_fgvOpenRank_guide_open" data-guide-link="homepage" href="{$guideHomepageUrl}" target="_blank" rel="noopener noreferrer">
                {translate key="plugins.generic.fgvOpenRank.configurationGuide.link.homepage"}
            </a>
            <button type="button" class="pkp_button" data-guide-target="0">{translate key="plugins.generic.fgvOpenRank.configurationGuide.restart"}</button>
        </div>
    </section>
</div>

<script src="{$pluginUrl}/js/configurationGuide.js?v={$assetVersion|escape}"></script>

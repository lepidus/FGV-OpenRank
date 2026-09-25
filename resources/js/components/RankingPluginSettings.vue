<template>
	<div v-if="!settings" class="rankingPluginSettings__loading">
		<PkpSpinner />
		{{ t('common.loading') }}
	</div>
	<div v-else class="rankingPluginSettings" data-cy="ranking-plugin-settings">
		<section class="rankingPluginSettings__intro" data-cy="ranking-plugin-settings-intro">
			<h2>{{ t('plugins.generic.rankingPlugin.settings.intro.title') }}</h2>
			<p>{{ t('plugins.generic.rankingPlugin.settings.intro.description') }}</p>
			<p>{{ t('plugins.generic.rankingPlugin.settings.intro.update') }}</p>
			<p>{{ t('plugins.generic.rankingPlugin.settings.intro.configured') }}</p>
			<p class="rankingPluginSettings__note">
				{{ t('plugins.generic.rankingPlugin.settings.intro.guide') }}
			</p>
		</section>

		<RankingTabsTable :tabs="settings.tabs" @save="saveTabs" @edit="openTabSettings" />

		<div data-cy="ranking-plugin-display-position">
			<PkpForm v-bind="displayPositionForm" @set="setDisplayPositionForm" @success="displayPositionSaved" />
		</div>
	</div>
</template>

<script setup>
import {ref, watch} from 'vue';
import RankingTabsTable from './RankingTabsTable.vue';
import {useSettingsModal} from './useSettingsModal.js';

const props = defineProps({
	settingsApiUrl: {type: String, required: true},
	tabSettingsUrl: {type: String, required: true},
});

const {t} = pkp.modules.useLocalize.useLocalize();
const {useFetch} = pkp.modules.useFetch;
const {notify} = pkp.modules.useNotify.useNotify();
const {openSettingsModal} = useSettingsModal();

const settings = ref(null);
const displayPositionForm = ref(null);

const {data, fetch: fetchSettings} = useFetch(props.settingsApiUrl);
watch(data, (newData) => newData && setSettings(newData));
fetchSettings();

function setSettings(newSettings) {
	settings.value = newSettings;
	displayPositionForm.value = newSettings.displayPositionForm;
}

function setDisplayPositionForm(formId, changes) {
	displayPositionForm.value = {...displayPositionForm.value, ...changes};
}

async function saveTabs(tabs) {
	settings.value = {...settings.value, tabs};

	const {data: savedSettings, fetch: save} = useFetch(`${props.settingsApiUrl}/tabs`, {
		method: 'PUT',
		body: {tabs: tabs.map(({id, enabled}) => ({id, enabled}))},
	});
	await save();

	if (savedSettings.value) {
		setSettings(savedSettings.value);
		notify(t('common.changesSaved'), 'success');
	} else {
		fetchSettings();
	}
}

function displayPositionSaved(savedSettings) {
	setSettings(savedSettings);
	notify(t('common.changesSaved'), 'success');
}

function openTabSettings(tab) {
	openSettingsModal({
		title: tab.customTitle || tab.label,
		url: props.tabSettingsUrl,
		params: {tabId: tab.id},
		formId: settings.value.tabForms[tab.id].id,
		onClose: fetchSettings,
	});
}
</script>

<style>
.rankingPluginSettings__loading {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	padding: 2rem;
}

.rankingPluginSettings {
	display: flex;
	flex-direction: column;
	gap: 1.5rem;
}

.rankingPluginSettings__intro {
	--intro-heading: #183f7a;
	--intro-text: #2f3438;
	--intro-muted: #49627d;
	--intro-border: #c8d4df;
	--intro-surface: #f5f8fa;
	color: var(--intro-text);
	font-size: 14px;
	line-height: 1.6;
}

.rankingPluginSettings__intro h2 {
	margin: 0 0 0.75rem;
	color: var(--intro-heading);
	font-size: 1.125rem;
	font-weight: 700;
}

.rankingPluginSettings__intro p {
	margin: 0 0 0.75rem;
}

.rankingPluginSettings__intro p.rankingPluginSettings__note {
	margin: 0;
	padding: 1rem;
	border: 1px solid var(--intro-border);
	border-inline-start-width: 4px;
	border-radius: 4px;
	background: var(--intro-surface);
	color: var(--intro-muted);
}
</style>

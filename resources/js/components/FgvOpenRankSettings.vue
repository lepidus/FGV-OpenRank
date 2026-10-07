<template>
	<div v-if="!settings" class="fgvOpenRankSettings__loading">
		<PkpSpinner />
		{{ t('common.loading') }}
	</div>
	<div v-else class="fgvOpenRankSettings" data-cy="fgv-open-rank-settings">
		<section class="fgvOpenRankSettings__intro" data-cy="fgv-open-rank-settings-intro">
			<h2>{{ t('plugins.generic.fgvOpenRank.settings.intro.title') }}</h2>
			<p>{{ t('plugins.generic.fgvOpenRank.settings.intro.description') }}</p>
			<p>{{ t('plugins.generic.fgvOpenRank.settings.intro.update') }}</p>
			<p>{{ t('plugins.generic.fgvOpenRank.settings.intro.configured') }}</p>
			<p class="fgvOpenRankSettings__note">
				{{ t('plugins.generic.fgvOpenRank.settings.intro.guide') }}
			</p>
		</section>

		<RankingTabsTable :tabs="settings.tabs" @save="saveTabs" @edit="openTabSettings" />

		<div data-cy="fgv-open-rank-display-position">
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
.fgvOpenRankSettings__loading {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	padding: 2rem;
}

.fgvOpenRankSettings {
	display: flex;
	flex-direction: column;
	gap: 1.5rem;
}

.fgvOpenRankSettings__intro {
	--intro-heading: #183f7a;
	--intro-text: #2f3438;
	--intro-muted: #49627d;
	--intro-border: #c8d4df;
	--intro-surface: #f5f8fa;
	color: var(--intro-text);
	font-size: 14px;
	line-height: 1.6;
}

.fgvOpenRankSettings__intro h2 {
	margin: 0 0 0.75rem;
	color: var(--intro-heading);
	font-size: 1.125rem;
	font-weight: 700;
}

.fgvOpenRankSettings__intro p {
	margin: 0 0 0.75rem;
}

.fgvOpenRankSettings__intro p.fgvOpenRankSettings__note {
	margin: 0;
	padding: 1rem;
	border: 1px solid var(--intro-border);
	border-inline-start-width: 4px;
	border-radius: 4px;
	background: var(--intro-surface);
	color: var(--intro-muted);
}
</style>

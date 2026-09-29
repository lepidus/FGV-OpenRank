<template>
	<div v-if="!form" class="rankingTabSettings__loading">
		<PkpSpinner />
		{{ t('common.loading') }}
	</div>
	<div v-else class="rankingTabSettings" :data-cy="`ranking-plugin-tab-form-${tabId}`">
		<PkpForm v-bind="form" @set="setForm" @success="notify(t('common.changesSaved'), 'success')" />
		<RankingTrendingDois
			v-if="tabId === 'trending'"
			:settings-api-url="settingsApiUrl"
			:trending-doi-url="trendingDoiUrl"
		/>
	</div>
</template>

<script setup>
import {ref, watch} from 'vue';
import RankingTrendingDois from './RankingTrendingDois.vue';

const props = defineProps({
	settingsApiUrl: {type: String, required: true},
	trendingDoiUrl: {type: String, required: true},
	tabId: {type: String, required: true},
});

const {t} = pkp.modules.useLocalize.useLocalize();
const {notify} = pkp.modules.useNotify.useNotify();
const {useFetch} = pkp.modules.useFetch;

const form = ref(null);

const {data, fetch: fetchSettings} = useFetch(props.settingsApiUrl);
watch(data, (settings) => {
	form.value = settings?.tabForms[props.tabId] ?? null;
});
fetchSettings();

function setForm(formId, changes) {
	form.value = {...form.value, ...changes};
}
</script>

<style>
.rankingTabSettings__loading {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.rankingTabSettings {
	display: flex;
	flex-direction: column;
	gap: 2rem;
}
</style>

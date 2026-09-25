<template>
	<div v-if="!form" class="rankingTrendingDoiForm__loading">
		<PkpSpinner />
		{{ t('common.loading') }}
	</div>
	<div v-else data-cy="ranking-plugin-trending-doi-form">
		<PkpForm v-bind="form" @set="setForm" @success="notify(t('common.changesSaved'), 'success')" />
	</div>
</template>

<script setup>
import {ref, watch} from 'vue';

const props = defineProps({
	settingsApiUrl: {type: String, required: true},
	doiId: {type: String, default: ''},
});

const {t} = pkp.modules.useLocalize.useLocalize();
const {notify} = pkp.modules.useNotify.useNotify();
const {useFetch} = pkp.modules.useFetch;

const form = ref(null);

const {data, fetch: fetchSettings} = useFetch(props.settingsApiUrl);
watch(data, (settings) => settings && (form.value = getForm(settings)));
fetchSettings();

function getForm({trendingDoiForm, trendingDois}) {
	const editedDoi = trendingDois.find((item) => String(item.id) === props.doiId);
	if (!editedDoi) {
		return trendingDoiForm;
	}

	return {
		...trendingDoiForm,
		action: `${props.settingsApiUrl}/trendingDois/${editedDoi.id}`,
		method: 'PUT',
		fields: trendingDoiForm.fields.map((field) => (field.name === 'doi' ? {...field, value: editedDoi.doi} : field)),
	};
}

function setForm(formId, changes) {
	form.value = {...form.value, ...changes};
}
</script>

<style>
.rankingTrendingDoiForm__loading {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}
</style>

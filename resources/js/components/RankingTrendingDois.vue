<template>
	<div class="rankingTrendingDois">
		<PkpTable data-cy="ranking-plugin-trending-dois">
			<template #label>{{ t('plugins.generic.rankingPlugin.trendingDois.title') }}</template>
			<template #top-controls>
				<PkpButton
					data-cy="ranking-plugin-trending-doi-add"
					:is-disabled="!doiForm || !!activeForm"
					@click="openDoiForm()"
				>
					{{ t('plugins.generic.rankingPlugin.trendingDois.add') }}
				</PkpButton>
			</template>
			<PkpTableHeader>
				<PkpTableColumn>
					<span class="sr-only">{{ t('common.order') }}</span>
				</PkpTableColumn>
				<PkpTableColumn>{{ t('plugins.generic.rankingPlugin.trendingDois.doi') }}</PkpTableColumn>
				<PkpTableColumn>
					<span class="sr-only">{{ t('common.moreActions') }}</span>
				</PkpTableColumn>
			</PkpTableHeader>
			<PkpTableBody>
				<PkpTableRow v-for="(item, index) in dois" :key="item.id" data-cy="ranking-plugin-trending-doi">
					<PkpTableCell>
						<RankingOrderButtons
							:label="item.doi"
							:is-first="index === 0"
							:is-last="index === dois.length - 1"
							@up="move(index, -1)"
							@down="move(index, 1)"
						/>
					</PkpTableCell>
					<PkpTableCell :is-row-header="true">{{ item.doi }}</PkpTableCell>
					<PkpTableCell>
						<div class="rankingTrendingDois__actions">
							<PkpButton data-cy="ranking-plugin-trending-doi-edit" @click="openDoiForm(item)">
								{{ t('common.edit') }}
							</PkpButton>
							<PkpButton
								:is-warnable="true"
								data-cy="ranking-plugin-trending-doi-delete"
								@click="confirmDelete(item)"
							>
								{{ t('common.delete') }}
							</PkpButton>
						</div>
					</PkpTableCell>
				</PkpTableRow>
			</PkpTableBody>
		</PkpTable>

		<div v-if="activeForm" class="rankingTrendingDois__form" data-cy="ranking-plugin-trending-doi-form">
			<PkpForm v-bind="activeForm" @set="setActiveForm" @success="doiFormSuccess" />
			<PkpButton data-cy="ranking-plugin-trending-doi-cancel" @click="activeForm = null">
				{{ t('common.cancel') }}
			</PkpButton>
		</div>
	</div>
</template>

<script setup>
import {ref, watch} from 'vue';
import RankingOrderButtons from './RankingOrderButtons.vue';

const props = defineProps({
	settingsApiUrl: {type: String, required: true},
});

const {t} = pkp.modules.useLocalize.useLocalize();
const {useFetch} = pkp.modules.useFetch;
const {openDialog} = pkp.modules.useModal.useModal();

const trendingDoisApiUrl = `${props.settingsApiUrl}/trendingDois`;
const dois = ref([]);
const doiForm = ref(null);
const activeForm = ref(null);

const {data, fetch: fetchSettings} = useFetch(props.settingsApiUrl);
watch(data, (newData) => newData && setSettings(newData));
fetchSettings();

function setSettings(settings) {
	dois.value = settings.trendingDois;
	doiForm.value = settings.trendingDoiForm;
}

async function send(url, method, body = undefined) {
	const {data: settings, fetch: request} = useFetch(url, {method, body});
	await request();
	if (settings.value) {
		setSettings(settings.value);
	} else {
		fetchSettings();
	}
}

function openDoiForm(item = null) {
	const formConfig = JSON.parse(JSON.stringify(doiForm.value));
	if (item) {
		formConfig.action = `${trendingDoisApiUrl}/${item.id}`;
		formConfig.method = 'PUT';
		formConfig.fields = formConfig.fields.map((field) =>
			field.name === 'doi' ? {...field, value: item.doi} : field,
		);
	}
	activeForm.value = formConfig;
}

function setActiveForm(formId, changes) {
	if (activeForm.value) {
		activeForm.value = {...activeForm.value, ...changes};
	}
}

function doiFormSuccess(settings) {
	activeForm.value = null;
	setSettings(settings);
}

function confirmDelete(item) {
	openDialog({
		title: t('common.delete'),
		message: t('plugins.generic.rankingPlugin.trendingDois.deleteConfirm'),
		actions: [
			{
				label: t('common.delete'),
				isWarnable: true,
				callback: async (close) => {
					await send(`${trendingDoisApiUrl}/${item.id}`, 'DELETE');
					close();
				},
			},
			{
				label: t('common.cancel'),
				callback: (close) => close(),
			},
		],
	});
}

function move(index, direction) {
	const reordered = [...dois.value];
	[reordered[index], reordered[index + direction]] = [reordered[index + direction], reordered[index]];
	dois.value = reordered;
	send(`${trendingDoisApiUrl}/order`, 'PUT', {ids: reordered.map((item) => item.id)});
}
</script>

<style>
.rankingTrendingDois {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

.rankingTrendingDois__actions {
	display: flex;
	justify-content: flex-end;
	gap: 0.5rem;
}

.rankingTrendingDois__form {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 0.5rem;
}
</style>

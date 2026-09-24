<template>
	<div class="rankingTabSettings" :data-cy="`ranking-plugin-tab-form-${tab.id}`">
		<div class="rankingTabSettings__header">
			<PkpButton data-cy="ranking-plugin-tab-back" @click="emit('back')">
				{{ t('common.back') }}
			</PkpButton>
			<h2>{{ tab.customTitle || tab.label }}</h2>
		</div>
		<PkpForm v-bind="form" @set="set" @success="emit('saved')" />
		<RankingTrendingDois v-if="tab.id === 'trending'" :settings-api-url="settingsApiUrl" />
	</div>
</template>

<script setup>
import RankingTrendingDois from './RankingTrendingDois.vue';

const props = defineProps({
	tab: {type: Object, required: true},
	formConfig: {type: Object, required: true},
	settingsApiUrl: {type: String, required: true},
});
const emit = defineEmits(['saved', 'back']);

const {t} = pkp.modules.useLocalize.useLocalize();
const {form, set} = pkp.modules.useForm.useForm(props.formConfig);
</script>

<style>
.rankingTabSettings {
	display: flex;
	flex-direction: column;
	gap: 1.5rem;
}

.rankingTabSettings__header {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.rankingTabSettings__header h2 {
	margin: 0;
	font-size: 1.125rem;
	font-weight: 700;
}
</style>

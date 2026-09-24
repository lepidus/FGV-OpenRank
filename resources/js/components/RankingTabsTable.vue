<template>
	<PkpTable data-cy="ranking-plugin-tabs">
		<template #label>{{ t('plugins.generic.rankingPlugin.configuration.tabs.title') }}</template>
		<PkpTableHeader>
			<PkpTableColumn>
				<span class="sr-only">{{ t('common.order') }}</span>
			</PkpTableColumn>
			<PkpTableColumn>
				{{ t('plugins.generic.rankingPlugin.configuration.grid.column.enabled') }}
			</PkpTableColumn>
			<PkpTableColumn>
				{{ t('plugins.generic.rankingPlugin.configuration.grid.column.defaultTitle') }}
			</PkpTableColumn>
			<PkpTableColumn>
				{{ t('plugins.generic.rankingPlugin.configuration.grid.column.customTitle') }}
			</PkpTableColumn>
			<PkpTableColumn>
				{{ t('plugins.generic.rankingPlugin.configuration.grid.column.customDescription') }}
			</PkpTableColumn>
			<PkpTableColumn>
				<span class="sr-only">{{ t('common.moreActions') }}</span>
			</PkpTableColumn>
		</PkpTableHeader>
		<PkpTableBody>
			<PkpTableRow v-for="(tab, index) in tabs" :key="tab.id" :data-cy="`ranking-plugin-tab-${tab.id}`">
				<PkpTableCell>
					<RankingOrderButtons
						:label="tab.label"
						:is-first="index === 0"
						:is-last="index === tabs.length - 1"
						@up="move(index, -1)"
						@down="move(index, 1)"
					/>
				</PkpTableCell>
				<PkpTableCell>
					<input
						type="checkbox"
						:checked="tab.enabled"
						:aria-label="`${t('plugins.generic.rankingPlugin.configuration.grid.column.enabled')}: ${tab.label}`"
						:data-cy="`ranking-plugin-tab-enabled-${tab.id}`"
						@change="toggle(index, $event.target.checked)"
					/>
				</PkpTableCell>
				<PkpTableCell :is-row-header="true">{{ tab.label }}</PkpTableCell>
				<PkpTableCell>{{ tab.customTitle }}</PkpTableCell>
				<PkpTableCell>{{ tab.customDescription }}</PkpTableCell>
				<PkpTableCell>
					<PkpButton :data-cy="`ranking-plugin-tab-edit-${tab.id}`" @click="emit('edit', tab)">
						{{ t('common.edit') }}
					</PkpButton>
				</PkpTableCell>
			</PkpTableRow>
		</PkpTableBody>
	</PkpTable>
</template>

<script setup>
import RankingOrderButtons from './RankingOrderButtons.vue';

const props = defineProps({
	tabs: {type: Array, required: true},
});
const emit = defineEmits(['save', 'edit']);

const {t} = pkp.modules.useLocalize.useLocalize();

function toggle(index, enabled) {
	emit('save', props.tabs.map((tab, i) => (i === index ? {...tab, enabled} : tab)));
}

function move(index, direction) {
	const tabs = [...props.tabs];
	[tabs[index], tabs[index + direction]] = [tabs[index + direction], tabs[index]];
	emit('save', tabs);
}
</script>

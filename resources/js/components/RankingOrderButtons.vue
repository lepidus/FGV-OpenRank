<template>
	<div class="rankingOrderButtons">
		<button
			type="button"
			class="rankingOrderButtons__button"
			:disabled="isFirst"
			:aria-label="`${t('common.orderUp')}: ${label}`"
			data-cy="ranking-plugin-order-up"
			@click="emit('up')"
		>
			<PkpIcon icon="ChevronUp" :aria-hidden="true" />
		</button>
		<button
			type="button"
			class="rankingOrderButtons__button"
			:disabled="isLast"
			:aria-label="`${t('common.orderDown')}: ${label}`"
			data-cy="ranking-plugin-order-down"
			@click="emit('down')"
		>
			<PkpIcon icon="ChevronDown" :aria-hidden="true" />
		</button>
	</div>
</template>

<script setup>
defineProps({
	label: {type: String, required: true},
	isFirst: {type: Boolean, default: false},
	isLast: {type: Boolean, default: false},
});
const emit = defineEmits(['up', 'down']);

const {t} = pkp.modules.useLocalize.useLocalize();
</script>

<style>
.rankingOrderButtons {
	display: flex;
	align-items: center;
	gap: 0.25rem;
}

.rankingOrderButtons__button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 2rem;
	height: 2rem;
	padding: 0;
	border: 0;
	border-radius: 4px;
	background: transparent;
	color: #006798;
	cursor: pointer;
}

.rankingOrderButtons__button:hover:not(:disabled),
.rankingOrderButtons__button:focus-visible {
	background: #006798;
	color: #fff;
}

.rankingOrderButtons__button:disabled {
	color: #bbb;
	cursor: default;
}

.rankingOrderButtons__button svg {
	width: 1.5rem;
	height: 1.5rem;
}
</style>

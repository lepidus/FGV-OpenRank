export function useSettingsModal() {
	const {openSideModal} = pkp.modules.useModal.useModal();

	function openSettingsModal({title, url, params = {}, formId, onClose}) {
		const modalUrl = new URL(url, window.location.href);
		Object.entries(params).forEach(([name, value]) => modalUrl.searchParams.set(name, value));

		openSideModal(
			'LegacyAjax',
			{legacyOptions: {title, url: modalUrl.toString(), closeOnFormSuccessId: formId}},
			{onClose},
		);
	}

	return {openSettingsModal};
}

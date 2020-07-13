(function($){
	$(document).ready(function () {
		let bodyField = $('body');
		bodyField.on('click','[data-command="copy"]',function () {
			let prevKey = $(this).parents('tr').find('input').val();
			let newKey = parseInt(prevKey) + 1;
			$('[name="cookieGroups['+prevKey+'][key]"]').val(newKey);
		});
		$('body [name$="[key]"]').prop('readonly',"true");

		removeDeleteButtons();

		// Select the node that will be observed for mutations
		const targetNode = document.getElementById('top');

		// Options for the observer (which mutations to observe)
		const config = { attributes: true, childList: true, subtree: true };

		// Callback function to execute when mutations are observed
		const callback = function(mutationsList) {
			// Use traditional 'for loops' for IE 11
			for(let mutation of mutationsList) {
				if (mutation.type === 'attributes' && mutation.attributeName === 'id')
					removeDeleteButtons();
			}
		};

		// Create an observer instance linked to the callback function
		const observer = new MutationObserver(callback);

		// Start observing the target node for configured mutations
		observer.observe(targetNode, config);

		function removeDeleteButtons() {
			$('#table_fieldpalette_cookieTools tbody tr').each(function () {

				let cookieField = $(this).find('.tl_td_content_left');
				let groupName = cookieField.find('span').text().trim();
				let cookieName = cookieField.text().trim().replace(groupName,'');
				cookieName = cookieName.trim();
				cookieName = cookieName.toLowerCase();
				let notRemove = [
					'contao csrf token',
					'contao https csrf token',
					'php session id'
				];
				if (
					notRemove.includes(cookieName)
				) {
					let removeButton = $(this).find('.tl_td_content_right .delete');
					if (removeButton.length > 0) {
						removeButton.remove();

					}
				}
			});
		}
	});
})(jQuery);
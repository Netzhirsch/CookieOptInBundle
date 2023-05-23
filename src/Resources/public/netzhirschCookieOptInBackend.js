(function($){
	$.noConflict();
	$(document).ready(function () {
		let bodyField = $('body');
		let newKey = 1;
		bodyField.on('click','[data-command="copy"]',function () {
			$(this).parents('tbody').find('[name$="[key]"]').each(function () {
				let tmpPrevKey = parseInt($(this).val());
				if (newKey < tmpPrevKey)
					newKey = tmpPrevKey;
			});
			$('[name="cookieGroups['+newKey+'][key]"]').val(++newKey);
		});
		$('body [name$="[key]"]').prop('readonly',"true");

		removeDeleteButtons();

		// on info or edit a tool we need to remove the delete buttons after refresh again.
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
					'php session id',
					'fe user auth',
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
(function($){
	$(document).ready(function () {
		$('body').on('click','[data-command="copy"]',function () {
			let prevKey = $(this).parents('tr').find('input').val();
			let newKey = parseInt(prevKey) + 1;
			$('[name="cookieGroups['+prevKey+'][key]"]').val(newKey);
		});
		$('body [name$="[key]"]').prop('readonly',"true");

	});
})(jQuery);
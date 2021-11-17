define(['jquery'], function($) {
	var $table = $('.translation-status');

	$(document.body).on('input', '.translation-status__checkbox input', function(event) {
		if (this.checked) {
			$table.find('tr > *:nth-child(' + this.value + ')').removeAttr('hidden');
		} else {
			$table.find('tr > *:nth-child(' + this.value + ')').attr('hidden', 'hidden');
		}
	});
});

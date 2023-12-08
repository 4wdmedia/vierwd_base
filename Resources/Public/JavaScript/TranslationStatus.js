import $ from"jquery";

var $table = $('.translation-status');
var $rows = $table.find('tbody').children();
var searchData = $.map($rows, function(row) {
	return row.innerText.toLowerCase();
});

$(document.body).on('change', '[name="_fileSelector"]', function(event) {
	if (this.options[this.selectedIndex].value) {
		window.location.href = this.options[this.selectedIndex].value;
	}
});

$(document.body).on('click', '#check-show-all-translations', function(event) {
	window.location.href = this.dataset.href;
});

$(document.body).on('input', '.translation-status__checkbox input', function(event) {
	if (this.checked) {
		$table.find('tr > *:nth-child(' + this.dataset.index + ')').removeAttr('hidden');
	} else {
		$table.find('tr > *:nth-child(' + this.dataset.index + ')').attr('hidden', 'hidden');
	}
});

var currentSearch = '';
$(document.body).on('input', '.translation-status__filter input', function(event) {
	var search = this.value.toLowerCase();
	if (search !== currentSearch) {
		performSearch(search);
	}
});

function performSearch(search) {
	currentSearch = search;
	$rows.attr('hidden', function(index) {
		if (searchData[index].indexOf(search) !== -1) {
			return false;
		}

		return 'hidden';
	});
}

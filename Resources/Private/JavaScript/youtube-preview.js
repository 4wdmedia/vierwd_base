$(document).on('click', '.youtube-preview__accept', function(event) {
	event.preventDefault();

	var $preview = $(this).closest('.youtube-preview');

	var $iframe = $('<iframe>', {
		attr: {
			width: '100%',
			height: '100%',
			allow: 'autoplay *; fullscreen *; encrypted-media *',
			frameborder: '0',
		},
		prop: {
			allowFullscreen: true,
		},
		src: $preview.data('youtube-url'),
	});
	var $video = $('<div>', {
		'class': 'video',
	}).append($iframe);

	$preview.replaceWith($video);
});

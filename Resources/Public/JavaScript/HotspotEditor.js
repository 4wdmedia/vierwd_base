define(['jquery'], function($) {
	var Hotspot = function Hotspot($hotspot) {
		this.$hotspot = $hotspot;
		this.$status = $hotspot.find('.vierwd-hotspot__status');
		this.$field = $hotspot.find('input');
		this.$image = $hotspot.find('.vierwd-hotspot__image');
		this.$position = $hotspot.find('.vierwd-hotspot__position');
		this.$positions = $();
		this.imageWidth = this.$image.data('width');
		this.imageHeight = this.$image.data('height');

		this.currentVariant = $hotspot.find('.vierwd-hotspot__image-variant').val();

		this.config = $hotspot.data('config') || {};
		if (!$.isPlainObject(this.config)) {
			this.config = {};
		}

		if (!this.config[this.currentVariant]) {
			this.config[this.currentVariant] = {};
		}

		this.updateValue();
	};

	Hotspot.prototype.updateValue = function updateValue() {
		this.$positions.remove();

		if (!this.config[this.currentVariant].type) {
			this.config[this.currentVariant].type = 'position';
		}

		this.$hotspot.find('.vierwd-hotspot__type').val(this.config[this.currentVariant].type);

		if (this.config[this.currentVariant].type === 'position') {
			if (this.config[this.currentVariant].top !== undefined && this.config[this.currentVariant].left !== undefined) {
				this.$position.css({
					display: 'block',
					top: this.config[this.currentVariant].top,
					left: this.config[this.currentVariant].left,
					right: 'auto'
				});
			} else {
				this.$position.css('display', 'none');
			}
		} else if (this.config[this.currentVariant].type === 'multiple') {
			this.$position.css('display', 'none');

			if (!this.config[this.currentVariant].hotspots) {
				this.config[this.currentVariant].hotspots = [];
			}

			var $positionTemplate = this.$position;
			var $positions = $();
			$.each(this.config[this.currentVariant].hotspots, function(index, hotspot) {
				var $position = $positionTemplate.clone().css({
					display: 'block',
					top: hotspot.top,
					left: hotspot.left,
					right: 'auto'
				});
				$position.attr('title', index);
				$position.data('index', index);
				$position.insertAfter($positionTemplate);
				$positions = $positions.add($position);
			});
			this.$positions = $positions;
		} else if (this.config[this.currentVariant].type === 'top-left') {
			this.$position.css({
				display: 'block',
				top: 33,
				left: 33,
				right: 'auto',
				bottom: 'auto'
			});
		} else if (this.config[this.currentVariant].type === 'top-right') {
			this.$position.css({
				display: 'block',
				top: 33,
				left: 'auto',
				right: 33,
				bottom: 'auto'
			});
		} else if (this.config[this.currentVariant].type === 'bottom-right') {
			this.$position.css({
				display: 'block',
				top: 'auto',
				left: 'auto',
				right: 33,
				bottom: 33,
			});
		} else if (this.config[this.currentVariant].type === 'bottom-left') {
			this.$position.css({
				display: 'block',
				top: 'auto',
				left: 33,
				right: 'auto',
				bottom: 33,
			});
		} else {
			this.$position.css('display', 'none');
		}

		this.$field.val(JSON.stringify(this.config));
	};

	Hotspot.prototype.updateImage = function updateImage(variant, $option) {
		this.currentVariant = variant;
		if (!this.config[this.currentVariant]) {
			this.config[this.currentVariant] = {};
		}

		this.$image.attr('src', $option.data('image'));
		this.imageWidth = $option.data('width');
		this.imageHeight = $option.data('height');

		this.updateValue();
	};

	Hotspot.prototype.updateHotspotPosition = function updateHotspotPosition(x, y) {
		if ($.inArray(this.config[this.currentVariant].type, ['position', 'multiple']) === -1) {
			return;
		}

		this.$status.text('X: ' + x + ', Y: ' + y);
	};

	Hotspot.prototype.updateType = function updateType(type) {
		this.config[this.currentVariant] = {type: type};
		this.updateValue();
	};

	Hotspot.prototype.addHotspot = function addHotspot(x, y) {
		x = x / this.$image.width() * this.imageWidth;
		y = y / this.$image.height() * this.imageHeight;
		var top = Math.round(y / this.imageHeight * 100) + '%';
		var left = Math.round(x / this.imageWidth * 100) + '%';

		if (this.config[this.currentVariant].type === 'multiple') {
			this.config[this.currentVariant].hotspots.push({
				top: top,
				left: left
			});
		} else {
			this.config[this.currentVariant].top = top;
			this.config[this.currentVariant].left = left;
		}

		this.updateValue();
	};

	Hotspot.prototype.removeHotspot = function removeHotspot(index) {
		if (this.config[this.currentVariant].type === 'multiple') {
			this.config[this.currentVariant].hotspots.splice(parseInt(index), 1);
		} else {
			this.config[this.currentVariant].top = undefined;
			this.config[this.currentVariant].left = undefined;
		}

		this.updateValue();
	};

	$.fn.hotspot = function(fn) {
		if (this.length > 1 && $(this).hasClass('vierwd-hotspot')) {
			$(this).each(function() {
				$(this).hotspot(fn);
			});
			return this;
		}
		var $hotspot = $(this).closest('.vierwd-hotspot');
		var hotspot = $hotspot.data('hotspot');
		if (!hotspot) {
			hotspot = new Hotspot($hotspot);
			$hotspot.data('hotspot', hotspot);
		}

		if (!fn) {
			return this;
		}

		return hotspot[fn].apply(hotspot, Array.prototype.slice.call(arguments, 1));
	};

	$(document).on('mousemove', '.vierwd-hotspot__image', function(event) {
		$(this).hotspot('updateHotspotPosition', event.offsetX, event.offsetY);
	});

	$(document).on('mouseleave', '.vierwd-hotspot__image', function(event) {
		$(this).closest('.vierwd-hotspot').find('.vierwd-hotspot__status').text('');
	});

	$(document).on('change', '.vierwd-hotspot__type', function(event) {
		$(this).hotspot('updateType', $(this).val());
	});

	$(document).on('change', '.vierwd-hotspot__image-variant', function(event) {
		$(this).hotspot('updateImage', $(this).val(), $(this).find(':selected'));
	});

	$(document).on('click', '.vierwd-hotspot__image', function(event) {
		$(this).hotspot('addHotspot', event.offsetX, event.offsetY);
	});

	$(document).on('click', '.vierwd-hotspot__position', function(event) {
		$(this).hotspot('removeHotspot', $(this).data('index'));
	});

	$(document).ready(function() {
		$('.vierwd-hotspot').hotspot();
	});
});

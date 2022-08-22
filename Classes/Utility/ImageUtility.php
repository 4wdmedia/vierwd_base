<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;

use function Safe\json_decode;
use function Safe\json_encode;

class ImageUtility {

	/**
	 * Get the crop settings for this image. This method always returns
	 * a crop, even if no crop is defined. In that case, the crop is the
	 * full image.
	 *
	 * @return array{'x': int, 'y': int, 'width': int, 'height': int}
	 */
	public static function getCrop(FileInterface $image, string $cropAreaName = 'default'): array {
		$emptyCrop = [
			'x' => 0,
			'y' => 0,
			'width' => (int)$image->getProperty('width'),
			'height' => (int)$image->getProperty('height'),
		];

		$crop = $image->getProperty('crop');
		if (!$crop) {
			return $emptyCrop;
		}

		$cropVariantCollection = CropVariantCollection::create($crop);
		$cropArea = $cropVariantCollection->getCropArea($cropAreaName);
		if ($cropArea->isEmpty()) {
			return $emptyCrop;
		}
		/** @phpstan-var array{'x': float, 'y': float, 'width': float, 'height': float} $crop */
		$crop = $cropArea->makeAbsoluteBasedOnFile($image)->asArray();
		$crop = array_map('intval', array_map('round', $crop));
		if ($crop['x'] < 0) {
			$crop['x'] = 0;
		}

		if ($crop['y'] < 0) {
			$crop['y'] = 0;
		}
		return $crop;
	}

	/**
	 * @param array{'x': int, 'y': int, 'width': int, 'height': int} $cropValues
	 */
	public static function convertCropForProcessing(FileInterface $image, ?array $cropValues): ?string {
		if ($cropValues === null) {
			return null;
		}
		if ($cropValues['x'] === 0 && $cropValues['y'] === 0 && $cropValues['width'] === (int)$image->getProperty('width') && $cropValues['height'] === (int)$image->getProperty('height')) {
			// If the crop is the full image, it's faster to use "no crop".
			return null;
		}

		$encoded = json_encode($cropValues);

		return $encoded;
	}

	/**
	 * @param int[] $size width and height
	 * @return int[] corrected width and height
	 */
	public static function adjustSizeForCrop(array $size, array $crop): array {
		$width = $size[0];
		$height = $size[1];
		$ratio = $width / $height;

		if ($width > $crop['width']) {
			$width = $crop['width'];
			$height = $width / $ratio;
		}
		if ($height > $crop['height']) {
			$height = $crop['height'];
			$width = $height * $ratio;
		}

		return [(int)round($width), (int)round($height)];
	}

	/**
	 * get the hotspot configuration from an image
	 *
	 * @return array $hotspot This will be an array with the following keys:
	 *  - type: position, multiple, top-left, top-right, bottom-right
	 *  - top (only for `type` "position"): position in percent (with percentage sign)
	 *  - left (only for `type` "position"): position in percent (with percentage sign)
	 *  - hotspots (only for `type` "multiple"): array with multple hotspots, each with top and left in percent
	 */
	public static function getHotspot(FileInterface $image): array {
		$hotspot = $image->getProperty('hotspot');
		if ($hotspot) {
			$hotspot = json_decode($hotspot, true);
		}

		if (!$hotspot) {
			$hotspot = [
				'type' => 'position',
				'top' => '50%',
				'left' => '50%',
			];
		} else {
			assert(is_array($hotspot));
			assert(array_key_exists('default', $hotspot));
			assert(array_key_exists('type', $hotspot['default']));
			// only work with default crop
			$hotspot = $hotspot['default'];
		}


		$cropValues = self::getCrop($image);

		/** @var int $width */
		$width = $image->getProperty('width');
		/** @var int $height */
		$height = $image->getProperty('height');

		if ($hotspot['type'] === 'position') {
			$backgroundPosition = $hotspot['left'] . ' ' . $hotspot['top'];
			// y = (y2 - y1)/(x2 - x1) * (x - x1) + y1
			$x1 = 0;
			$y1 = -100;
			$x2 = 100;
			$y2 = 100;
			$yOffset = ($y2 - $y1) / ($x2 - $x1) * (intval($hotspot['top']) - $x1) + $y1;
			$xOffset = ($y2 - $y1) / ($x2 - $x1) * (intval($hotspot['left']) - $x1) + $y1;

			$hotspot['xOffset'] = ($xOffset >= 0 ? '+' : '') . $xOffset;
			$hotspot['yOffset'] = ($yOffset >= 0 ? '+' : '') . $yOffset;
			$hotspot['backgroundPosition'] = $backgroundPosition;
		}

		if (in_array($hotspot['type'], ['position', 'multiple'])) {
			// adjust top/left values for crop

			if ($hotspot['type'] === 'position') {
				$x = intval($hotspot['left']) / 100 * $cropValues['width'] + $cropValues['x'];
				$left = round($x / $width * 100) . '%';
				$y = intval($hotspot['top']) / 100 * $cropValues['height'] + $cropValues['y'];
				$top = round($y / $height * 100) . '%';

				$hotspot['noCrop']['top'] = $top;
				$hotspot['noCrop']['left'] = $left;
			} else {
				$hotspot['noCrop'] = [];
				foreach ($hotspot['hotspots'] as $index => $point) {
					$x = intval($point['left']) / 100 * $cropValues['width'] + $cropValues['x'];
					$left = round($x / $width * 100) . '%';
					$y = intval($point['top']) / 100 * $cropValues['height'] + $cropValues['y'];
					$top = round($y / $height * 100) . '%';

					$hotspot['noCrop'][$index]['top'] = $top;
					$hotspot['noCrop'][$index]['left'] = $left;
				}
			}
		}

		return $hotspot;
	}

}

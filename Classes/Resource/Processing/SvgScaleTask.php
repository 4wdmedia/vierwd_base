<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Resource\Processing;

use TYPO3\CMS\Core\Resource\Processing\AbstractTask;

/**
 * Helper class to locally perform a resize of an SVG
 */
class SvgScaleTask extends AbstractTask {

	public const CONTEXT_SVGSCALE = 'Image.SvgScale';

	public function getType(): string {
		return 'Image';
	}

	public function getName(): string {
		return 'CropScaleMask';
	}

	public function getTargetFileName(): string {
		return 'svg_' . parent::getTargetFilename();
	}

	protected function isValidConfiguration(array $configuration): bool {
		return isset($configuration['width'], $configuration['height']);
	}

}

<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use EBT\ExtensionBuilder\Domain\Model\Extension;

class LocalizationService extends \EBT\ExtensionBuilder\Service\LocalizationService {

	public function prepareLabelArray(Extension $extension, string $type = 'locallang'): array {
		$labels = parent::prepareLabelArray($extension, $type);
		$labels = array_filter($labels, function(string $key): bool {
			return !str_ends_with($key, '.description');
		}, ARRAY_FILTER_USE_KEY);
		return $labels;
	}

}

<?php

namespace Vierwd\VierwdBase\Hooks;

/**
 * Class which offers TCE main hook functions.
 */
class DataHandler {

	/**
	 * Replace some common encoding errors
	 *
	 * @param string $status
	 * @param string $table The name of the table the data should be saved to
	 * @param int $id The uid of the page we are currently working on
	 * @param array $fieldArray The array of fields and values that have been saved to the datamap
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj) {

		// replace weird unicode chars
		// example: COMBINING DIAERESIS: dots above the previous char.
		// ü => ü
		// http://www.fileformat.info/info/unicode/char/0308/index.htm
		$replacements = [
			"a\xCC\x80" => 'à',
			"A\xCC\x80" => 'À',
			"a\xCC\x81" => 'á',
			"A\xCC\x81" => 'Á',
			"a\xCC\x82" => 'â',
			"A\xCC\x82" => 'Â',
			"a\xCC\x83" => 'ã',
			"A\xCC\x83" => 'Ã',
			"a\xCC\x88" => 'ä',
			"A\xCC\x88" => 'Ä',
			"a\xCC\x8A" => 'å',
			"A\xCC\x8A" => 'Å',

			"e\xCC\x80" => 'è',
			"E\xCC\x80" => 'È',
			"e\xCC\x81" => 'é',
			"E\xCC\x81" => 'É',
			"e\xCC\x82" => 'ê',
			"E\xCC\x82" => 'Ê',
			"e\xCC\x88" => 'ë',
			"E\xCC\x88" => 'Ë',

			"n\xCC\x81" => 'ń',
			"N\xCC\x81" => 'Ń',
			"n\xCC\x83" => 'ñ',
			"N\xCC\x83" => 'Ñ',

			"s\xCC\x8C" => 'š',
			"S\xCC\x8C" => 'Š',

			"o\xCC\x80" => 'ò',
			"O\xCC\x80" => 'Ò',
			"o\xCC\x81" => 'ó',
			"O\xCC\x81" => 'Ó',
			"o\xCC\x82" => 'ô',
			"O\xCC\x82" => 'Ô',
			"o\xCC\x83" => 'õ',
			"O\xCC\x83" => 'Õ',
			"o\xCC\x88" => 'ö',
			"O\xCC\x88" => 'Ö',

			"u\xCC\x80" => 'ù',
			"U\xCC\x80" => 'Ù',
			"u\xCC\x81" => 'ú',
			"U\xCC\x81" => 'Ú',
			"u\xCC\x82" => 'û',
			"U\xCC\x82" => 'Û',
			"u\xCC\x88" => 'ü',
			"U\xCC\x88" => 'Ü',

			"y\xCC\x80" => 'ỳ',
			"Y\xCC\x80" => 'Ỳ',
			"y\xCC\x81" => 'ý',
			"Y\xCC\x81" => 'Ý',
			"y\xCC\x83" => 'ỹ',
			"Y\xCC\x83" => 'Ỹ',
			"y\xCC\x88" => 'ÿ',
			"Y\xCC\x88" => 'Ÿ',
		];

		$search = array_keys($replacements);
		$replace = array_values($replacements);
		foreach ($fieldArray as &$value) {
			if (is_string($value)) {
				$value = str_replace($search, $replace, $value);
			}
		}
	}

}

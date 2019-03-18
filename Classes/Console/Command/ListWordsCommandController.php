<?php
declare(strict_types=1);

namespace Vierwd\VierwdBase\Console\Command;

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ListWordsCommandController extends CommandController {

	/**
	 * list all words used on the website
	 */
	public function runCommand() {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');

		$queryBuilder = $connection->createQueryBuilder();
		$result = $queryBuilder->selectLiteral('DISTINCT header')->from('tt_content')->where('header!=""')->execute();
		$headers = $result->fetchAll();
		$headers = array_column($headers, 'header');

		$queryBuilder = $connection->createQueryBuilder();
		$result = $queryBuilder->selectLiteral('DISTINCT bodytext')->from('tt_content')->where('bodytext!=""')->execute();
		$texts = $result->fetchAll();
		$texts = array_column($texts, 'bodytext');

		$words = [];
		array_walk($headers, function($header) use (&$words) {
			$header = str_replace(html_entity_decode('&shy;', 0, 'UTF-8'), '', $header);
			$headerWords = array_map('trim', preg_split('/\b/u', $header));
			foreach ($headerWords as $word) {
				$words[$word] = mb_strlen($word);
			}
		});

		array_walk($texts, function($text) use (&$words) {
			$text = str_replace(html_entity_decode('&shy;', 0, 'UTF-8'), '', $text);
			$text = strip_tags($text);
			$textWords = array_map('trim', preg_split('/\b/u', $text));
			foreach ($textWords as $word) {
				$words[$word] = mb_strlen($word);
			}
		});

		$words = array_filter($words, function($length) {
			return $length > 7;
		});

		$words = array_keys($words);
		$words = array_combine($words, $words);

		// get current hypenation
		$currentHypenationWords = $this->getHyphenationWords();
		$words = $currentHypenationWords + $words;

		uksort($words, function($word1, $word2) {
			return mb_strlen($word2) - mb_strlen($word1);
		});

		echo implode("\n", $words);
		echo "\n";
	}

	protected function getHyphenationWords(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_vierwdbase_hyphenation');
		$queryBuilder->select('*')->from('tx_vierwdbase_hyphenation');
		$hyphenationRows = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

		$configuration = implode("\n", array_map(function($hyphenationRow) {
			return $hyphenationRow['hyphenation'];
		}, $hyphenationRows));
		$words = array_map('trim', explode("\n", $configuration));
		$words = array_filter($words);

		$replacements = [];
		foreach ($words as $word) {
			$replacements[trim(str_replace(['#', '|', '•', '•'], '', $word))] = trim(str_replace(['#', '|', '•'], '•', $word));
		}

		return $replacements;
	}
}

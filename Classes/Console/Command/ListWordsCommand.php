<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
	name: 'vierwd:list-words',
	description: 'List all words used on the website',
)]
class ListWordsCommand extends Command {

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionByName('Default');

		$queryBuilder = $connection->createQueryBuilder();
		$headers = $queryBuilder->selectLiteral('DISTINCT header')
			->from('tt_content')
			->where('header!=""')
			->executeQuery()
			->fetchAllAssociative();
		$headers = array_column($headers, 'header');

		$queryBuilder = $connection->createQueryBuilder();
		$texts = $queryBuilder->selectLiteral('DISTINCT bodytext')
			->from('tt_content')
			->where('bodytext!=""')
			->andWhere($queryBuilder->expr()->isNotNull('bodytext'))
			->executeQuery()
			->fetchAllAssociative();
		$texts = array_column($texts, 'bodytext');

		foreach ($GLOBALS['TCA'] as $tableName => $tableConfig) {
			if (!str_starts_with($tableName, 'tx_vierwd') || $tableName === 'tx_vierwdbase_hyphenation') {
				continue;
			}

			$rteFields = [];
			foreach ($tableConfig['columns'] as $columnName => $columnConfig) {
				if (!isset($columnConfig['config'], $columnConfig['config']['type']) || $columnConfig['config']['type'] !== 'text') {
					continue;
				}

				if (($columnConfig['config']['rows'] ?? 1) < 2 && empty($columnConfig['config']['enableRichtext'])) {
					continue;
				}
				$rteFields[] = $columnName;
			}

			foreach ($rteFields as $rteField) {
				$queryBuilder = $connection->createQueryBuilder();
				$rteTexts = $queryBuilder->select($rteField . ' AS text')
					->distinct()
					->from($tableName)
					->andWhere($queryBuilder->expr()->neq($rteField, $queryBuilder->createNamedParameter('')))
					->andWhere($queryBuilder->expr()->isNotNull($rteField))
					->executeQuery()
					->fetchAllAssociative()
				;
				$rteTexts = array_column($rteTexts, 'text');
				$texts = array_merge($texts, $rteTexts);
			}
		}
		$texts = array_filter(array_unique($texts));

		$words = [];
		array_walk($headers, function(string $header) use (&$words): void {
			$header = str_replace(html_entity_decode('&shy;', 0, 'UTF-8'), '', $header);
			$headerWords = array_map('trim', (array)preg_split('/\b/u', $header));
			foreach ($headerWords as $word) {
				$words[$word] = mb_strlen((string)$word);
			}
		});

		array_walk($texts, function(string $text) use (&$words): void {
			$text = str_replace(html_entity_decode('&shy;', 0, 'UTF-8'), '', $text);
			// strip_tags removes all tags and might "join" words together:
			// "Line<br>Line 2" would become "LineLine 2".
			// We prepend a space before tags, to prevent those joined words
			$text = str_replace('<', ' <', $text);
			$text = strip_tags($text);
			$text = html_entity_decode($text);
			$text = preg_replace('/\s+/u', ' ', $text);
			assert(is_string($text));
			$textWords = array_map('trim', (array)preg_split('/\b/u', $text));
			foreach ($textWords as $word) {
				$words[$word] = mb_strlen((string)$word);
			}
		});

		$words = array_filter($words, function($length, $word) {
			return $length > 7 && !is_numeric($word);
		}, ARRAY_FILTER_USE_BOTH);

		$words = array_keys($words);
		$words = (array)array_combine($words, $words);

		// get current hypenation
		$currentHypenationWords = $this->getHyphenationWords();
		$words = $currentHypenationWords + $words;

		uksort($words, function($word1, $word2) {
			return mb_strlen((string)$word2) - mb_strlen((string)$word1);
		});

		echo implode("\n", $words);
		echo "\n";

		return Command::SUCCESS;
	}

	protected function getHyphenationWords(): array {
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_vierwdbase_hyphenation');
		$queryBuilder->select('*')->from('tx_vierwdbase_hyphenation');
		$hyphenationRows = $queryBuilder->executeQuery()->fetchAllAssociative();

		$configuration = implode("\n", array_map(function($hyphenationRow): string {
			assert(is_string($hyphenationRow['hyphenation']));
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

<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Routing\Aspect;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Routing\Aspect\PersistedPatternMapper;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Example:
 *   routeEnhancers:
 *     ProjectPlugin:
 *       type: Extbase
 *       extension: VierwdExample
 *       plugin: projects
 *       defaultController: 'Project::list'
 *       routes:
 *         -
 *           routePath: '/{project}'
 *           _controller: 'Project::show'
 *           _arguments:
 *             project: project
 *       requirements:
 *         project: '\d+'
 *       aspects:
 *         project:
 *           type: AutomaticSlugPatternMapper
 *           tableName: tx_vierwdexample_domain_model_project
 *           routeFieldPattern: '^(?P<title>.+)-(?P<uid>\d+)$'
 *           routeFieldResult: '{title}-{uid}'
 */
class AutomaticSlugPatternMapper extends PersistedPatternMapper {

	use SiteLanguageAwareTrait;

	protected function createRouteResult(?array $result): ?string {
		if ($result === null) {
			return $result;
		}

		$substitutes = [];
		foreach ($this->routeFieldResultNames as $fieldName) {
			if (!isset($result[$fieldName])) {
				return null;
			}
			$routeFieldName = '{' . $fieldName . '}';
			$substitutes[$routeFieldName] = $this->sluggify((string)$result[$fieldName]);
		}
		return str_replace(
			array_keys($substitutes),
			array_values($substitutes),
			$this->routeFieldResult
		);
	}

	/**
	 * @param QueryBuilder $queryBuilder
	 * @param array $values
	 * @param bool $resolveExpansion
	 * @return array
	 */
	protected function createRouteFieldConstraints(QueryBuilder $queryBuilder, array $values, bool $resolveExpansion = false): array {
		if (!isset($values['uid'])) {
			return parent::createFieldConstraints($queryBuilder, $values, $resolveExpansion);
		}

		$constraints = [];
		$constraints[] = $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($values['uid'], \PDO::PARAM_STR));

		return $constraints;
	}

	protected function sluggify(string $value): string {
		static $transliterator = null;
		if ($transliterator === null && class_exists('Transliterator')) {
			$transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower(); [\u0100-\u7fff] remove');
		}

		if ($transliterator) {
			$value = $transliterator->transliterate($value);
		}

		$charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
		$value = mb_strtolower($value);
		// replace accented chars
		$value = $charsetConverter->utf8_char_mapping($value);
		$value = trim($value);

		$value = (string)preg_replace('/\W+/u', '-', $value);
		if (mb_strlen($value) > 50) {
			$value = mb_substr($value, 0, 50);
		}
		// remove leading and trailing -
		$value = trim($value, '-');

		return $value;
	}

	protected function resolveOverlay(?array $record): ?array {
		$record = parent::resolveOverlay($record);
		if (!$record) {
			return $record;
		}

		$currentLanguageId = $this->siteLanguage->getLanguageId();
		if (isset($record['sys_language_uid']) && $record['sys_language_uid'] === $currentLanguageId) {
			return $record;
		}

		$fallbackLanguages = $this->resolveAllRelevantLanguageIds();
		$pageRepository = $this->createPageRepository();
		foreach ($fallbackLanguages as $languageId) {
			if (in_array($languageId, [-1, 0, $currentLanguageId])) {
				continue;
			}

			if ($this->tableName === 'pages') {
				$recordOverlay = $pageRepository->getPageOverlay($record, $languageId);
			} else {
				$recordOverlay = $pageRepository->getRecordOverlay($this->tableName, $record, $languageId) ?: null;
			}

			if (is_array($recordOverlay) && isset($recordOverlay['sys_language_uid']) && $recordOverlay['sys_language_uid'] === $languageId) {
				return $recordOverlay;
			}
		}

		return $record;
	}

}

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
 *           matchFields: ['uid']
 */
class AutomaticSlugPatternMapper extends PersistedPatternMapper {

	use SiteLanguageAwareTrait;

	public function __construct(array $settings) {
		parent::__construct($settings);

		if (!isset($this->settings['matchFields']) || !is_array($this->settings['matchFields'])) {
			$this->settings['matchFields'] = ['uid'];
		}
	}

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
	 * @return array
	 */
	protected function createRouteFieldConstraints(QueryBuilder $queryBuilder, array $values): array {
		// check if all match-fields are set
		if (count($this->settings['matchFields']) !== count(array_intersect($this->settings['matchFields'], array_keys($values)))) {
			// not all fields are set
			return parent::createRouteFieldConstraints($queryBuilder, $values);
		}

		$constraints = [];
		foreach ($this->settings['matchFields'] as $fieldName) {
			$constraints[] = $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($values[$fieldName], \PDO::PARAM_STR));
		}

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
		if (strlen($value) > 50) {
			$value = substr($value, 0, 50);
		}
		// remove leading and trailing -
		$value = trim($value, '-');

		return $value;
	}
}

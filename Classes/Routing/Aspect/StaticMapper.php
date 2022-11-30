<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/**
 * Mark a routing parameter as "static". That means it's not needed for cHash calculation.
 * Example:
 *   routeEnhancers:
 *     RegistrationPlugin:
 *       type: Extbase
 *       extension: VierwdExample
 *       plugin: registration
 *       defaultController: 'Registration::list'
 *       routes:
 *         -
 *           routePath: '/confirm/{registration}/{hash}'
 *           _controller: 'Registration::confirmEmail'
 *       requirements:
 *         registration: \d+
 *         hash: '^[a-f0-9]{32,40}$'
 *       aspects:
 *         registration:
 *           type: StaticMapper
 *         hash:
 *           type: StaticMapper
 */
class StaticMapper implements StaticMappableAspectInterface {

	public function generate(string $value): ?string {
		return $value;
	}

	public function resolve(string $value): ?string {
		return $value;
	}

}

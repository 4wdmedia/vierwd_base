<?php // PHP 7.2
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Routing;

use Symfony\Component\Routing\RouteCollection;

class SiteMatcher extends \TYPO3\CMS\Core\Routing\SiteMatcher {

	protected function createRouteCollectionFromGroupedRoutes(array $groupedRoutes): RouteCollection {
		$collection = parent::createRouteCollectionFromGroupedRoutes($groupedRoutes);
		$finalCollection = new RouteCollection();

		foreach ($collection as $identifier => $route) {
			if (is_link('/Users/' . $_SERVER['USER'] . '/4wdmedia/domains/' . $route->getHost())) {
				$devRoute = clone $route;
				$devRoute->setHost($route->getHost() . '.' . $_SERVER['USER']);
				$finalCollection->add($identifier . '-dev', $devRoute);
			}
			$finalCollection->add($identifier, $route);
		}
		return $finalCollection;
	}
}
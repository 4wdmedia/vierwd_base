<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ContentElements;

use TYPO3\CMS\Core\Authentication\Event\AfterGroupsResolvedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Vierwd\VierwdBase\Hooks\ContentElements;

class ModifyUserGroups {

	public function __invoke(AfterGroupsResolvedEvent $event): void {
		$groups = $event->getGroups();
		foreach ($groups as &$group) {
			if ($group['title'] === 'Redakteur') {
				$contentElements = GeneralUtility::makeInstance(ContentElements::class);
				$group['explicit_allowdeny'] = $contentElements->addContentElementsToAllowList($group['explicit_allowdeny'] ?? '');
			}
		}
		$event->setGroups($groups);
	}

}

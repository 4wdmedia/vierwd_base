<?php

namespace Vierwd\VierwdBase\Install\Controller\Action\Step;

class DatabaseData extends \TYPO3\CMS\Install\Controller\Action\Step\DatabaseData {

	/**
	 * make sure felogin and context_help are not active.
	 * Both are part of the default-system, which is specified in their composer.json (partOfFactoryDefault).
	 * It is not possible to change this setting and it is not possible to overwrite the Package or PackageManager
	 * to change this setting.
	 * This is why we override a Step during the installation which happens AFTER those packages have been activated.
	 */
	public function execute() {
		$packageManager = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance(<%= project.typo3Version < '7.0' ? "'TYPO3\\Flow\\Package\\PackageManager'" : "\TYPO3\CMS\Core\Package\PackageManager::class" %>);
		$packageManager->deactivatePackage('context_help');
		$packageManager->deactivatePackage('felogin');

		return parent::execute();
	}
}
<?php
namespace Vierwd\VierwdBase\Reports;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks for most current TYPO3 Version
 *
 * @author Robert Vock <robert.vock@4wdmedia.de>
 */
class LatestTYPO3 implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @return array<\TYPO3\CMS\Reports\Status>
	 */
	public function getStatus() {
		$severity = \TYPO3\CMS\Reports\Status::OK; // WARNING,ERROR,NOTICE

		$title = 'TYPO3 Update';
		$value = TYPO3_version;
		$message = '';

		$versions = GeneralUtility::getUrl('https://get.typo3.org/json');
		if (!$versions) {
			$message = 'Could not get TYPO3 versions.';
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
		} else {
			$versions = json_decode($versions, true);
			$branch = TYPO3_branch;
			if (!isset($versions[$branch])) {
				// strip off the last part of the version
				$branch = explode('.', $branch);
				array_pop($branch);
				$branch = implode('.', $branch);
			}

			if (!isset($versions[$branch])) {
				$message = 'Unknown TYPO3 branch: ' . TYPO3_branch;
				$severity = \TYPO3\CMS\Reports\Status::ERROR;
			} else if ($versions[$branch]['stable'] != TYPO3_version) {
				$message = 'Not on latest TYPO3 version: ' . $versions[$branch]['stable'];
				$severity = \TYPO3\CMS\Reports\Status::ERROR;
			}
		}

		$report = GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $title, $value, $message, $severity);

		$reports = array(
			$report,
		);
		return $reports;
	}
}

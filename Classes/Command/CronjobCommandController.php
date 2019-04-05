<?php

namespace Vierwd\VierwdBase\Command;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\MVC\Controller\CommandController;

/**
 * A dummy Command Controller with a noop command which simply echoes the argument
 */
class CronjobCommandController extends CommandController {

	/**
	 * Send mails with errors since last run.
	 *
	 * @param string $receiver
	 * @return void
	 */
	public function sendMailsCommand($receiver = 'typo3.reports@4wdmedia.de') {
		$lastRunFile = GeneralUtility::getFileAbsFileName('typo3temp/vierwd_errors.lastrun');

		$lastRun = 0;
		if (file_exists($lastRunFile)) {
			$lastRun = (int)file_get_contents($lastRunFile);
		}

		if (!$lastRun) {
			// no run yet, get the last week
			$lastRun = time() - 86400 * 7;
		}

		$fields = ['uid', 'userid', 'error', 'details', 'IP', 'tstamp', 'workspace'];
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$select = 'sys_log.' . implode(', sys_log.', $fields) . ', be_users.username, COUNT(*) as count',
			$from   = 'sys_log LEFT JOIN be_users ON (be_users.uid=sys_log.userid)',
			$where  = 'sys_log.type=5 AND sys_log.action=0 AND sys_log.tstamp>' . $lastRun,
			$group  = 'sys_log.tstamp, sys_log.userid, sys_log.IP, sys_log.details',
			$order  = 'sys_log.tstamp DESC',
			$limit  = '200'
		);

		if (!$rows) {
			file_put_contents($lastRunFile, time());
			return;
		}

		$distinctErrors = [];
		array_walk($rows, function($row) use (&$distinctErrors) {
			$row['tstamp'] = date('Y-m-d H:i:s', $row['tstamp']);
			if (!$row['username']) {
				$row['username'] = '-';
			}

			$details = $row['details'];

			if (!isset($distinctErrors[$details])) {
				$distinctErrors[$details] = [];
			}

			$distinctErrors[$details][] = $row;
		});

		$mail = '';
		foreach ($distinctErrors as $errorText => $errors) {
			$mail .= $errorText . "\n";
			foreach ($errors as $error) {
				$mail .= '  ' . $error['tstamp'] . ' ' . $error['username'] . ' ' . $error['IP'];
				if ($error['count'] > 1) {
					$mail .= ' (' . $error['count'] . ')';
				}
				$mail .= "\n";
			}
			$mail .= "\n";
		}

		$mailer = new Mailer();
		$message = $mailer->createMessage();

		$message->setFrom($this->getSender());
		$message->setTo($receiver);
		$message->setSubject('TYPO3 Errors on site ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
		$message->setBody($mail);

		if ($mailer->send($message)) {
			file_put_contents($lastRunFile, time());
		}
	}

	protected function getSender() {
		$domain = $this->getDomain();
		if (!$domain) {
			return 'typo3.reports@4wdmedia.de';
		} else {
			return 'noreply@' . $domain;
		}
	}

	protected function getDomain() {
		$domain = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			$select = 'domainName',
			$from   = 'sys_domain',
			$where  = '1' . BackendUtility::BEenableFields('sys_domain'),
			$group  = '',
			$order  = 'sorting',
			$limit  = ''
		);

		return $domain ? $domain['domainName'] : false;
	}
}

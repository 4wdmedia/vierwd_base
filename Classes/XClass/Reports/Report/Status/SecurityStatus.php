<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\XClass\Reports\Report\Status;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Reports\Report\Status\SecurityStatus as ParentSecurityStatus;

/**
 * The status report in TYPO3 checks, if the connection is used via https. If this is not the case,
 * a warning will be shown. We remove this warning in our dev-environment.
 */
class SecurityStatus extends ParentSecurityStatus {

	public function getStatus(ServerRequestInterface $request = null) {
		$statuses = parent::getStatus($request);

		unset($statuses['encryptedConnectionStatus']);
		unset($statuses['getLockSslStatus']);

		return $statuses;
	}
}
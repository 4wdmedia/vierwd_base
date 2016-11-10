<?php

namespace Vierwd\VierwdBase\OAuth2\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Vierwd\VierwdBase\OAuth2\Entities\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface {

	public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity) {
		// Some logic to persist the auth code to a database
	}

	public function revokeAuthCode($codeId) {
		// Some logic to revoke the auth code in a database
	}

	public function isAuthCodeRevoked($codeId) {
		return false; // The auth code has not been revoked
	}

	public function getNewAuthCode() {
		return new AuthCodeEntity();
	}
}

<?php

namespace Vierwd\VierwdBase\OAuth2\Entities;

use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface {
	/**
	 * Return the user's identifier.
	 *
	 * @return mixed
	 */
	public function getIdentifier() {
		return 1;
	}
}

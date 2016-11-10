<?php

namespace Vierwd\VierwdBase\OAuth2\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Vierwd\VierwdBase\OAuth2\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface {
	/**
	 * {@inheritdoc}
	 */
	public function getClientEntity($clientIdentifier, $grantType, $clientSecret = null, $mustValidateSecret = true) {
		$clients = [
			'myawesomeapp' => [
				'secret'          => password_hash('bOqhyZTdIZj8KsdBGd7e9Xey', PASSWORD_BCRYPT),
				'name'            => 'My Awesome App',
				'redirect_uri'    => 'http://www.4wdmedia.rvock.de/typo3/index.php?login_status=login',
				'is_confidential' => true,
			],
		];

		// Check if client is registered
		if (array_key_exists($clientIdentifier, $clients) === false) {
			return;
		}

		if (
			$mustValidateSecret === true
			&& $clients[$clientIdentifier]['is_confidential'] === true
			&& password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false
		) {
			return;
		}

		$client = new ClientEntity();
		$client->setIdentifier($clientIdentifier);
		$client->setName($clients[$clientIdentifier]['name']);
		$client->setRedirectUri($clients[$clientIdentifier]['redirect_uri']);

		return $client;
	}
}

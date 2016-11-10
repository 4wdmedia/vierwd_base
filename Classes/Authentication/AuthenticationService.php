<?php
namespace Vierwd\VierwdBase\Authentication;

use TYPO3\CMS\Core\Utility\GeneralUtility;

// http://www.4wdmedia.rvock.de/typo3/?state=ob8Ib7esyXJ7aCkoxpDDeXdNEMu7RvVz&code=4/_DoRssrT7uh-dP8EbHM9vJ6y-mxJ37eWWyRrOvmzj9Y&authuser=0&session_state=caaf4993f6a2c93dbbf284e5d1cf1e20a3cdc5c6..2613&prompt=consent&login_status=login

class AuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService {

	public function getUser() {
		if ($this->login['status'] !== 'login') {
			return false;
		}

		if (GeneralUtility::_GP('state') && GeneralUtility::_GP('code')) {
			// return from google
			return $this->checkResult();
		}

		// if ($this->login['uident_text'] !== '') {
		// 	// Login via password => no OAuth
		// 	return false;
		// }

		$user = $this->fetchUserRecord($this->login['uname']);
		if (!$user && substr($this->login['uname'], -12) === '@4wdmedia.de') {
			// assume login via OAuth google
			return $this->oAuthLogin();
		}

		return false;
	}

	/**
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * @param array $user Data of user.
	 * @return int >= 200: User authenticated successfully.
	 *                     No more checking is needed by other auth services.
	 *             >= 100: User not authenticated; this service is not responsible.
	 *                     Other auth services will be asked.
	 *             > 0:    User authenticated successfully.
	 *                     Other auth services will still be asked.
	 *             <= 0:   Authentication failed, no more checking needed
	 *                     by other auth services.
	 */
	public function authUser(array $user) {
		if (!empty($user['oauth_authenticated'])) {
			return 200;
		}

		if (substr($user['email'], -12) === '@4wdmedia.de') {
			// FORWARD MEDIA E-Mail address
			return $this->oAuthLogin($user);
		}

		return 100;
	}

	public function oAuthLogin($user = false) {
		$provider = $this->getProvider();

		// If we don't have an authorization code then get one
		$authUrl = $provider->getAuthorizationUrl();

		$_SESSION['oauth2_state'] = $provider->getState();
		if ($user) {
			$_SESSION['oauth2_user'] = $user['uid'];
		} else {
			$_SESSION['oauth2_user'] = false;
		}

		header('Location: ' . $authUrl);
		exit;
	}

	public function checkResult() {
		$provider = $this->getProvider();
		if (GeneralUtility::_GP('state') !== $_SESSION['oauth2_state']) {
			// invalid login
			$_SESSION['oauth2_state'] = null;
			throw new \Exception('Invalid state', 1478774809);
		}

		$token = $provider->getAccessToken('authorization_code', [
			'code' => GeneralUtility::_GP('code')
		]);
		$userDetails = $provider->getResourceOwner($token);

		if (empty($_SESSION['oauth2_user'])) {
			// TODO Create user
			throw new \Exception('User creation using OAuth not yet implemented', 1478774782);
		}

		$user = $this->fetchUserRecord('', 'AND uid=' . (int)$_SESSION['oauth2_user']);
		$user['oauth_authenticated'] = true;

		return $user;
	}

	protected function getProvider() {
		// We are using sessions here, because BE_USER with session is not yet available
		session_start();
		$hostedDomain = 'http' . (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 's' : '') . '://' . GeneralUtility::getIndpEnv('HTTP_HOST');
		$provider = new \League\OAuth2\Client\Provider\Google([
			'clientId'     => '419245696499-7ouqng3n9ip7u4dug2ckb9vk32o8n239.apps.googleusercontent.com',
			'clientSecret' => 'bOqhyZTdIZj8KsdBGd7e9Xey',
			// 'clientId'     => '419245696499-llpn6lutghof220bk8v5t9rugvjrdp4o.apps.googleusercontent.com',
			// 'clientSecret' => '48kK62fcT_xvMUnmBL9JTLfH',
			'redirectUri'  => GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?login_status=login',
			'hostedDomain' => $hostedDomain,
		]);

		return $provider;
	}
}
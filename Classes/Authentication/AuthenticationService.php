<?php
namespace Vierwd\VierwdBase\Authentication;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Vierwd\VierwdBase\OAuth2;

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

		try {
			$token = $provider->getAccessToken('authorization_code', [
				'code' => GeneralUtility::_GP('code')
			]);
		} catch (\Exception $e) {
			debug4wd($e);
		}

debug4wd($token);
		$userDetails = $provider->getResourceOwner($token);

		if (empty($_SESSION['oauth2_user'])) {
			// TODO Create user
			throw new \Exception('User creation using OAuth not yet implemented', 1478774782);
			// user has no DB record (yet), create one using defaults registered in extension config
			// password is not important, username is set to the user's default email address
			// fist though, we need to fetch that information from Google
			$record = [
				'username' => $username,
				'password' => substr(sha1($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . (microtime(TRUE) * time())), -8),
				'realName' => $userDetails->getFirstName() . ' ' . $userDetails->getLastName(),
				'email' => $userDetails->getEmail(),
				'tstamp' => time(),
				'disable' => '0',
				'deleted' => '0',
				'pid' => 0,
				'usergroup' => 1,
				'admin' => 1,
			];
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('be_users', $record);
			$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'be_users', 'uid = ' . intval($uid));
		}

		$user = $this->fetchUserRecord('', 'AND uid=' . (int)$_SESSION['oauth2_user']);
		$user['oauth_authenticated'] = true;

		return $user;
	}

	protected function getProvider() {
		// We are using sessions here, because BE_USER with session is not yet available
		session_start();
		$hostedDomain = 'http' . (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 's' : '') . '://' . GeneralUtility::getIndpEnv('HTTP_HOST');
		$provider = new \League\OAuth2\Client\Provider\GenericProvider([
			'clientId'     => 'myawesomeapp',
			'clientSecret' => 'bOqhyZTdIZj8KsdBGd7e9Xey',
			// 'clientId'     => '419245696499-llpn6lutghof220bk8v5t9rugvjrdp4o.apps.googleusercontent.com',
			// 'clientSecret' => '48kK62fcT_xvMUnmBL9JTLfH',
			'redirectUri'  => GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?login_status=login',
			'urlAuthorize'            => 'http://www.4wdmedia.rvock.de/vierwd_oauth/authorize/',
			'urlAccessToken'          => 'http://www.4wdmedia.rvock.de/vierwd_oauth/token/',
			'urlResourceOwnerDetails' => 'http://www.4wdmedia.rvock.de/vierwd_oauth/resource/'
		]);

		return $provider;
	}

	public function oauthServer(ServerRequestInterface $request, ResponseInterface $response) {
		$action = GeneralUtility::_GP('action');

		if ($action === 'authorize') {
			return $this->respondToAuthorize($request, $response);
		}

		if ($action === 'token') {
			return $this->respondToAccessToken($request, $response);
		}

		// fsmir('Unknown action: ' . $action);
	}

	/**
	 * @return League\OAuth2\Server\AuthorizationServer
	 */
	public function getOAuth2Server() {
		$clientRepository = new OAuth2\Repositories\ClientRepository();
		$scopeRepository = new OAuth2\Repositories\ScopeRepository();
		$accessTokenRepository = new OAuth2\Repositories\AccessTokenRepository();
		$authCodeRepository = new OAuth2\Repositories\AuthCodeRepository();
		$refreshTokenRepository = new OAuth2\Repositories\RefreshTokenRepository();

		$privateKey = GeneralUtility::getFileAbsFileName('EXT:vierwd_base/Resources/Private/OAuth2/private.key');
		$publicKey = GeneralUtility::getFileAbsFileName('EXT:vierwd_base/Resources/Private/OAuth2/public.key');

		// Setup the authorization server
		$server = new \League\OAuth2\Server\AuthorizationServer(
			$clientRepository,
			$accessTokenRepository,
			$scopeRepository,
			$privateKey,
			$publicKey
		);
		// Enable the client credentials grant on the server
		// $server->enableGrantType(
		// 	new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
		// 	new \DateInterval('PT1H') // access tokens will expire after 1 hour
		// );

		// Enable the authentication code grant on the server with a token TTL of 1 hour
		$server->enableGrantType(
			new \League\OAuth2\Server\Grant\AuthCodeGrant(
				$authCodeRepository,
				$refreshTokenRepository,
				new \DateInterval('PT10M')
			),
			new \DateInterval('PT1H')
		);

		return $server;
	}


	public function respondToAuthorize(ServerRequestInterface $request, ResponseInterface $response) {
		$server = $this->getOAuth2Server();

		try {
			// Validate the HTTP request and return an AuthorizationRequest object.
			// The auth request object can be serialized into a user's session
			$authRequest = $server->validateAuthorizationRequest($request);
			// Once the user has logged in set the user on the AuthorizationRequest
			$authRequest->setUser(new OAuth2\Entities\UserEntity());
			// Once the user has approved or denied the client update the status
			// (true = approved, false = denied)
			$authRequest->setAuthorizationApproved(true);
			// Return the HTTP redirect response
			return $server->completeAuthorizationRequest($authRequest, $response);
		} catch (OAuthServerException $exception) {
			// All instances of OAuthServerException can be formatted into a HTTP response

			return $exception->generateHttpResponse($response);
		} catch (\Exception $exception) {
			// Unknown exception
			$body = new Stream('php://temp', 'r+');
			$body->write($exception->getMessage());
			return $response->withStatus(500)->withBody($body);
		}
	}

	public function respondToAccessToken(ServerRequestInterface $request, ResponseInterface $response) {
		$server = $this->getOAuth2Server();

		try {
			return $server->respondToAccessTokenRequest($request, $response);
		} catch (OAuthServerException $exception) {
			return $exception->generateHttpResponse($response);
		} catch (\Exception $exception) {
			$body = new Stream('php://temp', 'r+');
			$body->write($exception->getMessage());
			return $response->withStatus(500)->withBody($body);
		}
	}
}
<?php

namespace PHPAide\Session;

use PHPAide\User\IUser;
use PHPAide\User\UserManager;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class SessionManager {
	private $sessionID = '';
	private $sessionPrefix = '';
	private $userManager;
	private $expires;

	/**
	 * @param UserManager $userManager
	 * @param string $sessionPrefix
	 * @param int|null $expires
	 */
	public function __construct( UserManager $userManager, string $sessionPrefix, int $expires = null ) {
		$this->sessionPrefix = $sessionPrefix;
		$this->userManager = $userManager;
		$this->expires = $expires ?? 3600;
	}

	public function startSession( IUser $user, Response $response ): Response {
		if ( !$user->exists() ) {
			return $response;
		}
		session_start();

		$this->sessionID = session_id();
		$this->setSessionUser( $user );

		$response->addHeader( 'Set-Cookie', $this->getSessionKey() . '=' . $this->getSessionID() );
		return $response;
	}

	public function endSession( IUser $user, Response $response ): Response {
		# Remove cookie
		session_destroy();

		return $response;
	}

	public function loadSession( Request $request ): ?IUser {
		$sessionID = $this->getSessionIDFromRequest( $request );
		if ( !$sessionID ) {
			return null;
		}
		if ( !$this->validSessionID( $sessionID ) ) {
			throw new \Exception( 'Invalid session ID' );
		}

		$this->sessionID = $sessionID;
		session_id( $sessionID );
		session_start();

		return $this->userFromSession();
	}

	public function getSessionID() {
		return $this->sessionID;
	}

	private function getSessionIDFromRequest( Request $request ): ?string {
		return null;
	}

	private function validSessionID( ?string $sessionID ): bool {
		return preg_match( '/^[-,a-zA-Z0-9]{1,128}$/', $sessionID ) > 0;
	}

	private function userFromSession() {
		$userID = isset( $_SESSION[$this->getSessionKey()] )
			? (int)$_SESSION[$this->getSessionKey()] : null;
		if ( !$userID ) {
			return null;
		}

		return $this->userManager->getUserById( $userID );
	}

	private function setSessionUser( IUser $user ) {
		$sessionKey = $this->sessionPrefix . '_user_id';
		$_SESSION[$sessionKey] = $user->getId();
	}

	private function getSessionKey(): string {
		return $this->sessionPrefix . '_user_id';
	}

}

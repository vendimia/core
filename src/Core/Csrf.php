<?php

namespace Vendimia\Core;

use Vendimia\Session\SessionManager;
use Vendimia\Http\Request;
use Stringable;

/**
 * Generator and validator of anti site forgery token
 */
class Csrf implements Stringable
{
    private $token = null;

    public function __construct(
        private SessionManager $session
    )
    {
        if (!$session->vendimia_token) {
            $session->vendimia_token = $this->generateToken();
        } else {
            $this->token = $session->vendimia_token;
        }
    }

    public function generateToken()
    {
        return $this->token = bin2hex(random_bytes(24));
    }

    public function verify($token): bool
    {
        return $this->token === $token;
    }

    /**
     * Verify the token using a Request.
     *
     * Compares the value of the HTTP Request header 'X-Vendimia-Token' with
     * the saved token
     */
    public function verifyFromRequest(Request $request)
    {
        return $this->token === $request->getHeaderLine('X-Vendimia-Token');
    }

    public function getToken()
    {
        return $this->token;
    }

    public function __toString()
    {
        return $this->getToken();
    }
}
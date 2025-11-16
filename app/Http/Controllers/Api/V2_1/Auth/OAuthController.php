<?php

namespace App\Http\Controllers\Api\V2_1\Auth;

use App\Http\Controllers\Api\V2_1\BaseController;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Psr\Http\Message\ServerRequestInterface;

class OAuthController extends BaseController
{
    /**
     * Authorization endpoint (GET).
     * Shows authorization form for OAuth2 authorization code flow.
     *
     * @param ServerRequestInterface $request
     * @param AuthorizationController $controller
     * @return \Illuminate\Http\Response
     */
    public function authorizeOAuth(
        ServerRequestInterface $request,
        AuthorizationController $controller
    ) {
        return $controller->authorize($request);
    }

    /**
     * Authorization endpoint (POST).
     * Handles authorization form submission.
     *
     * @param ServerRequestInterface $request
     * @param AuthorizationController $controller
     * @return \Illuminate\Http\Response
     */
    public function approveOAuth(
        ServerRequestInterface $request,
        AuthorizationController $controller
    ) {
        return $controller->approve($request);
    }

    /**
     * Token endpoint.
     * Issues access tokens for various OAuth2 grant types.
     *
     * Supported grant types:
     * - authorization_code: Exchange authorization code for access token
     * - client_credentials: Server-to-server authentication
     * - password: Resource owner password credentials (use sparingly)
     * - refresh_token: Exchange refresh token for new access token
     *
     * @param ServerRequestInterface $request
     * @param AccessTokenController $controller
     * @return \Illuminate\Http\Response
     */
    public function token(
        ServerRequestInterface $request,
        AccessTokenController $controller
    ) {
        return $controller->issueToken($request);
    }

    /**
     * Refresh token endpoint (convenience wrapper).
     *
     * @param Request $request
     * @param AccessTokenController $controller
     * @param ServerRequestInterface $serverRequest
     * @return \Illuminate\Http\Response
     */
    public function refreshToken(
        Request $request,
        AccessTokenController $controller,
        ServerRequestInterface $serverRequest
    ) {
        // Validate refresh token exists
        if (!$request->has('refresh_token')) {
            return $this->errorResponse('refresh_token is required', 400);
        }

        // Add grant_type to request
        $request->merge(['grant_type' => 'refresh_token']);

        return $controller->issueToken($serverRequest);
    }
}

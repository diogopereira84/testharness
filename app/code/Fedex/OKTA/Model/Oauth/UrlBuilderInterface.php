<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

interface UrlBuilderInterface
{
    public const URL_SUFFIX                     = '/oauth2/default/v1';
    public const AUTH_ENDPOINT                  = '/authorize';
    public const TOKEN_ENDPOINT                 = '/token';
    public const USER_INFO_ENDPOINT             = '/userinfo';
    public const TOKEN_TYPE                     = 'access_token';

    /**
     * Request parameters keys
     */
    public const REQUEST_CLIENT_ID_PARAM        = 'client_id';
    public const REQUEST_NONCE_PARAM            = 'nonce';
    public const REQUEST_SCOPE_PARAM            = 'scope';
    public const REQUEST_STATE_PARAM            = 'state';
    public const REQUEST_REDIRECT_ID_PARAM      = 'redirect_uri';
    public const REQUEST_RESPONSE_MODE_PARAM    = 'response_mode';
    public const REQUEST_RESPONSE_TYPE_PARAM    = 'response_type';
    public const REQUEST_CODE_CHALLENGE_METHOD_PARAM    = 'code_challenge_method';
    public const REQUEST_CODE_CHALLENGE_PARAM    = 'code_challenge';

    /**
     * Default request parameters values
     */
    public const REQUEST_SCOPE_VALUE            = 'openid profile email groups';
    public const REQUEST_STATE_VALUE            = 'okta_sso';
    public const REQUEST_RESPONSE_MODE_VALUE    = 'form_post';
    public const REQUEST_RESPONSE_TYPE_VALUE    = 'code'; //id_token

    /**
     * @return string
     */
    public function build(): string;

    /**
     * @param string $clientId
     * @return $this
     */
    public function setClientId(string $clientId);

    /**
     * @param string $domain
     * @return $this
     */
    public function setDomain(string $domain);

    /**
     * @param string $nonce
     * @return $this
     */
    public function setNonce(string $nonce);

    /**
     * @param string $redirectUrl
     * @return $this
     */
    public function setRedirectUrl(string $redirectUrl);

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope);

    /**
     * @param string $state
     * @return $this
     */
    public function setState(string $state);

    /**
     * @param string $responseType
     * @return $this
     */
    public function setResponseType(string $responseType);

    /**
     * @param string $responseMode
     * @return $this
     */
    public function setResponseMode(string $responseMode);
}

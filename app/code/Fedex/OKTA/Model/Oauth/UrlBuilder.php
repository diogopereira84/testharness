<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilder\ChallengeResolver;

class UrlBuilder implements UrlBuilderInterface
{
    /**
     * Internal / required properties
     */
    private ?string $clientId = NULL;
    private ?string $domain = NULL;
    private ?string $nonce = NULL;
    private ?string $redirectUrl = NULL;
    private string $responseMode;
    private string $responseType;
    private string $state;
    private string $scope;


    /**
     * UrlBuilder constructor.
     * @param UrlHelper $urlHelper
     * @param LoggerInterface $logger
     * @param ChallengeResolver $challengeResolver
     */
    public function __construct(
        private UrlHelper $urlHelper,
        protected LoggerInterface $logger,
        private ChallengeResolver $challengeResolver
    )
    {
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function build(): string
    {
        $this->validateRequiredData();
        return $this->urlHelper->addRequestParam($this->getAuthRequestUrl(), $this->getRequestParams());
    }

    /**
     * @param string $clientId
     * @return $this
     */
    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param string $domain
     * @return $this
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param string $nonce
     * @return $this
     */
    public function setNonce(string $nonce)
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * @param string $redirectUrl
     * @return $this
     */
    public function setRedirectUrl(string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState(string $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param string $responseType
     * @return $this
     */
    public function setResponseType(string $responseType)
    {
        $this->responseType = $responseType;
        return $this;
    }

    /**
     * @param string $responseMode
     * @return $this
     */
    public function setResponseMode(string $responseMode)
    {
        $this->responseMode = $responseMode;
        return $this;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function validateRequiredData(): bool
    {
        if ($this->clientId === null) {
            $this->logger->info(__METHOD__.':'.__LINE__.' User has not defined an OKTA Client Id');
            throw new LocalizedException(__('You must define an OKTA Client Id.'));
        }

        if ($this->domain === null) {
            $this->logger->info(__METHOD__.':'.__LINE__.' User has not defined an OKTA Domain.');
            throw new LocalizedException(__('You must define an OKTA Domain.'));
        }

        if ($this->redirectUrl === null) {
            $this->logger->info(__METHOD__.':'.__LINE__.' User has not defined an OKTA Redirect URL.');
            throw new LocalizedException(__('You must define an OKTA Redirect URL'));
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAuthRequestUrl(): string
    {
        return rtrim($this->domain, "/") . static::AUTH_ENDPOINT;
    }

    /**
     * @return array
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function getRequestParams(): array
    {
        $params = [
            static::REQUEST_CLIENT_ID_PARAM       => $this->clientId,
            static::REQUEST_REDIRECT_ID_PARAM     => $this->redirectUrl,
            static::REQUEST_SCOPE_PARAM           => static::REQUEST_SCOPE_VALUE,
            static::REQUEST_STATE_PARAM           => static::REQUEST_STATE_VALUE,
            static::REQUEST_RESPONSE_TYPE_PARAM   => static::REQUEST_RESPONSE_TYPE_VALUE,
            static::REQUEST_RESPONSE_MODE_PARAM   => static::REQUEST_RESPONSE_MODE_VALUE
        ];

            $code = $this->challengeResolver->resolve();
            $params[static::REQUEST_CODE_CHALLENGE_METHOD_PARAM] = $code->getChallengeMethod();
            $params[static::REQUEST_CODE_CHALLENGE_PARAM] = $code->getChallenge();


        if ($this->nonce) {
            $params[static::REQUEST_NONCE_PARAM] = $this->nonce;
        }

        return $params;
    }
}

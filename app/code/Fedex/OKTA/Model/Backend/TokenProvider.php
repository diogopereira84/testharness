<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Backend;

use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Oauth\UrlBuilder\CodeStorage;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

class TokenProvider
{
    private const REQUEST_SCOPE_VALUE  = 'profile';
    private const REQUEST_GRANT_TYPE  = 'authorization_code';
    /**
     * @var $code
     */
    private $code;

    /**
     * @param OktaHelper $oktaHelper
     * @param AbstractApiClient $apiClient
     * @param EncryptorInterface $encryptor
     * @param CodeStorage $codeStorage
     */
    public function __construct(
        private OktaHelper $oktaHelper,
        private AbstractApiClient $apiClient,
        private EncryptorInterface $encryptor,
        private CodeStorage $codeStorage
    )
    {
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getOktaToken(string $code)
    {
        $this->setHeaders();
        $this->setDomian();
        $this->code = $code;
        return $this->apiClient->execute(
            UrlBuilderInterface::TOKEN_ENDPOINT,
            RestRequest::METHOD_POST,
            null,
            $this->getRequestBody()
        );
    }

    private function setDomian()
    {
        $this->apiClient->domain = $this->oktaHelper->getDomain();
    }

    private function setHeaders()
    {
        $this->apiClient->headers['Accept'] = 'application/json';
        $this->apiClient->headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    /**
     * @return array
     */
    private function getRequestBody()
    {
        $body = [
            'client_id' => $this->oktaHelper->getClientId(),
            'grant_type' => self::REQUEST_GRANT_TYPE,
            'scope' => self::REQUEST_SCOPE_VALUE,
            'redirect_uri' => $this->oktaHelper->getRedirectUrl(),
            'code' => $this->code,
        ];

         $body['code_verifier'] = $this->codeStorage->retrieve();

        return $body;
    }
}

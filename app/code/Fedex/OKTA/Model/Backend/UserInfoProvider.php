<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Backend;

use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

class UserInfoProvider
{

    /**
     * @var AbstractApiClientInterface
     */
    private $apiClient;
    /**
     * @var $accessToken
     */
    private $accessToken;

    /**
     * UserInfoProvider constructor.
     * @param OktaHelper $oktaHelper
     * @param AbstractApiClient $apiClient
     */
    public function __construct(
        private OktaHelper $oktaHelper,
        AbstractApiClient $apiClient
    ) {
        $this->apiClient = $apiClient;
    }

    /**
     * @param string $accessToken
     * @return mixed
     */
    public function getUserInfo(string $accessToken)
    {
        $this->accessToken = $accessToken;
        $this->setHeaders();
        $this->setDomian();
        return $this->apiClient->execute(
            UrlBuilderInterface::USER_INFO_ENDPOINT,
            RestRequest::METHOD_POST,
            null,
            []
        );
    }

    private function setDomian()
    {
        $this->apiClient->domain = $this->oktaHelper->getDomain();
    }

    private function setHeaders()
    {
        $this->apiClient->headers['Accept'] = 'application/json';
        $this->apiClient->headers['Content-Type'] = 'application/json';
        $this->apiClient->headers['Authorization'] = "Bearer {$this->accessToken}";
    }
}

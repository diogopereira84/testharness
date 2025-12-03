<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Exception;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ProductInfo
{
    /**
     * @param MarketplaceConfig $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param Authorization $Authorization
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private MarketplaceConfig $config,
        private Curl $curl,
        private LoggerInterface $logger,
        private Authorization $authorization,
        private CheckoutSession $checkoutSession
    ) {
    }

    /**
     * @param string $brokerConfigId
     * @param string $productSku
     * @return string|null
     * @throws Exception
     */
    public function execute(string $brokerConfigId, string $productSku): ?string
    {
        $retries = 3;
        $token = $this->getAuthorizationToken($productSku);

        do {
            try {
                $headers = [
                    "Content-Type: application/json", "Accept-Language: json",
                    "Authorization: Bearer ".$token
                ];
                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_ENCODING => ''
                    ]
                );

                if ($this->config->isEnableShopsConnection()) {
                    $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
                    //avoid call the api if the API url to get expiration is not configured on the seller
                    if (empty($shopCustomAttributes['get-info-url'])) {
                        return '1';
                    }
                    $this->curl->get(
                        $shopCustomAttributes['get-info-url'] . "?BrokerConfigID=$brokerConfigId"
                    );
                } else {
                    $this->curl->get(
                        $this->config->getNavitorProductInfoUrl() . "?BrokerConfigID=$brokerConfigId"
                    );
                }

                $this->logger->info("SELLER INFO: Curl BrokerConfigId " . $brokerConfigId);
                $this->logger->info("SELLER INFO: Curl status " . $this->curl->getStatus());

                if ($this->curl->getStatus() == 401) {
                    $token = $this->setNewTokenSession($productSku);
                } else if ($this->curl->getStatus() == 200) {
                    return $this->curl->getBody();
                } else {
                    return null;
                }
            } catch (Exception $e) {
                $this->logger->info("SELLER INFO: Exception on get product info: " . $e->getMessage());
            }
            $retries--;
        } while ($retries != 0);

        return null;
    }

    /**
     * @param $productSku
     * @return string|null
     * @throws Exception
     */
    private function getAuthorizationToken($productSku = null): ?string
    {
        $token = $this->checkoutSession->getMarketplaceAuthToken();
        if (!$token) {
            $token = $this->setNewTokenSession($productSku);
        }
        return $token;
    }

    /**
     * @param $productSku
     * @return string|null
     */
    private function setNewTokenSession($productSku = null): ?string
    {
        try {
            $token = $this->authorization->execute($productSku);
            $this->checkoutSession->setMarketplaceAuthToken($token);
            return $token;
        } catch (Exception $e) {
            $this->logger->info("SELLER INFO: Error on generate authentication token: " . $e->getMessage());
        }
        return null;
    }
}

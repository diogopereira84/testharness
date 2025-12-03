<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\Builder;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Marketplace
{
    /**
     * @param MarketplaceConfig $config
     * @param Curl $curl
     * @param Builder $xmlBuilder
     * @param Redirect $redirect
     * @param LoggerInterface $logger
     */
    public function __construct(
        private MarketplaceConfig $config,
        private Curl $curl,
        private Builder $xmlBuilder,
        private Redirect $redirect,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Punch-out Request
     *
     * @param $productSku
     * @param bool $redirect
     * @param mixed|null $productConfigData
     * @return mixed|ResultRedirect
     * @throws Exception
     * @throws NoSuchEntityException
     */
    public function punchout($productSku = null, bool $redirect = true, mixed $productConfigData = null): array|ResultRedirect
    {
        $xml = $this->xmlBuilder->build($productSku, $productConfigData);

        if ($this->config->isEnableShopsConnection()) {
            $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
            $this->curl->addHeader('Content-type', 'application/xml');

            if (isset($shopCustomAttributes['seller-headers'])) {
                $sellerHeader = $shopCustomAttributes['seller-headers'];
                if (!is_object($sellerHeader)) {
                    $sellerHeader = json_decode($sellerHeader);
                }
                foreach ($sellerHeader as $key => $value) {
                    $this->curl->addHeader($key, $value);
                }
            }
            $this->curl->post(
                $shopCustomAttributes['pdp-punchout-url'],
                $xml->asXML()
            );
        } else {
            $this->curl->post(
                $this->config->getNavitorUrl(),
                $xml->asXML()
            );
        }

        $this->logger->info("SELLER INFO: Curl status " . $xml->asXML());
        $this->logger->info("SELLER INFO: Curl status " . $this->curl->getStatus());
        if ($this->curl->getStatus() != 200 &&
            $this->curl->getStatus() != 100) {
            throw new Exception($this->curl->getBody());
        }
        $xml = simplexml_load_string($this->curl->getBody());

        if ($xml->Response->Status['code'] != 200) {
            throw new Exception($this->curl->getBody());
        }
        $url = (string)$xml->Response->PunchOutSetupResponse->StartPage->URL;
        $this->logger->info("SELLER INFO: Url config " . $url);

        if (!$redirect) {
            $authCode = (string) $xml->Response->PunchOutSetupResponse->StartPage->AUTH_CODE ?? '';
            return [
              'url' => $url,
              'auth_code' => $authCode
            ];
        }

        return $this->redirect->redirect(true, $url);
    }
}

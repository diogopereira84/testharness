<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\Builder;
use Magento\Framework\HTTP\Client\CurlFactory;
use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Authorization
{
    /**
     * @param MarketplaceConfig $config
     * @param CurlFactory $curlFactory
     * @param Builder $xmlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private MarketplaceConfig $config,
        private CurlFactory $curlFactory,
        private Builder $xmlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Authorization Request
     *
     * @param string $productSku
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function execute(string $productSku): ?string
    {
        $xml = $this->xmlBuilder->build($productSku);
        $curl = $this->curlFactory->create();
        $authUrl = $this->config->getNavitorAuthUrl();

        if ($this->config->isEnableShopsConnection()) {
            $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
            if (isset($shopCustomAttributes['authorization-url'])) {
                $authUrl = $shopCustomAttributes['authorization-url'];
            }
        }

        $curl->post(
            $authUrl,
            $xml->asXML()
        );

        $this->logger->info("SELLER AUTHORIZATION INFO: Curl data " . $xml->asXML());
        $this->logger->info("SELLER AUTHORIZATION INFO: Curl status " . $curl->getStatus());

        if ($curl->getStatus() != 200) {
            throw new Exception($curl->getBody());
        }

        $xml = simplexml_load_string($curl->getBody());
        if ($xml->Response && $xml->Response->AuthResponse && $xml->Response->AuthResponse->Credential) {
            $token = (string)$xml->Response->AuthResponse->Credential->SharedSecret ?? null;
        }

        return $token ?? null;
    }
}

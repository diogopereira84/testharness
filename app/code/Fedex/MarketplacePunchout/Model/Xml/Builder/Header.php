<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml\Builder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;

class Header
{
    /**
     * Shared token punchout integration type.
     */
    private const SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE = 'shared-secret-token';

    /**
     * Seller punchout identity.
     */
    private const SELLER_PUNCHOUT_IDENTITY = 'punchout-identity';

    /**
     * @param ElementFactory $xmlFactory
     * @param Marketplace $config
     */
    public function __construct(
        private ElementFactory $xmlFactory,
        private Marketplace $config
    ) {
    }

    /**
     * Build the request xml edit header
     *
     * @param $productSku
     * @param mixed|null $productConfigData
     * @return Element
     * @throws NoSuchEntityException
     */
    public function build($productSku = null, mixed $productConfigData = null): Element
    {
        $header = $this->xmlFactory->create(
            ['data' => '<Header></Header>']
        );

        if ($this->config->isEnableShopsConnection() && $productSku !== null) {
            $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
            if ($shopCustomAttributes['integration-type'] == self::SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE) {
                $sender = $header->addChild('Sender');
                $senderCredential = $sender->addChild('Credential');
                $senderCredential->addChild('Identity', $shopCustomAttributes[self::SELLER_PUNCHOUT_IDENTITY]);
                $senderCredential->addChild('SharedSecret', $shopCustomAttributes[self::SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE]);
                return $header;
            }
            //if toggle is enable and the integration type is not shared secret
            $resellerId          = $shopCustomAttributes['reseller-id'];
            $defaultCustomerAcct = $shopCustomAttributes['default-customer-acct'];
            $senderIdentity      = $shopCustomAttributes['sender-identity'];
            $navinkSecretKey     = $shopCustomAttributes['navink-secret-key'];
        }

        $resellerId          = $resellerId ?? $this->config->getFromId();
        $defaultCustomerAcct = $defaultCustomerAcct ?? $this->config->getToId();
        $senderIdentity      = $senderIdentity ?? $this->config->getSenderIdentity();
        $navinkSecretKey     = $navinkSecretKey ?? $this->config->getSenderSharedSecret();

        $from = $header->addChild('From');
        $to = $header->addChild('To');
        $sender = $header->addChild('Sender');

        $fromCredential = $from->addChild('Credential');
        $fromCredential->addAttribute('domain', 'DUNS');
        $fromCredential->addChild('Identity', $resellerId);

        $toCredential = $to->addChild('Credential');
        $toCredential->addAttribute('domain', 'DUNS');
        $toCredential->addChild('Identity', $defaultCustomerAcct);

        $senderCredential = $sender->addChild('Credential');
        $senderCredential->addChild('Identity', $senderIdentity);
        $senderCredential->addChild('SharedSecret', $navinkSecretKey);
        return $header;
    }
}

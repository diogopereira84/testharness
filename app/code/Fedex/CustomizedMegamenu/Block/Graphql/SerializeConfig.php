<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomizedMegamenu\Block\Graphql;

use Magento\Framework\Serialize\Serializer\Json;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Helper\Image;
use Magento\Customer\CustomerData\JsLayoutDataProviderPoolInterface;

class SerializeConfig extends \Magento\Checkout\Block\Cart\Sidebar
{
    /**
     * Initializing Constructor
     *
     * @param Context $context
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Image $imageHelper
     * @param JsLayoutDataProviderPoolInterface $jsLayoutDataProvider
     * @param Json|null $serializer
     * @param ToggleConfig $toggleConfig
     * @throws \RuntimeException
     */
    public function __construct(
        protected Context $context,
        protected Session $customerSession,
        protected CheckoutSession $checkoutSession,
        Image $imageHelper,
        protected JsLayoutDataProviderPoolInterface $jsLayoutDataProvider,
        private Json $jsonSerializer,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $imageHelper, $jsLayoutDataProvider);
    }

    /**
     * Get serialized config
     *
     * @return object
     */
    public function getSerializedConfig()
    {
        if (!$this->toggleConfig->getToggleConfigValue('xmen_remove_adobe_commerce_override')) {
            return $this->jsonSerializer->serialize(parent::getConfig());
        }

        return parent::getSerializedConfig();
    }
}

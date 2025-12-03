<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\DataObject\Factory;
use Magento\Quote\Model\Quote\Item\CartItemProcessorInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Bundle\Api\Data\BundleOptionInterfaceFactory;
use Magento\Quote\Api\Data as QuoteApi;

class CartItemProcessor extends \Magento\Bundle\Model\CartItemProcessor implements CartItemProcessorInterface
{

    /**
     * @param Factory $objectFactory
     * @param QuoteApi\ProductOptionExtensionFactory $productOptionExtensionFactory
     * @param BundleOptionInterfaceFactory $bundleOptionFactory
     * @param QuoteApi\ProductOptionInterfaceFactory $productOptionFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        Factory $objectFactory,
        QuoteApi\ProductOptionExtensionFactory $productOptionExtensionFactory,
        BundleOptionInterfaceFactory $bundleOptionFactory,
        QuoteApi\ProductOptionInterfaceFactory $productOptionFactory,
        protected ConfigInterface $config
    ) {
        parent::__construct(
            $objectFactory,
            $productOptionExtensionFactory,
            $bundleOptionFactory,
            $productOptionFactory
        );
    }

    /**
     * @inheritDoc
     */
    public function convertToBuyRequest(CartItemInterface $cartItem)
    {
        if(!$this->config->isTigerE468338ToggleEnabled()) {
            return parent::convertToBuyRequest($cartItem);
        }

        if($this->cartItemHasBuyRequest($cartItem)) {
            return null;
        }

        if ($cartItem->getProductOption() && $cartItem->getProductOption()->getExtensionAttributes()) {
            $options = $cartItem->getProductOption()->getExtensionAttributes()->getBundleOptions();
            if (is_array($options)) {
                $requestData = [];
                foreach ($options as $option) {
                    /** @var \Magento\Bundle\Api\Data\BundleOptionInterface $option */
                    foreach ($option->getOptionSelections() as $selection) {
                        $requestData['bundle_option'][$option->getOptionId()][] = $selection;
                        $requestData['bundle_option_qty'][$option->getOptionId()] = $option->getOptionQty();
                    }
                }
                return $this->objectFactory->create($requestData);
            }
        }
        return null;
    }

    /**
     * Check if the cart item has a buy request
     *
     * @param CartItemInterface $cartItem
     * @return bool
     */
    private function cartItemHasBuyRequest(CartItemInterface $cartItem): bool
    {
        $infoBuyRequest = $cartItem->getOptionByCode('info_buyRequest');
        return $infoBuyRequest && !is_null($infoBuyRequest->getValue());
    }
}

<?php

namespace Fedex\Orderhistory\Model\CustomOptions;

use Magento\Catalog\Model\CustomOptions\CustomOptionProcessor as BaseCustomOptionProcessor;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemProcessorInterface;
use Magento\Quote\Api\Data\ProductOptionExtensionFactory;
use Magento\Quote\Model\Quote\ProductOptionFactory;

class CustomOptionProcessor extends BaseCustomOptionProcessor
{
    /**
     * Serializer interface instance.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @param DataObject\Factory $objectFactory
     * @param ProductOptionFactory $productOptionFactory
     * @param ProductOptionExtensionFactory $extensionFactory
     * @param CustomOptionFactory $customOptionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Quote\Model\Quote\ProductOptionFactory $productOptionFactory,
        \Magento\Quote\Api\Data\ProductOptionExtensionFactory $extensionFactory,
        \Magento\Catalog\Model\CustomOptions\CustomOptionFactory $customOptionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->customOptionFactory = $customOptionFactory;
        $this->extensionFactory = $extensionFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->objectFactory = $objectFactory;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * Receive custom option from buy request
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @codeCoverageIgnore
     */
    protected function getOptions(CartItemInterface $cartItem)
    {
        if (!empty($cartItem->getOptionByCode('info_buyRequest'))) {
            $opionValue = $cartItem->getOptionByCode('info_buyRequest')->getValue();
            if (!$opionValue || $opionValue == '' || !$this->isJson($opionValue)) {
                return [];
            }
        }

        $buyRequest = !empty($cartItem->getOptionByCode('info_buyRequest'))
            ? $this->serializer->unserialize($cartItem->getOptionByCode('info_buyRequest')->getValue())
            : null;
        return is_array($buyRequest) && isset($buyRequest['options'])
            ? $buyRequest['options']
            : [];
    }

    /**
     * Check valid json
     * @param string $string
     * @codeCoverageIgnore
     */
    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

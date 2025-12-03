<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\LiveSearch\Observer;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Eav\Setup\EavSetup;
use Psr\Log\LoggerInterface;

class EnforceLiveSearchAttributes implements ObserverInterface
{
    public const UPLOAD_FILE_ATTRIBUTE_SET_NAME = 'FXOPrintProducts';
    public const CUSTOMIZE_ATTRIBUTE_SET_NAME = 'PrintOnDemand';
    public const UPLOAD_FILE_ATTRIBUTE_NAME = 'upload_file_search_action';
    public const CUSTOMIZE_ATTRIBUTE_NAME = 'customize_search_action';
    public const XML_PATH_TOGGLE = 'tiger_d203672';

    /**
     * @param ToggleConfig $toggleConfig
     * @param EavSetup $eavSetup
     * @param LoggerInterface $logger
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        protected ToggleConfig                    $toggleConfig,
        protected EavSetup                        $eavSetup,
        protected LoggerInterface                 $logger,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        protected AttributeRepositoryInterface    $attributeRepository,
    ) {}

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute (\Magento\Framework\Event\Observer $observer) {
        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE)) {

            try {
                /** @var Product $product */
                $product = $observer->getProduct();
                $productAttrSetId = $product?->getAttributeSetId();

                $fxoPrintProductsAttrSetId = $this->eavSetup->getAttributeSetId(
                    Product::ENTITY, self::UPLOAD_FILE_ATTRIBUTE_SET_NAME
                );
                $printOnDemandAttrSetId = $this->eavSetup->getAttributeSetId(
                    Product::ENTITY, self::CUSTOMIZE_ATTRIBUTE_SET_NAME
                );

                switch ($productAttrSetId) {
                    case $fxoPrintProductsAttrSetId:
                        $product->setData(self::UPLOAD_FILE_ATTRIBUTE_NAME, 1);
                        break;
                    case $printOnDemandAttrSetId:
                        $product->setData(self::CUSTOMIZE_ATTRIBUTE_NAME, 1);
                        break;
                    default:
                        break;
                }
            } catch (\Exception $e) {
                $this->logger->alert(__METHOD__ . ':' . __LINE__ . ': '.$e->getMessage());
            }
        }

        return $this;
    }
}

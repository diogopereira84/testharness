<?php
/**
 * @category     Fedex
 * @package      Fedex_SharedCatalogCustomization
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Copier;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;

/**
 * Assign products to Shared Catalog on product duplicate action.
 */
class AssignSharedCatalogOnDuplicateProductPlugin
{

    /**
     * @var ProductItemManagementInterface
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @param ProductSharedCatalogsLoader $productSharedCatalogsLoader
     * @param ProductItemManagementInterface $productItemManagement
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private ProductSharedCatalogsLoader $productSharedCatalogsLoader,
        ProductItemManagementInterface $productItemManagement,
        private \Psr\Log\LoggerInterface $logger
    ) {
        $this->sharedCatalogProductItemManagement = $productItemManagement;
    }

    /**
     * Product after copy plugin.
     *
     * @param Copier $subject
     * @param Product $result
     * @param Product $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCopy(
        Copier $subject,
        Product $result,
        Product $product
    ) {
        $origProductSharedCatalogs = $this->productSharedCatalogsLoader->getAssignedSharedCatalogs($product->getSku());
        if (count($origProductSharedCatalogs)) {
            foreach ($origProductSharedCatalogs as $origProductSharedCatalog) {
                try {
                    /**
                     * Core bug fix to add row for guest customer group if shared catalog is public
                     */
                    if ($origProductSharedCatalog->getType() == SharedCatalogInterface::TYPE_PUBLIC) {
                        $this->sharedCatalogProductItemManagement->addItems(
                            GroupInterface::NOT_LOGGED_IN_ID,
                            [$result->getSku()]
                        );
                    }
                    /**
                     * Core bug fix:end
                     */
                    $this->sharedCatalogProductItemManagement->addItems(
                        $origProductSharedCatalog->getCustomerGroupId(),
                        [$result->getSku()]
                    );
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->logger->critical(__METHOD__.':'.__LINE__.':'.$e->getMessage());
                }
            }
        }

        return $result;
    }
}

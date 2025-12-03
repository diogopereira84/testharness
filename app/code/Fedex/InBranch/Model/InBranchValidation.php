<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Model;

use Fedex\InBranch\Model\InBranchValidationInterface;
use Fedex\InBranch\Helper\Data;
use Fedex\Delivery\Helper\Data as deliveryHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\FXOCMConfigurator\Helper\Data as fxocmonfiguratorData;
use Fedex\SelfReg\Helper\SelfReg;

class InBranchValidation implements InBranchValidationInterface
{
    /**
     * Add constructor.
     * @param Data $data
     * @param deliveryHelper $deliveryHelper
     * @param Session $quote
     * @param ProductRepository $productRepository
     * @param fxocmonfiguratorData $fxocmhelper
     * @param SelfReg $selfregHelper
     */
    public function __construct(
        private Data $data,
        private deliveryHelper $deliveryHelper,
        private Session $quote,
        private ProductRepository $productRepository,
        private fxocmonfiguratorData $fxocmhelper,
        private SelfReg $selfregHelper
    )
    {
    }

    /**
     * Check Isbranch product is already exist
     *
     * @param object $productInfo
     * @param bool $jsonObject
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isInBranchValid($productInfo, $jsonObject = false): bool
    {
        $quote = $this->quote->getQuote();
        $isEproStore = $this->isInBranchUser();
        if ($isEproStore) {
            if ($jsonObject):
                $requestDataExtracted = json_decode((string)$productInfo, true);
                if ($this->fxocmhelper->getFxoCMToggle() && isset($requestDataExtracted['integratorProductReference'])) {
                    $sku = $requestDataExtracted['integratorProductReference'] ?? '';
                } else {
                    $sku = $requestDataExtracted['fxoMenuId'] ?? '';
                }
                $product = $this->productRepository->get($sku);
            endif;
            if (!$jsonObject):
                $product = $productInfo;
            endif;
            $items = $quote->getAllVisibleItems();
            $newProductLocation = $product->getData('product_location_branch_number');
            foreach ($items as $item) {
                $cartProduct = $item->getProduct();
                $cartProductResource = $cartProduct->getResource();
                $cartProductLocation = $cartProductResource->getAttributeRawValue(
                    $cartProduct->getId(),
                    'product_location_branch_number',
                    $cartProduct->getStoreId());

                if (!empty($newProductLocation) &&
                    !empty($cartProductLocation) &&
                    $cartProductLocation != $newProductLocation):
                    return true;
                endif;
            }
        }
        return false;
    }

    /**
     * Check Isbranch product is already exist for reorder
     *
     * @param array $reorderDatas
     * @return mixed|void
     * @throws NoSuchEntityException
     */
    public function isInBranchValidReorder($reorderDatas)
    {
        $quote = $this->quote->getQuote();
        $isEproStore = $this->isInBranchUser();
        $branchIds = [];
        if ($isEproStore) {
            $items = $quote->getAllVisibleItems();
            foreach ($reorderDatas as $key => $value) {
                $product = $this->productRepository->getById(trim($value['product_id']));
                $result = $product->getProductLocationBranchNumber();
                if ($result!='') {
                    $branchIds[]= $result;
                }
            }
            foreach ($items as $item) {
                $productBranchLocationNumaber = $item->getProduct()->getProductLocationBranchNumber();
                if (!empty($branchIds) && $productBranchLocationNumaber != '' &&
                    !in_array($productBranchLocationNumaber, $branchIds)) {
                    return true;
                }
            }
        }
    }

    /**
     * Get Allowed inbranch location
     *
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAllowedInBranchLocation()
    {
        $locationNumber = '';
        $quote = $this->quote->getQuote();
        $isEproStore = $this->isInBranchUser();
        if ($isEproStore) {
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                $locationNumber = $item->getProduct()->getProductLocationBranchNumber();
                if ($locationNumber != '' && $locationNumber != null) {
                    return  $locationNumber;
                }
            }
        }
        return $locationNumber;
    }


     /**
      * Check Allowed isbranch user loggedin
      *
      * @return bool
      */
    public function isInBranchUser(): bool
    {
        return (bool)($this->deliveryHelper->isEproCustomer() || $this->selfregHelper->isSelfRegCustomer());
    }

    /**
      * Check in-branch location exist and contentAssociation is empty
      *
      * @return bool
      */
    public function isInBranchProductWithContentAssociationsEmpty($items): bool
    {
        if (!$this->isInBranchUser()) {
            return false;
        }

        foreach ($items as $item) {
            $product = $item->getProduct();
            if (!$product) {
                continue;
            }

            $locationNumber = $product->getProductLocationBranchNumber();
            
            if (!empty($locationNumber) && $this->isProductContentAssociationsEmpty($item)) {
                return true;
            }
        }

        return false;
    }

    /**
      * Check in-branch contentAssociations if empty
      *
      * @return bool
      */
    public function isProductContentAssociationsEmpty($item): bool
    {
        $itemOption = $item->getOptionByCode('info_buyRequest');

        if (!$itemOption) {
            return false;
        }

        $itemOptionsData = json_decode($itemOption->getValue(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($itemOptionsData['external_prod'])) {
            return false;
        }
            
        return !empty(array_filter($itemOptionsData['external_prod'], fn($itemOptionData) => 
            empty($itemOptionData['contentAssociations'])
        ));
    }
}

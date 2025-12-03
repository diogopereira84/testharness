<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\ProductUnavailabilityMessage\ViewModel;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\MarketplaceProduct\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CheckProductAvailability implements ArgumentInterface
{
    public const TIGER_D_232503 = 'tiger_d232503';

    /**
     * @param CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
     * @param MarketplaceConfig $config
     */
    public function __construct(
        private readonly CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel,
        private readonly MarketplaceConfig $config,
        private readonly Data $helper,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Check if E-441563 toggle is enabled
     *
     * @return bool
     */
    public function isE441563ToggleEnabled()
    {
        return $this->checkProductAvailabilityDataModel->isE441563ToggleEnabled();
    }

    /**
     * Check if D-228743 toggle is enabled
     *
     * @return bool
     */
    public function isTigerTeamD228743ToggleEnabled()
    {
        return (bool) $this->checkProductAvailabilityDataModel->isTigerTeamD228743ToggleEnabled();
    }

    /**
     * @param $product
     * @return bool
     * @throws NoSuchEntityException
     */
    public function checkProductAvailable($product): bool
    {
        if (!$this->isE441563ToggleEnabled()) {
            return true;
        }

        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_232503)) {
            return $product->getData('is_unavailable') ? false : true;
        } else {
            if (!$product->getMiraklMcmProductId()) {
                return !$product->getData('is_unavailable');
            }
            if ($product->getData('is_unavailable')) {
                return false;
            }
            foreach ($this->helper->getAllOffers($product) as $offer) {
                if ($offer->getData('quantity') > 0) {
                    return true;
                }
            }
            return false;
        }
    }
    /**
     * @return mixed|string
     */
    public function getProductPDPErrorMessage()
    {
        return $this->checkProductAvailabilityDataModel->getProductPDPErrorMessage();
    }

    /**
     * @return mixed|string
     */
    public function getProductCartlineErrorMessage()
    {
        return $this->checkProductAvailabilityDataModel->getProductCartlineErrorMessage();
    }
    /**
     * @return mixed|string
     */
    public function getProductPDPErrorMessageTitle()
    {
        return $this->checkProductAvailabilityDataModel->getProductPDPErrorMessageTitle();
    }

    /**
     * @return mixed|string
     */
    public function getProductCartlineErrorMessageTitle()
    {
        return $this->checkProductAvailabilityDataModel->getProductCartlineErrorMessageTitle();
    }
    /**
     * @return mixed|string
     */
    public function checkCartHaveUnavailbleProduct(){
        return $this->checkProductAvailabilityDataModel->checkCartHaveUnavailbleProduct();
   }
    /**
     * @return mixed|string
     */
    public function getUnavailableButtonCssClass(){
        return 'disabled-button';
    }
    /**
     * @return mixed|string
     */
    public function getUnavailableQtyCssClass(){
        return 'disabled-qty';
    }
    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getStockStatus($product){
        return $this->checkProductAvailabilityDataModel->getStockStatus($product);
    }

}

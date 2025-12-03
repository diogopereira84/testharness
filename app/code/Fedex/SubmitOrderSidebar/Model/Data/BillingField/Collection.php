<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\Data\BillingField;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldCollectionInterface;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends DataCollection implements BillingFieldCollectionInterface
{
    const D195387_TOGGLE = 'tiger_d195387';

    public function __construct(
        EntityFactoryInterface $entityFactory,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($entityFactory);
    }

    /**
     * Returns PO number
     *
     * @return string
     */
    public function getPoNumer(): string
    {
        $poNumber = $this->getFirstItem();
        return (string)$poNumber->getValue();
    }

    /**
     * Checks if collection has Po Number
     *
     * @return bool
     */
    public function hasPoNumber(): bool
    {
        return $this->getSize() > 0;
    }

    /**
     * @return void
     */
    public function removePoReferenceId()
    {
        $this->removeItemByKey(0);
    }

    /**
     * @return mixed|false
     */
    public function toArrayApi()
    {
        $billingFields = (parent::toArray()['items'])? parent::toArray()['items'] : false;
        if($this->toggleConfig->getToggleConfigValue(self::D195387_TOGGLE) && $billingFields){
            foreach ($billingFields as $key => $billingField){
                if(array_key_exists('first_field', $billingField)) {
                    unset($billingField['first_field']);
                    $billingFields[$key] = $billingField;
                }
            }
        }
        return $billingFields;
    }

}

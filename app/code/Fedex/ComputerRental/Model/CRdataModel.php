<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ComputerRental\Model;

use Fedex\ComputerRental\Api\CRDataInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Session\SessionManagerInterface;
use Fedex\Delivery\Helper\Data as deliveryHelper;

class CRdataModel implements CRDataInterface
{

    /**
     * CRdataModel constructor.
     *
     * @param SessionManagerInterface $coreSession
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected  SessionManagerInterface $coreSession,
        protected  ToggleConfig $toggleConfig,
        private readonly deliveryHelper $deliveryHelper
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function saveStoreCodeInSession($storeCode)
    {
        $this->coreSession->setData('storeCode', $storeCode);
    }
    /**
     * {@inheritdoc}
     */
    public function getStoreCodeFromSession()
    {
       return  $this->coreSession->getData('storeCode');
    }
    /**
     * {@inheritdoc}
     */
    public function getLocationCode()
    {
        return $this->coreSession->getData('locationCode');
    }
    /**
     * {@inheritdoc}
     */
    public function saveLocationCode($locationCode)
    {
        $this->coreSession->setData('locationCode', $locationCode);
    }

    /**
     * @return bool
     */
    public function isRetailCustomer(){
        return (!$this->deliveryHelper->isCommercialCustomer());
    }
}

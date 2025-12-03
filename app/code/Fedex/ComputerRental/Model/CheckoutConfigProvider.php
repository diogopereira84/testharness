<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ComputerRental\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Fedex\ComputerRental\Api\CRDataInterface;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * CheckoutConfigProvider constructor.
     *
     * @param CRDataInterface $crData
     */
    public function __construct(
        protected readonly CRDataInterface $crData
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'CRStoreCode' => $this->crData->getStoreCodeFromSession(),
            'CRLocationCode' => $this->crData->getLocationCode(),
            'isRetailCustomer' => (bool) $this->crData->isRetailCustomer(),
        ];
    }
}

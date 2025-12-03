<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\B2b\Model\Quote\Address;
use Fedex\CartGraphQl\Model\Address\CollectRates as GraphQlAddressBuilder;

class AddressPlugin
{
    /**
     * @param GraphQlAddressBuilder $graphQlAddressBuilder
     */
    public function __construct(
        private GraphQlAddressBuilder $graphQlAddressBuilder
    ) {
    }

    /**
     * @param Address $subject
     * @return array
     */
    public function beforeCollectShippingRates(Address $subject): array
    {
        if ($subject->getCountryId()) {
            $this->graphQlAddressBuilder->execute($subject);
        }

        $subject->setCollectShippingRates(false);

        return [];
    }
}

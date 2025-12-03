<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\CustomerData;

use Magento\Customer\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;

class MarketplaceSection implements SectionSourceInterface
{
    /**
     * @param Session $customerSession
     */
    public function __construct(
        private Session $customerSession
    ) {
    }

    /**
     * @return array
     */
    public function getSectionData(): array
    {
        return [
            'has_error' => $this->customerSession->getMarketplaceError()
        ];
    }
}

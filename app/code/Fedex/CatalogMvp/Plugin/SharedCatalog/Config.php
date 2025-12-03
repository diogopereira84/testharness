<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\SharedCatalog;

use Magento\SharedCatalog\Model\Config as ParentConfig;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Customer\Model\Session;

class Config
{
    /**
     * @param CatalogMvp $helper
     * @param Session $customerSession
     */
    public function __construct(
        protected CatalogMvp $helper,
        protected Session $customerSession
    )
    {
    }

    /**
     * Disable Shared Catalog when request come from Catalog MVP product creation
     * @param ParentConfig $subject
     * @return boolean
     */
    public function afterIsActive(ParentConfig $subject, $result)
    {
        if($this->customerSession->getFromMvpProductCreate())
        {
            return false;
        }
        return $result;
    }
}

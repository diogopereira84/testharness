<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Plugin;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Element\AbstractBlock;

class LinksPlugin
{
    /**
     * LinksPlugin Construct
     *
     * @param SelfReg $selfReg
     */
    public function __construct(
        private SelfReg $selfReg
    )
    {
    }

    /**
     * Hide customer account navigation link
     *
     * @param Links $subject
     * @param result
     * @param AbstractBlock $link
     *
     * @return array|mixed
     */
    public function afterRenderLink(Links $subject, $result, AbstractBlock $link)
    {
        if($this->selfReg->toggleUserRolePermissionEnable()) {
            return $result;
        }
        if(!$this->selfReg->isSelfRegCustomerAdmin() && $link->getNameInLayout() == 'customer-account-company-company-users-link') {
            $result = null;
        }
        return $result;
    }
}

<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);
namespace Fedex\InBranch\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends AbstractHelper
{
    /**
     * @param Context $context
     * @param Session $cartSession
     */
    public function __construct(
        Context $context,
        private Session $cartSession
    ) {
        parent::__construct($context);
    }


    /**
     * Check if Inbranch product is in cart
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function checkInCartINBranch()
    {
        $result = false;
        $items = $this->cartSession->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $result = $item->getProduct()->getProductLocationBranchNumber();
            if ($result !=''):
                return $result;
            endif;
        }
        return $result;
    }
}

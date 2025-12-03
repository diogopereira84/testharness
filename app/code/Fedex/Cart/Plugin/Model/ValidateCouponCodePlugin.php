<?php
declare(strict_types=1);

namespace Fedex\Cart\Plugin\Model;

use Fedex\Cart\Helper\Data;
use Magento\SalesRule\Model\ValidateCouponCode;

class ValidateCouponCodePlugin
{
    /**
     * @param Data $helper
     */
    public function __construct(
        private Data $helper
    ) {
    }

    /**
     * Around plugin for execute method
     *
     * @param ValidateCouponCode $subject
     * @param callable $proceed
     * @param array $couponCodes
     * @param int|null $customerId
     * @return array
     */
    public function aroundExecute(
        ValidateCouponCode $subject,
        callable           $proceed,
        array              $couponCodes,
        ?int               $customerId = null
    ): array {
        if ($this->helper->isMixedCartPromoErrorToggleEnabled()) {
            $validCouponCodes = [];
            foreach ($couponCodes as $code) {
                $validCouponCodes[] = $code;
            }
            return $validCouponCodes;
        }

        return $proceed($couponCodes, $customerId);
    }
}

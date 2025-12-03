<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage\Rule;

use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Magento\Framework\App\RequestInterface;

class HasCouponRule implements RuleInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isValid(TransportInterface $transport): bool
    {
        $shippingData = $this->request->getParam('ship_method_data');
        if (
            !(filter_var($this->request->getParam('removedFedexAccount'), FILTER_VALIDATE_BOOL))
            && $transport->getFXORateQuote()->hasMultiplePromotion()
            && $transport->getFXORateQuote()->hasDetailCouponDiscounts()
            && $transport->getFXORateQuote()->hasDetailShippingDeliveryDiscount()
            && isset($shippingData["carrier_code"])
            && $shippingData["carrier_code"] == "fedexshipping"
        ) {
            return true;
        }

        return !is_null($transport->getCart()->getCouponCode());
    }
}

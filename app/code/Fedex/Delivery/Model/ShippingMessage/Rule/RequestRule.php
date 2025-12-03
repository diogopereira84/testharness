<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage\Rule;

use Fedex\Delivery\Model\ShippingMessage\RuleCompositeInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Serialize\JsonValidator;

class RequestRule implements RuleCompositeInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SerializerInterface $serializer,
        private readonly JsonValidator $jsonValidator,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * Bellow are the conditions for the Shipping Message to be shown:
     *
     * If there is at least one COUPON discount but no delivery line discount
     * If total of all the COUPON discounts is same as delivery line discount
     *
     * @inheritDoc
     */
    public function isValid(TransportInterface $transport): bool
    {
        /** NOT A FREE SHIPPING METHOD SELECTED */
        $shippingData = $this->request->getParam('ship_method_data');

        if (is_null($shippingData)) {
            $shippingData = $this->request->getContent();
        }

        if (!is_array($shippingData) && $this->jsonValidator->isValid($shippingData)) {
            $shippingData = $this->serializer->unserialize($shippingData);

        }

        if (isset($shippingData['ship_method_data']['amount'])
            && is_numeric($shippingData['ship_method_data']['amount'])
            && $shippingData['ship_method_data']['amount'] > 0) {
            return false;
        }

        if (isset($shippingData['amount']) && is_numeric($shippingData['amount']) && $shippingData['amount'] > 0) {
            return false;
        }

        if ((filter_var($this->request->getParam('removedFedexAccount'), FILTER_VALIDATE_BOOL)) && $transport->getFXORateQuote()->hasMultiplePromotion()) {
            return false;
        }

//        if ($transport->getFXORateQuote()->hasMultiplePromotion()) {
//            return false;
//        }

        if (is_array($shippingData) && array_key_exists('ship_method',$shippingData)
            && $shippingData['ship_method'] == null) {
            return false;
        }

        /** NOT A FREE SHIPPING METHOD SELECTED */
        if ($transport->getStrategy() == 'rateQuote') {
            if ($transport->getFXORateQuote()->hasDetailFreeShipping()
                && $transport->getFXORateQuote()->hasDetailCouponDiscounts()
                && !$transport->getFXORateQuote()->hasDetailShippingDeliveryDiscount()) {
                return true;
            }
            if ($transport->getFXORateQuote()->hasSinglePromotion()
                && !$transport->getFXORateQuote()->hasDetailShippingDeliveryDiscount()) {
                return true;
            }

            if ($transport->getFXORateQuote()->hasMultiplePromotion()
                && $transport->getFXORateQuote()->hasDetailFreeShipping()
                && !is_null($this->checkoutSession->getQuote()->getCouponCode())) {
                return true;
            }

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

        }

        return false;
    }
}

<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model;

use Fedex\Delivery\Api\ShippingMessageInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\ShippingMessage\RuleCompositeInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;

class ShippingMessage implements ShippingMessageInterface
{
    /**
     * Show message key
     */
    private const SHOW_FREE_SHIPPING_MESSAGE = 'show_free_shipping_message';

    /**
     * Message text key
     */
    private const FREE_SHIPPING_MESSAGE = 'free_shipping_message';

    /**
     * @param RuleCompositeInterface $rule
     * @param DeliveryHelper $deliveryHelper
     */
    public function __construct(
        private readonly RuleCompositeInterface $rule,
        private readonly DeliveryHelper $deliveryHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMessage(TransportInterface $transport): array
    {
        $response = [
            self::SHOW_FREE_SHIPPING_MESSAGE => 0,
            self::FREE_SHIPPING_MESSAGE => null
        ];

        if ($this->rule->isValid($transport)) {
            $response[self::SHOW_FREE_SHIPPING_MESSAGE] = 1;
            $response[self::FREE_SHIPPING_MESSAGE] = $this->deliveryHelper->getPromoMessageText();
        }

        return $response;
    }
}

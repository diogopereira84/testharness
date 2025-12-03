<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage\Rule;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;

class IsEnabledRule implements RuleInterface
{
    /**
     * @param DeliveryHelper $deliveryHelper
     */
    public function __construct(
        private readonly DeliveryHelper $deliveryHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isValid(TransportInterface $transport): bool
    {
        return $this->deliveryHelper->isGroundShippingPromoMessagingActive();
    }
}

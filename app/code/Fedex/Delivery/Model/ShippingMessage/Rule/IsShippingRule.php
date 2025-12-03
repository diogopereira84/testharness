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

class IsShippingRule implements RuleInterface
{
    /**
     * @inheritDoc
     */
    public function isValid(TransportInterface $transport): bool
    {
        return (bool)$transport->getCart()->getIsFromShipping();
    }
}

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

interface RuleInterface
{
    /**
     * Return the rule status
     *
     * @param TransportInterface $transport
     *
     * @return bool
     */
    public function isValid(TransportInterface $transport): bool;
}

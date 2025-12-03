<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage;

interface RuleCompositeInterface
{
    /**
     * Return the validation status of all rules
     *
     * @param TransportInterface $transport
     *
     * @return bool
     */
    public function isValid(TransportInterface $transport): bool;
}

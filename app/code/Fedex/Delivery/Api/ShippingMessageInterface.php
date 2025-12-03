<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Api;

use Fedex\Delivery\Model\ShippingMessage\TransportInterface;

interface ShippingMessageInterface
{
    /**
     * Get shipping message
     *
     * @param TransportInterface $transport
     * @return array[]
     */
    public function getMessage(TransportInterface $transport): array;
}

<?php
/**
 * @category    Fedex
 * @package     Fedex_TrackOrder
 */

declare (strict_types = 1);

namespace Fedex\TrackOrder\Model;

use Fedex\TrackOrder\Api\CurlRequestInterface;
use Psr\Log\LoggerInterface;

class OrderDetailApi
{
    /**
     * Constructor
     *
     * @param CurlRequestInterface $curlRequest
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CurlRequestInterface $curlRequest,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Get order details from API
     *
     * @param [type] $orderId
     * @return void
     */
    public function fetchOrderDetailFromApi($orderId)
    {
        try {
            return $this->curlRequest->sendRequest($orderId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .' Error Message: ' . $e->getMessage());
            return [
                'order_id' => $orderId,
                'isValid' => false,
                'error_message' => 'An error occurred while fetching the order details. Please try again later. ' . $e->getMessage()
            ];
        }
    }
}

<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\CancelOrderInterface;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Mirakl\MMP\FrontOperator\Request\Order\Workflow\CancelOrderRequest;
use Psr\Log\LoggerInterface;

class CancelOrder implements CancelOrderInterface
{
    /**
     * @param MMP $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        private MMP             $client,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $miraklOrderId
     * @return bool
     */
    public function cancelOrder(string $miraklOrderId): bool
    {
        $status = false;
        try {
            $request = new CancelOrderRequest($miraklOrderId);
            $this->client->send($request);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Order was cancelled in Mirakl : ' . $miraklOrderId);
            $status = true;
        } catch (\Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Order could not be cancelled in Mirakl due to ' . $e->getMessage());
        }
        return $status;
    }

}
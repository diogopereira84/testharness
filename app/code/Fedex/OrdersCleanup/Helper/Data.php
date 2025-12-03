<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrdersCleanup\Helper;

use Fedex\OrdersCleanup\Model\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;

/**
 * Data Helper
 */
class Data extends AbstractHelper
{

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Config $moduleConfig
     */
    public function __construct(
        Context                          $context,
        private readonly LoggerInterface $logger,
        private readonly Config          $moduleConfig
    )
    {
        parent::__construct($context);
    }

    /**
     * Logging
     *
     * @param $location
     * @param $message
     * @param $isCritical
     * @param $dataJson
     * @return void
     */
    public function logMessage($location, $message, $isCritical, $dataJson = null): void
    {
        if ($this->moduleConfig->isLoggingEnabled()) {
            if ($dataJson) {
                $logMessage = $location . ' :OrdersCleanup' .
                    ' order id = ' . $dataJson['entity_id'] . ' increment_id = ' . $dataJson['increment_id'] .
                    ' type = ' . $dataJson['order_type'] .
                    '. ' . $message;
            } else {
                $logMessage = $location . ' :OrdersCleanup' . '. ' . $message;
            }

            if ($isCritical) {
                $this->logger->critical($logMessage);
            } else {
                $this->logger->info($logMessage);
            }
        }
    }
}

<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Module\Manager;

class ToggleObserver implements ObserverInterface
{
    /**
     * Toggle Observer Constructor
     *
     * @param Logger $logger
     * @param ToggleConfig $toggleConfig
     * @param Manager $moduleManager
     */
    public function __construct(
        protected Logger $logger,
        protected ToggleConfig $toggleConfig,
        protected Manager $moduleManager
    )
    {
    }

    /**
     * Save toggle configuration in cache
     *
     * @return void
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $this->toggleConfig->saveToggleConfigCache();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            . ' Error in saving toggle configuration in cache from frontend observer: '
             . $e->getMessage());
        }
    }
}

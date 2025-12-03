<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\HttpRequestLogger\Model;

use Psr\Log\LoggerInterface;

class Logger
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param mixed $url
     * @param mixed $timeSpent
     * @return void
     */
    public function log(mixed $url, mixed$timeSpent): void
    {
        $this->logger->info(__CLASS__.':'.__LINE__.' - '.sprintf('HTTP Log Feature - Request URL: %s, Time Spent: %f seconds', $url, $timeSpent));
    }
}

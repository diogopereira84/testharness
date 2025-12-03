<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model;

use Psr\Log\LoggerInterface;
use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;

class Logger implements LoggerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private GeneralConfig   $generalConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->emergency($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->alert($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->critical($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->error($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->notice($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @inheritDoc
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($this->generalConfig->isLogEnabled()) {
            $this->logger->log($level, $message, $context);
        }
    }
}

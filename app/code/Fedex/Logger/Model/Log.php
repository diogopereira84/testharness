<?php

namespace Fedex\Logger\Model;

use Magento\Framework\Logger\Monolog;
use Monolog\Logger;
use Magento\Framework\Stdlib\CookieManagerInterface\Proxy as CookieManagerInterface;

class Log extends Monolog
{
    /**
     * @param CookieManagerInterface $cookieManager
     * {@inheritdoc}
     */
    public function __construct(
        $name,
        private CookieManagerInterface $cookieManager,
        array $handlers = [],
        array $processors = []
    )
    {
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Prepends a unique php session id to the log message and adds a log record
     *
     * @param int $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @return bool Whether the record has been processed
     */
    public function addRecord(int $level, string $message, array $context = [], $datetime = null): bool
    {
        $phpSessId = $this->cookieManager->getCookie('PHPSESSID');

        if ($phpSessId) {
            $phpSessId = $phpSessId . " ";
        }

        if ($message instanceOf \Exception) {
            if (!isset($context['exception'])) {
                $context['exception'] = $message;
            }
            $message = $phpSessId . $message->getMessage();
        } else {
            $message = $phpSessId . $message;
        }

        return Logger::addRecord($level, $message, $context);
    }
}

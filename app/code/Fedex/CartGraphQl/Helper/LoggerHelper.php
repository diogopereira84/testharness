<?php

namespace Fedex\CartGraphQl\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Magento\NewRelicReporting\Model\NewRelicWrapper;
use Magento\Framework\Session\SessionManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LoggerHelper extends AbstractHelper
{
    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param NewRelicWrapper $newRelicWrapper
     * @param SessionManagerInterface $sessionManager
     * @param ToggleConfig $toggleConfig
     */
    public function __construct
    (
        protected Context $context,
        private readonly LoggerInterface $logger,
        private readonly NewRelicWrapper $newRelicWrapper,
        private readonly SessionManagerInterface $sessionManager,
        private readonly ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * @param string $message
     * @param array $context
     * @param bool $excludeHeaderParamsToInitiateInNewRelic
     * @return void
     */
    public function info(string $message, array $context = [], bool $excludeHeaderParamsToInitiateInNewRelic = true): void
    {
        if ($context === []) {
            $this->logger->info($message);
            return;
        }
        $context = $this->setExtraDataInContext($context, $message);
        if (!$excludeHeaderParamsToInitiateInNewRelic && $this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $context = $this->initializeNewrelicCustomParameter($context);
        }
        $this->logger->info($message . PHP_EOL, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @param bool $excludeHeaderParamsToInitiateInNewRelic
     * @return void
     */
    public function debug(string $message, array $context = [], bool $excludeHeaderParamsToInitiateInNewRelic = true): void
    {
        if ($context === []) {
            $this->logger->debug($message);
            return;

        }
        $context = $this->setExtraDataInContext($context, $message);
        if (!$excludeHeaderParamsToInitiateInNewRelic && $this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $context = $this->initializeNewrelicCustomParameter($context);
        }
        $this->logger->debug($message . PHP_EOL, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @param bool $excludeHeaderParamsToInitiateInNewRelic
     * @return void
     */
    public function error(string $message, array $context = [], bool $excludeHeaderParamsToInitiateInNewRelic = true): void
    {
        if ($context === []) {
            $this->logger->error($message);
            return;

        }
        $context = $this->setExtraDataInContext($context, $message);
        if (!$excludeHeaderParamsToInitiateInNewRelic && $this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $context = $this->initializeNewrelicCustomParameter($context);
        }
        $this->logger->error($message . PHP_EOL, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @param bool $excludeHeaderParamsToInitiateInNewRelic
     * @return void
     */
    public function critical(string $message, array $context = [], bool $excludeHeaderParamsToInitiateInNewRelic = true): void
    {
        if ($context === []) {
            $this->logger->critical($message);
            return;
        }
        $context = $this->setExtraDataInContext($context, $message);
        if (!$excludeHeaderParamsToInitiateInNewRelic && $this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $context = $this->initializeNewrelicCustomParameter($context);
        }
        $this->logger->critical($message . PHP_EOL, $context);
    }

    /**
     * @param $context
     * @param $message
     * @return array
     */
    public function setExtraDataInContext($context, $message): array
    {
        if (!is_array($context)) {
            return [];
        }
        if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $phpSessionId = $this->getPhpSessionId();
            if ($phpSessionId) {
                $message = $phpSessionId . ' ' . $message;
            }
        }
        $context['message'] = $message;
        $context['timestamp'] = time();
        return $context;
    }

    /**
     * @param array $context
     * @return array
     */
    public function initializeNewrelicCustomParameter(array $context): array
    {
        if (empty($context)) {
            return [];
        }
        foreach ($context as $key => $value) {
            if (!is_string($value)) {
                $this->newRelicWrapper->addCustomParameter($key, $value);
                continue;
            }
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $context[$key] = json_encode(
                    [$decoded],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
                $value = $context[$key];
            }
            $this->newRelicWrapper->addCustomParameter($key, $value);
        }
        return $context;
    }

    /**
     * @return string
     */
    public function getPhpSessionId(): string
    {
        return $this->sessionManager->getSessionId() ?? '';
    }
}

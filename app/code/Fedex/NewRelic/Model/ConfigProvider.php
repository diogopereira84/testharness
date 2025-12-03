<?php
/**
 * @category  Fedex
 * @package   Fedex_NewRelic
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\NewRelic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\NewRelic\Api\ConfigProviderInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * NewRelic Deployment Marker API URL xml path
     */
    private const API_URL = "https://api.newrelic.com/v2/applications/{{app_id}}/deployments.json";

    /**
     * NewRelic Deployment Marker API KEY xml path
     */
    private const API_KEY = 'fedex/new_relic_deployment_marker/api_key';

    /**
     * NewRelic Deployment Marker Identifier xml path
     */
    private const APPLICATION_IDENTIFIER = 'fedex/new_relic_deployment_marker/application_identifier';

    /**
     * NewRelic Deployment Marker Enabled xml path
     */
    private const ENABLED = 'fedex/new_relic_deployment_marker/enabled';

    /**
     * NewRelic Deployment Marker Changelog xml path
     */
    private const CHANGELOG = 'fedex/new_relic_deployment_marker/next_changelog';

    /**
     * NewRelic Deployment Marker description xml path
     */
    private const DESCRIPTION = 'fedex/new_relic_deployment_marker/next_description';

    /**
     * NewRelic Deployment Marker user xml path
     */
    private const USER = 'fedex/new_relic_deployment_marker/next_user';

    /**
     * Initializes ConfigProvider
     *
     * This class is responsible for providing
     * Magento system configuration from admin
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly WriterInterface $configWriter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::ENABLED
        );
    }

    /**
     * @inheritDoc
     */
    public function getApiUrl(): string
    {
        return self::API_URL;
    }

    /**
     * @inheritDoc
     */
    public function getApiKey(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::API_KEY
        );
    }

    /**
     * @inheritDoc
     */
    public function getAppIdentifier(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::APPLICATION_IDENTIFIER
        );
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::DESCRIPTION
        );
    }

    /**
     * @inheritDoc
     */
    public function getChangeLog(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CHANGELOG
        );
    }

    /**
     * @inheritDoc
     */
    public function getUser(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::USER
        );
    }

    /**
     * @inheritDoc
     */
    public function canPerformDeploymentMarker(): bool
    {
        if ($this->getStatus()
            && !empty($this->getAppIdentifier())
            && !empty($this->getApiKey())) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function resetFields(): void
    {
        $this->configWriter->save(
            self::CHANGELOG,
            ''
        );
        $this->configWriter->save(
            self::DESCRIPTION,
            ''
        );
        $this->configWriter->save(
            self::USER,
            ''
        );
    }
}

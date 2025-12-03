<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Plugin\Model\Http\Command;

use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\FeedExportStatusBuilder;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SaaSCommon\Model\Http\Command\SubmitFeed as SubmitFeedCore;
use Magento\SaaSCommon\Model\Logging\SaaSExportLoggerInterface;

class SubmitFeedPlugin
{
    const SAAS_ENVIRONMENT_NAME = 'services_connector/services_id/environment_name';
    const ENV_VARIABLE_CONTROL = 'storefront_features/website_configuration/environment_variable_control_feed_export';
    const TESTING_ENV_L1 = 'Dev Testing L1';
    const ADOBE_CATALOG_SERVICE_FEED = 'ADOBE_CATALOG_SERVICE_FEED';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SaaSExportLoggerInterface $logger
     * @param FeedExportStatusBuilder $feedExportStatusBuilder
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected SaaSExportLoggerInterface $logger,
        protected FeedExportStatusBuilder $feedExportStatusBuilder
    ) {
    }

    /**
     * Logic to prevent ALS Sync in local environment for all environments except "Dev Testing L1"
     *
     * @param SubmitFeedCore $subject
     * @param callable $proceed
     * @param string $feedName
     * @param array $data
     * @param int|null $timeout
     * @return FeedExportStatus
     */
    public function aroundExecute(
        SubmitFeedCore $subject,
        callable $proceed,
        string $feedName,
        array $data,
        ?int $timeout = null
    ): FeedExportStatus {
        if ($this->scopeConfig->getValue(self::ENV_VARIABLE_CONTROL)) {
            $environmentName = $this->scopeConfig->getValue(self::SAAS_ENVIRONMENT_NAME);
            $feedExportEnabled = getenv(self::ADOBE_CATALOG_SERVICE_FEED);
            if ($environmentName !== self::TESTING_ENV_L1 && $feedExportEnabled !== 'true') {
                $errorMessage = 'Feed export is disabled via environment variable.';
                $this->logger->error($errorMessage);
                return $this->feedExportStatusBuilder->build(
                    ExportStatusCodeProvider::APPLICATION_ERROR,
                    $errorMessage
                );
            }
        }
        return $proceed($feedName, $data, $timeout);
    }
}

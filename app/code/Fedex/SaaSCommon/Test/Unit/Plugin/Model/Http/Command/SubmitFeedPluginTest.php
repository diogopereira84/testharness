<?php
namespace Fedex\SaaSCommon\Test\Unit\Plugin\Model\Http\Command;

use Fedex\SaaSCommon\Plugin\Model\Http\Command\SubmitFeedPlugin;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\FeedExportStatusBuilder;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SaaSCommon\Model\Http\Command\SubmitFeed;
use Magento\SaaSCommon\Model\Logging\SaaSExportLoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubmitFeedPluginTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var SaaSExportLoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FeedExportStatusBuilder|MockObject
     */
    private $feedExportStatusBuilderMock;

    /**
     * @var SubmitFeed|MockObject
     */
    private $submitFeedMock;

    /**
     * @var FeedExportStatus|MockObject
     */
    private $feedExportStatusMock;

    /**
     * @var callable|MockObject
     */
    private $proceedMock;

    /**
     * @var SubmitFeedPlugin
     */
    private $submitFeedPlugin;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->loggerMock = $this->createMock(SaaSExportLoggerInterface::class);
        $this->feedExportStatusBuilderMock = $this->createMock(FeedExportStatusBuilder::class);
        $this->submitFeedMock = $this->createMock(SubmitFeed::class);
        $this->feedExportStatusMock = $this->createMock(FeedExportStatus::class);

        $this->proceedMock = function ($feedName, $data, $timeout = null) {
            return $this->feedExportStatusMock;
        };

        $this->submitFeedPlugin = new SubmitFeedPlugin(
            $this->scopeConfigMock,
            $this->loggerMock,
            $this->feedExportStatusBuilderMock
        );
    }

    /**
     * @param string $envName
     * @param string|bool|null $adobeCatalogServiceFeed
     * @param bool $toggleEnabled
     * @return void
     */
    private function setUpEnvironment(string $envName, $adobeCatalogServiceFeed = null, bool $toggleEnabled = true): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function ($path) use ($envName, $toggleEnabled) {
                if ($path === SubmitFeedPlugin::ENV_VARIABLE_CONTROL) {
                    return $toggleEnabled;
                }
                if ($path === SubmitFeedPlugin::SAAS_ENVIRONMENT_NAME) {
                    return $envName;
                }
                return null;
            });

        if ($adobeCatalogServiceFeed !== null) {
            putenv(SubmitFeedPlugin::ADOBE_CATALOG_SERVICE_FEED . '=' . $adobeCatalogServiceFeed);
        } else {
            putenv(SubmitFeedPlugin::ADOBE_CATALOG_SERVICE_FEED);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        putenv(SubmitFeedPlugin::ADOBE_CATALOG_SERVICE_FEED);
        parent::tearDown();
    }

    /**
     * Test aroundExecute method when toggle is disabled
     */
    public function testAroundExecuteWhenToggleIsDisabled(): void
    {
        $feedName = 'products';
        $data = ['product1', 'product2'];
        $timeout = 30;

        $this->setUpEnvironment('Production', null, false);

        $result = $this->submitFeedPlugin->aroundExecute(
            $this->submitFeedMock,
            $this->proceedMock,
            $feedName,
            $data,
            $timeout
        );

        $this->assertSame($this->feedExportStatusMock, $result);
    }

    /**
     * Test aroundExecute method when environment is Dev Testing L1
     */
    public function testAroundExecuteForDevTestingL1Environment(): void
    {
        $feedName = 'products';
        $data = ['product1', 'product2'];
        $timeout = 30;

        $this->setUpEnvironment(SubmitFeedPlugin::TESTING_ENV_L1);

        $result = $this->submitFeedPlugin->aroundExecute(
            $this->submitFeedMock,
            $this->proceedMock,
            $feedName,
            $data,
            $timeout
        );

        $this->assertSame($this->feedExportStatusMock, $result);
    }

    /**
     * Test aroundExecute method when environment is not Dev Testing L1 but ADOBE_CATALOG_SERVICE_FEED is true
     */
    public function testAroundExecuteForNonDevEnvironmentWithEnabledFeedExport(): void
    {
        $feedName = 'products';
        $data = ['product1', 'product2'];
        $timeout = 30;

        $this->setUpEnvironment('Production', 'true');

        $result = $this->submitFeedPlugin->aroundExecute(
            $this->submitFeedMock,
            $this->proceedMock,
            $feedName,
            $data,
            $timeout
        );

        $this->assertSame($this->feedExportStatusMock, $result);
    }

    /**
     * Test aroundExecute method when environment is not Dev Testing L1 and ADOBE_CATALOG_SERVICE_FEED is not true
     */
    public function testAroundExecuteForNonDevEnvironmentWithDisabledFeedExport(): void
    {
        $feedName = 'products';
        $data = ['product1', 'product2'];
        $timeout = 30;

        $this->setUpEnvironment('Production', 'false');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Feed export is disabled via environment variable.');

        $this->feedExportStatusBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                ExportStatusCodeProvider::APPLICATION_ERROR,
                'Feed export is disabled via environment variable.'
            )
            ->willReturn($this->feedExportStatusMock);

        $result = $this->submitFeedPlugin->aroundExecute(
            $this->submitFeedMock,
            $this->proceedMock,
            $feedName,
            $data,
            $timeout
        );

        $this->assertSame($this->feedExportStatusMock, $result);
    }

    /**
     * Test aroundExecute method when environment is not Dev Testing L1 and ADOBE_CATALOG_SERVICE_FEED is not set
     */
    public function testAroundExecuteForNonDevEnvironmentWithoutFeedExportSetting(): void
    {
        $feedName = 'products';
        $data = ['product1', 'product2'];
        $timeout = 30;

        $this->setUpEnvironment('Production');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Feed export is disabled via environment variable.');

        $this->feedExportStatusBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                ExportStatusCodeProvider::APPLICATION_ERROR,
                'Feed export is disabled via environment variable.'
            )
            ->willReturn($this->feedExportStatusMock);

        $result = $this->submitFeedPlugin->aroundExecute(
            $this->submitFeedMock,
            $this->proceedMock,
            $feedName,
            $data,
            $timeout
        );

        $this->assertSame($this->feedExportStatusMock, $result);
    }
}

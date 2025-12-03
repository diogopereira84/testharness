<?php
declare(strict_types=1);

namespace Fedex\Search\Test\Unit\Plugin;

use Fedex\Search\Helper\Config;
use Fedex\Search\Plugin\SearchQueryPlugin;
use Magento\Search\Model\Query as QueryModel;
use Magento\Search\Model\ResourceModel\Query as QueryResource;
use PHPUnit\Framework\TestCase;

class SearchQueryPluginTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelper;

    /**
     * @var SearchQueryPlugin
     */
    private $plugin;

    /**
     * @var QueryModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryModel;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(Config::class);
        $this->plugin = new SearchQueryPlugin($this->configHelper);
        $this->queryModel = $this->createMock(QueryModel::class);
    }

    public function testSaveIncrementalPopularity_SkipsWhenDisabled(): void
    {
        $this->configHelper->method('isQueryTrackingDisabled')->willReturn(true);

        $proceedCalled = false;

        $closure = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };

        $this->plugin->aroundSaveIncrementalPopularity(
            $this->createMock(QueryResource::class),
            $closure,
            $this->queryModel
        );

        $this->assertFalse($proceedCalled, 'Expected proceed NOT to be called when tracking is disabled.');
    }

    public function testSaveIncrementalPopularity_CallsProceedWhenEnabled(): void
    {
        $this->configHelper->method('isQueryTrackingDisabled')->willReturn(false);

        $proceedCalled = false;

        $closure = function ($query) use (&$proceedCalled) {
            $proceedCalled = true;
            $this->assertInstanceOf(QueryModel::class, $query);
        };

        $this->plugin->aroundSaveIncrementalPopularity(
            $this->createMock(QueryResource::class),
            $closure,
            $this->queryModel
        );

        $this->assertTrue($proceedCalled, 'Expected proceed to be called when tracking is enabled.');
    }

    public function testSaveNumResults_SkipsWhenDisabled(): void
    {
        $this->configHelper->method('isQueryTrackingDisabled')->willReturn(true);

        $proceedCalled = false;
        $closure = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };

        $this->plugin->aroundSaveNumResults(
            $this->createMock(QueryResource::class),
            $closure,
            $this->queryModel
        );

        $this->assertFalse($proceedCalled, 'Expected proceed NOT to be called when tracking is disabled.');
    }

    public function testSaveNumResults_CallsProceedWhenEnabled(): void
    {
        $this->configHelper->method('isQueryTrackingDisabled')->willReturn(false);

        $proceedCalled = false;
        $closure = function ($query) use (&$proceedCalled) {
            $proceedCalled = true;
            $this->assertInstanceOf(QueryModel::class, $query);
        };

        $this->plugin->aroundSaveNumResults(
            $this->createMock(QueryResource::class),
            $closure,
            $this->queryModel
        );

        $this->assertTrue($proceedCalled, 'Expected proceed to be called when tracking is enabled.');
    }

    public function testSaveIncrementalPopularity_WithInvalidQueryObject(): void
    {
        $this->expectException(\TypeError::class);

        $this->plugin->aroundSaveIncrementalPopularity(
            $this->createMock(QueryResource::class),
            function () {},
            null
        );
    }
}

<?php
/**
 * @category  Fedex
 * @package   Fedex_CatalogSyncAdmin
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CatalogSyncAdmin\Test\Unit\Cron;

use Fedex\CatalogSyncAdmin\Cron\ForceResyncCatalog;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SaaSCommon\Model\ResyncManager;
use Magento\SaaSCommon\Model\ResyncManagerPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ForceResyncCatalogTest extends TestCase
{
    /**
     * @var ResyncManagerPool|MockObject
     */
    private $resyncManagerPool;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    /**
     * @var ResyncManager|MockObject */
    private $resyncManager;

    /**
     * @var ForceResyncCatalog
     */
    private $forceResyncCatalog;

    /**
     * @var array
     */
    private $getResyncManagerArgs = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resyncManagerPool = $this->createMock(ResyncManagerPool::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->resyncManager = $this->createMock(ResyncManager::class);
        $this->forceResyncCatalog = new ForceResyncCatalog(
            $this->resyncManagerPool,
            $this->logger,
            $this->toggleConfig
        );
    }

    /**
     * @return void
     */
    public function testExecuteSuccessToggleOff()
    {
        $feedNames = $this->getFeedNames();
        $feedNames[] = 'productoverrides';

        $resyncManager = $this->createMock(ResyncManager::class);

        $this->resyncManagerPool->expects($this->exactly(count($feedNames)))
            ->method('getResyncManager')
            ->willReturnCallback(function ($feedName) use ($resyncManager) {
                $this->getResyncManagerArgs[] = $feedName;
                return $resyncManager;
            });

        $resyncManager->expects($this->exactly(count($feedNames)))
            ->method('executeFullResync');

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->logger->expects($this->atLeast(1))
            ->method('info');

        $this->forceResyncCatalog->execute();

        $this->assertEquals($feedNames, $this->getResyncManagerArgs);
    }

    /**
     * @return void
     */
    public function testExecuteSuccessToggleOn()
    {
        $feedNames = $this->getFeedNames();
        $resyncManager = $this->createMock(ResyncManager::class);

        $this->resyncManagerPool->expects($this->exactly(count($feedNames)))
            ->method('getResyncManager')
            ->willReturnCallback(function ($feedName) use ($resyncManager) {
                $this->getResyncManagerArgs[] = $feedName;
                return $resyncManager;
            });

        $resyncManager->expects($this->exactly(count($feedNames)))
            ->method('executeFullResync');

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->logger->expects($this->atLeast(1))
            ->method('info');

        $this->forceResyncCatalog->execute();

        $this->assertEquals($feedNames, $this->getResyncManagerArgs);
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $feedNames = $this->getFeedNames();
        $resyncManager = $this->createMock(ResyncManager::class);

        $this->resyncManagerPool->expects($this->exactly(3))
            ->method('getResyncManager')
            ->willReturnCallback(function ($feedName) use ($resyncManager) {
                $this->getResyncManagerArgs[] = $feedName;
                return $resyncManager;
            });

        $resyncManager->expects($this->exactly(3))
            ->method('executeFullResync')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(null),
                $this->returnValue(null),
                $this->throwException(new \Exception('Test exception'))
            ));

        $this->logger->expects($this->atLeast(1))
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('An error occurred during catalog data re-sync: Test exception'));

        $this->forceResyncCatalog->execute();


        $expectedFeedNames = array_slice($feedNames, 0, 3);
        $this->assertEquals($expectedFeedNames, $this->getResyncManagerArgs);
    }

    /**
     * @return string[]
     */
    private function getFeedNames(): array
    {
        return [
            'productattributes',
            'products',
            'scopesCustomerGroup',
            'scopesWebsite',
            'prices'
        ];
    }
}

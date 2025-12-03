<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\Observer\ConfigObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigObserverTest extends TestCase
{
    protected $loggerMock;
    protected $requestMock;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var ConfigObserver|MockObject
     */
    protected $configObserver;

    /**
     * @var |MockObject
     */
    protected $toggleConfig;
    private Observer $observer;
    private DataObject $event;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'saveToggleConfigCache'
                ]
            )
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['critical'])
                            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getParam'])
                            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->configObserver = $this->objectManager->getObject(
            ConfigObserver::class,
            [
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Delete Browser Specific Cookies
     */
    public function testExecute()
    {
        $eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cacheValue = [
                        "environment_toggle" => [
                            "fields" => [
                                "enable_fcl" => [
                                    "value" => 1
                                ]
                            ]
                        ]
                    ];
        $this->requestMock->expects($this->any())
                            ->method('getParam')
                            ->with('groups')
                            ->willReturn($cacheValue);

        $this->assertEquals(null, $this->configObserver->execute($eventObserver));
    }

    /**
     * Test execute function
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->loggerMock->expects($this->exactly(1))->method('critical');
        $this->requestMock->expects($this->any())
                            ->method('getParam')
                            ->willThrowException($exception);
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
        $returnValue = null;
        $this->assertSame($returnValue, $this->configObserver->execute($this->observer));
    }
}

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
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\Observer\ToggleObserver;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Module\Manager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToggleObserverTest extends TestCase
{
    protected $_toggleConfig;
    protected $_loggerMock;
    /**
     * @var (\Magento\Framework\App\CacheInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_cacheInterface;
    protected $_manager;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;
    protected $_toggleObserver;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var ToggleObserver|MockObject
     */
    protected $toggleObserver;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * Manager variable
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $manager;
    private Observer $observer;
    private DataObject $event;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->_toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getToggleConfig',
                    'saveToggleConfigCache',
                    'getToggleConfigValue',
                    'disableEnableModule'
                ]
            )
            ->getMock();

        $this->_loggerMock = $this->getMockBuilder(LoggerInterface::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['critical'])
                            ->getMockForAbstractClass();

        $this->_cacheInterface = $this->getMockBuilder(CacheInterface::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['load'])
                            ->getMockForAbstractClass();

        $this->_manager = $this->getMockBuilder(Manager::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['isOutputEnabled'])
                            ->getMock();

        $this->_objectManager = new ObjectManager($this);

        $this->_toggleObserver = $this->_objectManager->getObject(
            ToggleObserver::class,
            [
                'logger' => $this->_loggerMock,
                'toggleConfig' => $this->_toggleConfig,
                'moduleManager' => $this->_manager
            ]
        );
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $cacheData = [
                        "enable_fcl" => [
                            "value" => 1
                        ]
                    ];

        $this->_toggleConfig->expects($this->any())
                            ->method('getToggleConfig')
                            ->willReturn($cacheData);

        $this->_toggleConfig->expects($this->any())
                            ->method('saveToggleConfigCache');

        $this->_toggleConfig->expects($this->any())
                            ->method('getToggleConfigValue')
                            ->willReturn(false);

        $this->_manager->expects($this->any())
                            ->method('isOutputEnabled')
                            ->willReturnSelf();

        $this->_toggleConfig->expects($this->any())
                            ->method('disableEnableModule')
                            ->willReturnSelf();

        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
        $returnValue = null;
        $this->assertSame($returnValue, $this->_toggleObserver->execute($this->observer));
    }

    /**
     * Test execute function
     */
    public function testExecuteWithEnableModule()
    {
        $this->_toggleConfig->expects($this->any())
                            ->method('saveToggleConfigCache');

        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
        $returnValue = null;
        $this->assertSame($returnValue, $this->_toggleObserver->execute($this->observer));
    }

    /**
     * Test execute function with exception
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->_toggleConfig->expects($this->any())
                            ->method('saveToggleConfigCache')
                            ->willThrowException($exception);
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
        $returnValue = null;
        $this->assertSame($returnValue, $this->_toggleObserver->execute($this->observer));
    }
}

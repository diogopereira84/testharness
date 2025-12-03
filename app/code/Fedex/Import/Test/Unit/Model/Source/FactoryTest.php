<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManagerInterface;
use Fedex\Import\Model\Source\Factory;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

class FactoryTest extends Testcase
{
    protected $objectManagerInterface;
    /**
     * @var (\Magento\ImportExport\Model\Source\Import\AbstractBehavior & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractBehavior;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $Mock;
    /**
     * Set up method
     */
    protected function setUp():void
    {
        $this->objectManagerInterface = $this->getMockBuilder(ObjectManagerInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->abstractBehavior = $this->getMockBuilder(AbstractBehavior::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            Factory::class,
            [
                'objectManager' => $this->objectManagerInterface,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test method for Create
     *
     * @return void
     */
    public function testCreate()
    {
        $this->objectManagerInterface->expects($this->any())->method('create')->willReturnSelf();
        $this->assertSame($this->objectManagerInterface, $this->Mock->create('Factory'));
    }

    /**
     * Test method for Create WithoutParameters
     *
     * @return void
     */
    public function testCreateWithoutParameters()
    {
        $this->assertSame($this->expectExceptionMessage("Incorrect class name"), $this->Mock->create(null));
    }
}

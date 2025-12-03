<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\Years;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class YearsTest extends TestCase
{
    protected $dateTimeFactoryMock;
    protected $dateTimeMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(
            Years::class,
            ['dateTimeFactory' => $this->dateTimeFactoryMock]
        );
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $this->dateTimeFactoryMock->expects($this->any())->method('create')->willReturn($this->dateTimeMock);

        $this->dateTimeMock->method('gmtDate')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls('2022', '2023');

        $this->dateTimeMock->expects($this->any())->method('timestamp')->willReturn('2023');
        $this->model->toOptionArray();
    }
}

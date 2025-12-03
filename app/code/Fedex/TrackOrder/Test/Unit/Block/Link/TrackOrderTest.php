<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\Test\Unit\Block\Link;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Fedex\TrackOrder\Block\Link\TrackOrder;
use PHPUnit\Framework\TestCase;

class TrackOrderTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $trackOrderMock;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaperMock = $this
        ->getMockBuilder(\Magento\Framework\Escaper::class)
        ->setMethods(['escapeHtml'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->trackOrderMock = $this->objectManager->getObject(
            TrackOrder::class,
            [
                'context' => $this->contextMock,
                '_urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock
            ]
        );
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\TrackOrder\Block\Link\TrackOrder::class,
            '_toHtml',
        );

        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->trackOrderMock);
        $this->assertIsString($expectedResult);
    }
}

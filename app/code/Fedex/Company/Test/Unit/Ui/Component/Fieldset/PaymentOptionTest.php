<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Ui\Component\Fieldset;

use Fedex\Company\Ui\Component\Fieldset\PaymentOption;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Fieldset;
use PHPUnit\Framework\TestCase;

class PaymentOptionTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\UiComponent\ContextInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    protected $paymentOption;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->paymentOption = $this->getMockBuilder(PaymentOption::class)
            ->enableOriginalConstructor()
            ->setMethods(['getName'])
            ->setConstructorArgs(
                [
                    'context' => $this->contextMock,
                    'toggleConfig' => $this->toggleConfigMock,
                ]
            )
            ->getMock();
    }

    /**
     * @test testGetConfigurationWithCompanyPaymentMethod
     */
    public function testGetConfigurationWithCompanyPaymentMethod()
    {
        $testData = [
            'visible' => true,
            'disabled' => false,
        ];

        $this->paymentOption->expects($this->any())
            ->method('getName')
            ->willReturn('company_payment_methods');

        $this->assertEquals($testData, $this->paymentOption->getConfiguration());
    }

    /**
     * @test testGetConfigurationWithFedExPaymentMethod
     */
    public function testGetConfigurationWithFedExPaymentMethod()
    {
        $testData = [
            'visible' => false,
            'disabled' => true,
        ];

        $this->paymentOption->expects($this->any())
            ->method('getName')
            ->willReturn('payment_methods');

        $this->assertEquals($testData, $this->paymentOption->getConfiguration());
    }
}

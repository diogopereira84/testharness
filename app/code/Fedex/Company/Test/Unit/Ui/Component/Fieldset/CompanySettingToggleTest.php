<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Ui\Component\Fieldset;

use Fedex\Company\Ui\Component\Fieldset\CompanySettingToggle;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\TestCase;

class CompanySettingToggleTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\UiComponent\ContextInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $companySettingToggle;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();

        $this->companySettingToggle = $this->getMockBuilder(CompanySettingToggle::class)
            ->enableOriginalConstructor()
            ->setMethods(['getName'])
            ->setConstructorArgs(
                [
                    'context' => $this->contextMock,
                ]
            )
            ->getMock();
    }

    /**
     * @test testGetChildComponents
     */
    public function testGetChildComponents()
    {
        $this->companySettingToggle->expects($this->any())
            ->method('getName')
            ->willReturn('company_logo');

        $this->assertNotNull($this->companySettingToggle->getChildComponents());
    }

    /**
     * @test testGetChildComponents
     */
    public function testGetChildComponentsEmptyArray()
    {
        $this->companySettingToggle->expects($this->any())
            ->method('getName')
            ->willReturn('force_empty_array');

        $this->assertIsArray($this->companySettingToggle->getChildComponents());
    }

    /**
     * @test testGetConfigurationWithtoggleOn
     */
    public function testGetConfigurationWithtoggleOn()
    {
        $this->companySettingToggle->expects($this->any())
            ->method('getName')
            ->willReturn('company_logo');

        $this->assertNotNull($this->companySettingToggle->getConfiguration());
    }

}

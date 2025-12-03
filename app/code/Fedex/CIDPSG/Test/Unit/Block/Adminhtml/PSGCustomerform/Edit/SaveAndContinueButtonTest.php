<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block\Adminhtml\PSGCustomerform\Edit;

use Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit\SaveAndContinueButton;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * SaveAndContinueButtonTest block class
 */
class SaveAndContinueButtonTest extends TestCase
{
    /**
     * @var Context|MockObject $contextMock
     */
    protected $contextMock;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test testImplementsButtonProviderInterface
     *
     * @return void
     */
    public function testImplementsButtonProviderInterface()
    {
        $saveAndContinueButton = new SaveAndContinueButton($this->contextMock);

        $this->assertInstanceOf(ButtonProviderInterface::class, $saveAndContinueButton);
    }

    /**
     * Test getButtonData
     *
     * @return void
     */
    public function testGetButtonData()
    {
        $saveAndContinueButton = new SaveAndContinueButton($this->contextMock);
        $buttonData = $saveAndContinueButton->getButtonData();
        $this->assertIsArray($buttonData);
        $this->assertArrayHasKey('label', $buttonData);
        $this->assertArrayHasKey('class', $buttonData);
        $this->assertArrayHasKey('data_attribute', $buttonData);
        $this->assertArrayHasKey('sort_order', $buttonData);
        $this->assertEquals('Save and Continue Edit', $buttonData['label']);
        $this->assertEquals('save', $buttonData['class']);
        $this->assertEquals(
            [
                'mage-init' => ['button' => ['event' => 'saveAndContinueEdit']],
            ],
            $buttonData['data_attribute']
        );

        $this->assertEquals(80, $buttonData['sort_order']);
    }
}

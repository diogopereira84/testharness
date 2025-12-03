<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block\Adminhtml\PSGCustomerform\Edit;

use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit\SaveButton;
use Magento\Backend\Block\Widget\Context;

/**
 * GenericButtonTest unit test class
 */
class SaveButtonTest extends TestCase
{
    /**
     * @var Context|MockObject
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
        $saveButton = new SaveButton($this->contextMock);

        $this->assertInstanceOf(ButtonProviderInterface::class, $saveButton);
    }
    
    /**
     * Test testImplementsButtonProviderInterface
     *
     * @return void
     */
    public function testGetButtonData()
    {
        $saveButton = new SaveButton($this->contextMock);
        $buttonData = $saveButton->getButtonData();
        $this->assertIsArray($buttonData);
        $this->assertArrayHasKey('label', $buttonData);
        $this->assertArrayHasKey('class', $buttonData);
        $this->assertArrayHasKey('data_attribute', $buttonData);
        $this->assertArrayHasKey('sort_order', $buttonData);
        $this->assertEquals('Save', $buttonData['label']);
        $this->assertEquals('save primary', $buttonData['class']);
        $this->assertEquals(
            [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            $buttonData['data_attribute']
        );
        
        $this->assertEquals(90, $buttonData['sort_order']);
    }
}

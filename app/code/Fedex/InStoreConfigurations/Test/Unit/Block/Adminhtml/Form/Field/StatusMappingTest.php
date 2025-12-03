<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Test\Unit\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Fedex\InStoreConfigurations\Block\Adminhtml\Form\Field\StatusMapping;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusMappingTest extends TestCase
{
    protected $layout;
    protected $select;
    /**
     * @var StatusMapping
     */
    private StatusMapping $statusMapping;

    /**
     * @var Context|MockObject
     */
    private Context $contextMock;

    protected function setUp(): void
    {
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->select  = $this->createMock(Select::class);
        $this->layout->expects($this->any())->method('createBlock')->willReturn($this->select);
        $this->statusMapping = $this->getMockBuilder(StatusMapping::class)
            ->disableOriginalConstructor()->setMethodsExcept(['addColumn', 'getColumns'])
            ->getMock();

        $this->statusMapping->method('getLayout')->willReturn($this->layout);
    }

    public function testPrepareToRender(): void
    {
        $class = new \ReflectionClass($this->statusMapping);
        $method = $class->getMethod('_prepareToRender');
        $method->setAccessible(true);
        $method->invoke($this->statusMapping);
        $this->assertEquals([
            'magento_status', 'mapped_status'
        ], array_keys($this->statusMapping->getColumns()));
    }
}

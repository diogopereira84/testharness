<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Block\Adminhtml\Form\Field;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Template\Context;
use Fedex\OKTA\Block\Adminhtml\Form\Field\Role;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    protected $layout;
    protected $select;
    /**
     * @var Role
     */
    private Role $role;

    /**
     * @var Context|MockObject
     */
    private Context $contextMock;

    protected function setUp(): void
    {
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->select  = $this->createMock(Select::class);
        $this->select->expects($this->any())->method('calcOptionHash')->willReturn('3653429534');
        $this->layout->expects($this->any())->method('createBlock')->willReturn($this->select);
        $this->role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()->setMethodsExcept(['addColumn', 'getColumns', 'getInternalRoleRenderer'])
            ->getMock();

        $this->role->method('getLayout')->willReturn($this->layout);
        $this->role->method('getLayout')->willReturn($this->layout);
    }

    public function testPrepareToRender(): void
    {
        $class = new \ReflectionClass($this->role);
        $method = $class->getMethod('_prepareToRender');
        $method->setAccessible(true);
        $method->invoke($this->role);
        $this->assertEquals([
            'internal_role', 'external_group'
        ], array_keys($this->role->getColumns()));
    }

    public function testPrepareArrayRow(): void
    {
        $class = new \ReflectionClass($this->role);
        $method = $class->getMethod('_prepareArrayRow');
        $method->setAccessible(true);
        $data = new DataObject([
            'internal_role' => 'some data'
        ]);
        $method->invokeArgs($this->role, ['row' => $data]);
        $this->assertEquals([
            'internal_role' => 'some data',
            'option_extra_attrs' =>
                 [
                    'option_3653429534' => 'selected="selected"',
                ],
        ], $data->getData());
    }
}

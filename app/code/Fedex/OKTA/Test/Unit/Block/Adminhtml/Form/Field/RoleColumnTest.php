<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Block\Adminhtml\Form\Field;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Fedex\OKTA\Model\Config\Source\Role;
use Fedex\OKTA\Block\Adminhtml\Form\Field\RoleColumn;
use PHPUnit\Framework\TestCase;

class RoleColumnTest extends TestCase
{
    private Role        $role;
    private Select      $select;
    private Context     $context;
    private RoleColumn  $roleColumn;

    protected function setUp(): void
    {
        $this->select = $this->createMock(Select::class);
        $this->role = $this->createMock(Role::class);
        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())->method('getEscaper')->willReturn(new Escaper());
        $this->roleColumn = new RoleColumn($this->context, $this->role);
    }

    public function testSetInputName(): void
    {
        $name = 'custom name';
        $this->roleColumn->setInputName($name);
        $this->assertEquals($name, $this->roleColumn->getName());
    }

    public function testSetInputId(): void
    {
        $inputId = 'input-Id';
        $this->roleColumn->setInputId($inputId);
        $this->assertEquals($inputId, $this->roleColumn->getId());
    }

    public function testToHtml(): void
    {
        $options = [['value' => '0', 'label' => __('None')]];
        $this->role->expects($this->once())->method('getAllOptions')->willReturn($options);
        $this->roleColumn->setName('test');
        $this->roleColumn->setId('test');
        $this->roleColumn->setTitle('Test');
        $this->roleColumn->setClass('Test');
        $this->assertEquals(
            '<select name="test" id="test" class="Test" title="Test" ><option value="0" >None</option></select>',
            $this->roleColumn->_toHtml()
        );
    }
}
<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Block\Adminhtml\Edit\Tab;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\Block\Widget\Tab;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\CustomerIdProvider;

class UserPermissionsTabTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $helper;

    /**
     * @var MockObject
     */
    private MockObject $toggleConfig;

    /**
     * @var MockObject
     */
    private MockObject $customerIdProviderMock;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->customerIdProviderMock = $this->createMock(CustomerIdProvider::class);
        $this->helper = new ObjectManager($this);
    }

    /**
     * @param string $method
     * @param string $field
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider dataProvider
     */
    public function testGetters($method, $field, $value, $expected)
    {
        /** @var Tab $object */
        $object = $this->helper->getObject(
            Tab::class,
            ['data' => [$field => $value]]
        );
        $this->toggleConfig
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('sgc_b_2256325')
            ->willReturn($value);
        $this->customerIdProviderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);
        $this->assertEquals($expected, $object->{$method}());
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'getTabLabel' => ['getTabLabel', 'label', 'User Permissions', 'User Permissions'],
            'getTabLabel (default)' => ['getTabLabel', 'empty', 'User Permissions', null],
            'getTabTitle' => ['getTabTitle', 'title', 'User Permissions', 'User Permissions'],
            'getTabTitle (default)' => ['getTabTitle', 'empty', 'User Permissions', null],
            'canShowTab' => ['canShowTab', 'can_show', true, true],
            'canShowTab' => ['canShowTab', 'can_show', false, false],
            'canShowTab (default)' => ['canShowTab', 'empty', false, true],
            'isHidden' => ['isHidden', 'is_hidden', false, false],
            'isHidden (default)' => ['isHidden', 'empty', true, false],
            'getTabClass' => ['getTabClass', 'class', 'test classes', 'test classes'],
            'getTabClass (default)' => ['getTabClass', 'empty', 'test classes', null],
            'getTabUrl' => ['getTabUrl', 'url', 'test url', 'test url'],
            'getTabUrl (default)' => ['getTabUrl', 'empty', 'test url', '#']
        ];
    }
}

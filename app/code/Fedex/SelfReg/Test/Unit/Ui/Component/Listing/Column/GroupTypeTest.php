<?php

namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Fedex\SelfReg\Ui\Component\Listing\Column\GroupType;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class GroupTypeTest extends TestCase
{
    /**
     * @var GroupType
     */
    private $groupType;

    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var UiComponentFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfigMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $objectManager = new ObjectManager($this);

        $this->groupType = $objectManager->getObject(
            GroupType::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $this->uiComponentFactoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'components' => [],
                'data' => []
            ]
        );
    }

    public function testPrepareWhenColumnIsEnabled()
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with(GroupType::XML_PATH_USER_GROUP_ORDER_APPROVERS)
            ->willReturn(true);

        $this->groupType->prepare();

        $this->assertArrayNotHasKey('componentDisabled', $this->groupType->getData('config') ?? []);
    }

    public function testPrepareWhenColumnIsDisabled()
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with(GroupType::XML_PATH_USER_GROUP_ORDER_APPROVERS)
            ->willReturn(false);

        $this->groupType->prepare();

        $this->assertTrue($this->groupType->getData('config')['componentDisabled']);
    }

    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['type' => 'order_approval'],
                    ['type' => 'folder_permissions'],
                    ['type' => 'unknown_type'],
                ]
            ]
        ];

        $result = $this->groupType->prepareDataSource($dataSource);

        $this->assertEquals(
            'order_approval',
            $result['data']['items'][0]['type']
        );
        $this->assertEquals(
            'folder_permissions',
            $result['data']['items'][1]['type']
        );
        $this->assertEquals(
            'unknown_type',
            $result['data']['items'][2]['type']
        );
    }
}

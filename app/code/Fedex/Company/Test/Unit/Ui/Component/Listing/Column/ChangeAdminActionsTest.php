<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Api\Data\Test\Unit\Ui\Component\Listing\Column;

use Fedex\Company\Ui\Component\Listing\Column\ChangeAdminActions;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * test for Change Admin Actions
 */
class ChangeAdminActionsTest extends TestCase
{
    /**
     * @var Actions
     */
    protected $component;

    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var ChangeAdminActions
     */
    protected $changeAdminActionsMock;

    /**
     * test setup method
     *
     * @return void
     */
    protected function setup(): void
    {
        $this->context = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManager($this);
        $this->changeAdminActionsMock = $this->objectManagerHelper->getObject(
            ChangeAdminActions::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'request' => $this->requestMock,
            ]
        );
        $this->changeAdminActionsMock->setData('name', 'actions');
    }

    /**
     * test prepare data source method
     *
     * @param array $dataSource
     * @param array $expectedDataSource
     * @dataProvider getPrepareDataSource
     *
     * @return void
     */
    public function testPrepareDataSource($dataSource, $expectedDataSource)
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['adminId'], ['oldAdminId'], ['savedAdminId'])
            ->willReturnOnConsecutiveCalls('3', '4', '4');
        $dataSource = $this->changeAdminActionsMock->prepareDataSource($dataSource);

        $this->assertEquals($expectedDataSource, $dataSource,);
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getPrepareDataSource()
    {
        $makeAdminAction = [
            'makeAdmin' => [
                'label' => __('Make Admin'),
                'type' => 'make-admin',
                'options' => [
                    'gridProvider' => 'change_admin_grid.change_admin_grid_data_source'
                ]
            ]
        ];

        $removeAdminAction = [
            'removeAdmin' => [
                'label' => __('Remove Admin'),
                'type' => 'remove-admin',
                'options' => [
                    'gridProvider' => 'change_admin_grid.change_admin_grid_data_source'
                ]
            ]
        ];

        $makeAdminDisabledAction = [
            'makeAdminDisabled' => [
                'label' => __('Make Admin'),
                'type' => 'make-admin-disabled',
                'disabled' => true,
            ]
        ];

        return [
            [[ 'data' => [] ], ['data' => []]],
            [[ 'data' => [ 'items' => []] ], [ 'data' => [ 'items' => []] ]],
            [
                [
                    'data' => [
                        'items' => [
                            ['entity_id' => '1', 'customer_role' => '0', 'status' => '1'],
                            ['entity_id' => '2', 'customer_role' => '1', 'status' => '1'],
                            ['entity_id' => '3', 'customer_role' => '1', 'status' => '1'],
                            ['entity_id' => '4', 'customer_role' => '0', 'status' => '1'],
                            ['entity_id' => '5', 'customer_role' => '1', 'status' => '0']
                        ]
                    ]
                ],
                [
                    'data' => [
                        'items' => [
                            ['entity_id' => '1', 'customer_role' => '1', 'status' => '1', 'actions' => $makeAdminAction],
                            ['entity_id' => '2', 'customer_role' => '1', 'status' => '1', 'actions' => $makeAdminAction],
                            ['entity_id' => '3', 'customer_role' => '0', 'status' => '1', 'actions' => $removeAdminAction],
                            ['entity_id' => '4', 'customer_role' => '1', 'status' => '1', 'actions' => $makeAdminAction],
                            ['entity_id' => '5', 'customer_role' => '1', 'status' => '0', 'actions' => $makeAdminDisabledAction]
                        ]
                    ]
                ]
            ],
        ];
    }
}

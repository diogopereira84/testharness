<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Fedex\SelfReg\Ui\Component\Listing\Column\ManageUserGroupActions;
use Magento\Customer\Model\GroupFactory;

class ManageUserGroupActionsTest extends TestCase
{
    private $contextMock;
    private $uiComponentFactoryMock;
    private $urlBuilderMock;
    private $groupFactoryMock;
    private $manageUserGroupActions;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->groupFactoryMock = $this->createMock(GroupFactory::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);

        $this->manageUserGroupActions = new ManageUserGroupActions(
            $this->contextMock,
            $this->uiComponentFactoryMock,
            $this->groupFactoryMock,
            $this->urlBuilderMock,
        );
    }

    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['id' => 1, 'group_name' => '1', 'site_url' => '<test_site>'],
                    ['id' => 2, 'group_name' => '1', 'site_url' => '<test_site>'],
                ]
            ]
        ];

        $expectedDataSource = [
            'data' => [
                'items' => [
                    [
                        'id' => 1,
                        'group_name' => '1',
                        'site_url' => '<test_site>',
                        'actions' => [
                            'edit' => [
                                'href' => '#',
                                'label' => __('Edit'),
                                'hidden' => false,
                                'type' => 'edit-group',
                                'id' => 1,
                                'site_url' => '<test_site>'
                            ],
                            'delete' => [
                                'href' => '#',
                                'label' => __('Delete'),
                                'hidden' => false,
                                'type' => 'delete-group',
                                'options' => [
                                    'id' => 1,
                                    'deleteUrl' => 'company/user/delete',
                                    'gridProvider' => 'manage_user_groups_listing.selfreg_users_manageusergroups_listing_data_source',
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 2,
                        'group_name' => '1',
                        'site_url' => '<test_site>',
                        'actions' => [
                            'edit' => [
                                'href' => '#',
                                'label' => __('Edit'),
                                'hidden' => false,
                                'type' => 'edit-group',
                                'id' => 2,
                                'site_url' => '<test_site>'
                            ],
                            'delete' => [
                                'href' => '#',
                                'label' => __('Delete'),
                                'hidden' => false,
                                'type' => 'delete-group',
                                'options' => [
                                    'id' => 2,
                                    'deleteUrl' => 'company/user/delete',
                                    'gridProvider' => 'manage_user_groups_listing.selfreg_users_manageusergroups_listing_data_source',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->urlBuilderMock->method('getUrl')->willReturn('company/user/delete');

        $this->assertEquals($expectedDataSource, $this->manageUserGroupActions->prepareDataSource($dataSource));
    }
}
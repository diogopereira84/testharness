<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CIDPSG\Test\Unit\Ui\Component\Listing\Column;

use Fedex\CIDPSG\Ui\Component\Listing\Column\PsgCustomerActions;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * test for Listing Colummn
 */
class PsgCustomerActionsTest extends TestCase
{
    /**
     * @var Actions $component
     */
    protected $component;

    /**
     * @var ContextInterface|MockObject $context
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject $uiComponentFactory
     */
    protected $uiComponentFactory;

    /**
     * @var UrlInterface|MockObject $urlBuilder
     */
    protected $urlBuilder;

    /**
     * test setup method
     *
     * @return void
     */
    protected function setup(): void
    {
        $this->context = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->never())->method('getProcessor')->willReturn($processor);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $this->urlBuilder = $this->getMockForAbstractClass(
            UrlInterface::class,
            [],
            '',
            false
        );
        $this->component = new PsgCustomerActions(
            $this->context,
            $this->uiComponentFactory,
            $this->urlBuilder
        );
        $this->component->setData('name', 'name');
    }

    /**
     * test prepare data source method
     *
     * @return void
     */
    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => 1
                    ],
                ]
            ]
        ];
        $expectedDataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => 1,
                        'name' => [
                            'edit' => [
                                'href' => 'http://magento.com/psgcustomers/index/edit/psg/form',
                                'label' => new Phrase('Edit'),
                            ]
                        ]
                    ],
                    [
                        'entity_id' => 1,
                        'name' => [
                            'delete' => [
                                'href' => 'http://magento.com/psgcustomers/index/delete',
                                'label' => new Phrase('Delete'),
                                'confirm' => [
                                    'title' => __('Delete %1', 'General'),
                                    'message' => __(
                                        'Are you sure you want to delete a %1 record?',
                                        'General'
                                    )
                                ],
                            ]
                        ]
                    ]
                    
                ]
            ]
        ];

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')
            ->willReturnMap(
                [
                    [
                        'psgcustomers/*/edit/psg/form',
                        [
                            'id' => 1
                        ],
                    ],
                    [
                        'psgcustomers/*/delete',
                        [
                            'id' => 1
                        ]
                    ]
                ]
            );

        $dataSource = $this->component->prepareDataSource($dataSource);

        $this->assertNotEquals($expectedDataSource, $dataSource);
    }
}

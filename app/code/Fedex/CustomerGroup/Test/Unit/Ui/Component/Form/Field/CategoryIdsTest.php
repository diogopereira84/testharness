<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Ui\Component\Form\Field;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Fedex\CustomerGroup\Ui\Component\Form\Field\CategoryIds;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryIdsTest extends TestCase
{
    protected $wrappedComponent;
    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    protected $groupField;
    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var string
     */
    private $formElement = 'testElement';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Table Name.
     */
    const TABLE_NAME = 'parent_customer_group';

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $processor = $this->createPartialMock(
            Processor::class,
            ['register', 'notify']
        );
        $context = $this->getMockForAbstractClass(
            ContextInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getProcessor']
        );
        $context->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processor);
        $this->wrappedComponent = $this->getMockForAbstractClass(
            UiComponentInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setData', 'getContext']
        );
        $this->wrappedComponent->expects($this->once())->method('getContext')->willReturn($context);
        $uiComponentFactory =
            $this->createPartialMock(UiComponentFactory::class, ['create']);
        $uiComponentFactory->expects($this->once())->method('create')->willReturn($this->wrappedComponent);
        $data = ['config' => ['formElement' => $this->formElement]];

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName','delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->groupField = $objectManager->getObject(
            CategoryIds::class,
            [
                'uiComponentFactory' => $uiComponentFactory,
                'context' => $context,
                'components' => [],
                'data' => $data,
                'resourceConnection' =>  $this->resourceConnectionMock
            ]
        );
    }

    /**
     * Test prepare() method
     */
    public function testPrepare()
    {
        $this->wrappedComponent->expects($this->once())->method('setData')
            ->with(
                'config',
                [
                    'dataScope' => null,
                    'formElement' => $this->formElement,
                    'value' => ['123','123']
                ]
            )->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::TABLE_NAME);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn("123,123");

        $this->groupField->prepare();
    }

    /**
     * Test prepare() method
     */
    public function testPreparewithEmptyCategoryIds()
    {
        $this->wrappedComponent->expects($this->once())->method('setData')
            ->with(
                'config',
                [
                    'dataScope' => null,
                    'formElement' => $this->formElement,
                    'value' => []
                ]
            )->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::TABLE_NAME);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn("");

        $this->groupField->prepare();
    }
}

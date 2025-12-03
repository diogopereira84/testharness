<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Ui\Component\Form\ParentGroup;

use Fedex\CustomerGroup\Ui\Component\Form\ParentGroup\Options;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    /**
     * @var Options
     */
    private $optionsBlock;

    /**
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactoryMock;
    /**
     * @var Collection
     */
    protected $collectionMock;
    /**
     * @var DataObject
     */
    protected $dataMock;
    /**
     * @param GroupCollectionFactory $groupCollectionFactoryMock
     */

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->groupCollectionFactoryMock = $this->getMockBuilder(GroupCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsBlock = new Options(
            $this->groupCollectionFactoryMock
        );
    }

    /**
     * test GetAllOptions
     */
    public function testGetAllOptions(): void
    {
        $groupId = 1;
        $groupCode = 'General';

        $groupMock = $this->getMockBuilder(\Magento\Customer\Model\Group::class)
            ->setMethods(['getCustomerGroupId','getCustomerGroupCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $groupMock->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn($groupId);

        $groupMock->expects($this->once())
            ->method('getCustomerGroupCode')
            ->willReturn($groupCode);

        $this->collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$groupMock]);

        $this->groupCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $result = $this->optionsBlock->getAllOptions();

        $expectedResult = [
            [
                'value' => $groupId,
                'label' => $groupCode,
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }
}

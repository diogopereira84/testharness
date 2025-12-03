<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Shipment\Test\Unit\Ui\Component\Listing\Column\Status;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Shipment\Model\ResourceModel\Shipment\CollectionFactory;
use Fedex\Shipment\Ui\Component\Listing\Column\Status\Options;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;

/**
 * Test class for Fedex\Shipment\Ui\Component\Listing\Column\Status\Options
 *
 */
class OptionsTest extends TestCase
{
    /**
     * @var Options|MockObject
     */
    protected $model;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->collectionFactoryMock = $this->createPartialMock(CollectionFactory::class, ['create']);
        $this->model = $objectManager->getObject(
            Options::class,
            [
                'collectionFactory' => $this->collectionFactoryMock
            ]
        );
    }

    /**
     * Test testToOptionArray method.
     */
    public function testToOptionArray()
    {
        $options = ['value' => '1', 'label' => 'New'];
        $expectedOptions = [['value' => '1', 'label' => 'New']];
        $varienObject = new DataObject();
        $varienObject->setData($options);
        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn([$varienObject]);
        $result = $this->model->toOptionArray();
        $this->assertEquals($expectedOptions, $result);
    }
}

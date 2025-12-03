<?php

namespace Fedex\Shipment\Test\Unit\Block\Adminhtml\Shipment;

use Fedex\Shipment\Block\Adminhtml\Shipment\Grid;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Block\Adminhtml\Shipment\Grid
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class GridTest extends TestCase
{
    /**
     * @var objectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var Grid|MockObject
     */
    protected $grid;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->grid = $this->objectManagerHelper->getObject(Grid::class);
    }

    /**
     * Test testGetGridUrl method.
     */
    public function testGetGridUrl()
    {
        $this->assertEquals(null, $this->grid->getGridUrl());
    }

    /**
     * Test testGetRowUrl method.
     */
    public function testGetRowUrl()
    {
        $rowData = ["id" => "2"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($rowData);
        $this->assertEquals(null, $this->grid->getRowUrl($varienObject));
    }
}

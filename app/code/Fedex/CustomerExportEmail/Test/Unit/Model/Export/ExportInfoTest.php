<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Test\Unit\Model\Export;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\CustomerExportEmail\Model\Export\ExportInfo;


class ExportInfoTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var ExportInfo |MockObject */
    protected $exportinfo;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->exportinfo = $this->objectManagerHelper->getObject(
            ExportInfo::class
        );
    }

    /**
     * Test testGetMessage
     */
    public function testGetMessage()
    {
        $this->assertEquals(null, $this->exportinfo->getMessage());
    }

    /**
     * Test testSetMessage
     */
    public function testSetMessage()
    {
        $this->assertEquals(null, $this->exportinfo->setMessage("Test"));
    }

    /**
     * Test getCustomerdata
     */
    public function testGetCustomerdata()
    {
        $this->assertEquals(null, $this->exportinfo->getCustomerdata());
    }

    /**
     * Test setCustomerdata
     */
    public function testSetCustomerdata()
    {
        $this->assertEquals(null, $this->exportinfo->setCustomerdata("Test"));
    }

    /**
     * Test getInActiveColumns
     */
    public function testGetInActiveColumns()
    {
        $this->assertEquals(null, $this->exportinfo->getInActiveColumns());
    }

    /**
     * Test setInActiveColumns
     */
    public function testSetInActiveColumns()
    {
        $this->assertEquals(null, $this->exportinfo->setInActiveColumns("Test"));
    }
}

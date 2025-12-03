<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Ui\Component\Listing\Column\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Import\Ui\Component\Listing\Column\Product\Options;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Model\Website;

class OptionsTest extends TestCase
{
    protected $systemStoreMock;
    protected $websiteMock;
    protected $Mock;
    protected $MockOptions;
    /**
     * Set up method
     */
    public function setUp():void
    {

        $this->systemStoreMock = $this->getMockBuilder(SystemStore::class)
        ->setMethods(['getWebsiteCollection','getGroupCollection','getStoreCollection'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->websiteMock = $this->getMockBuilder(Website::class)
        ->setMethods(['getWebsiteId'])
        ->disableOriginalConstructor()
        ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            Options::class,
            ['systemStore' => $this->systemStoreMock]
        );
        $this->MockOptions = $objectManagerHelper->getObject(
            Options::class,
            ['options' => ['abc','abc']]
        );
    }

    /**
     * Test method for toOptionArray
     *
     * @return void
     */
    public function testtoOptionArray()
    {
        $this->systemStoreMock->expects($this->any())->method('getWebsiteCollection')->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->any())->method('getWebsiteId')->willReturn(1);
        $this->systemStoreMock->expects($this->any())->method('getGroupCollection')->willReturn(['test']);
        $this->systemStoreMock->expects($this->any())->method('getStoreCollection')->willReturn(['test']);
        $this->Mock->toOptionArray();
        $this->MockOptions->toOptionArray();
    }
}

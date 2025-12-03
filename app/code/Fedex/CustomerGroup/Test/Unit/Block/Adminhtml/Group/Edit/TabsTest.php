<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Test\Unit\Block\Adminhtml\Group\Edit;

use Fedex\CustomerGroup\Block\Adminhtml\Group\Edit\Tabs;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TabsTest extends TestCase
{
    /**
     * @var WidgetTabs|MockObject
     */

    protected $widgetTabsMock;

    /**
     * @var ObjectManager|MockObject
     */

    protected $objectManagerHelper;

    /**
     * @var Tabs|MockObject
     */
    protected $tabs;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->widgetTabsMock = $this->getMockBuilder(WidgetTabs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tabs = $this->objectManagerHelper->getObject(
            Tabs::class,
            []
        );
    }
    
    /**
     * Test method for testBeforeToHtml
     *
     * @return void
     */
    public function testBeforeToHtml()
    {
        $this->assertNotNull($this->tabs->_beforeToHtml());
    }
}

<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\Index;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var PageFactory|MockObject */
    protected $pageFactory;

    /** @var Index|MockObject */
    protected $index;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
        ->setMethods(['create','setActiveMenu','addBreadcrumb','getConfig','getTitle','prepend'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->index = $this->objectManagerHelper->getObject(
            Index::class,
            [
                'resultPageFactory' => $this->pageFactory
            ]
        );
    }

    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $this->pageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('setActiveMenu')->willReturn("Fedex_Shipment");
        $this->pageFactory->expects($this->any())->method('addBreadcrumb')->willReturn("Fedex");
        $this->pageFactory->expects($this->any())->method('getConfig')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('getTitle')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('prepend')->willReturn("Manage Shipment Status");
        $this->assertEquals($this->pageFactory, $this->index->execute());
    }
}

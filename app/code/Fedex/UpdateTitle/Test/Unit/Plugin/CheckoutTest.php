<?php
namespace Fedex\UpdateTitle\UnitTest\Test\Unit\Plguin;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Checkout\Controller\Index\Index;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Fedex\UpdateTitle\Plugin\Checkout;

class CheckoutTest extends TestCase {

     protected $pageFactory;
    protected $page;
    protected $config;
    protected $title;
    protected $index;
    protected $block;
    protected $getlayoutInterface;
    protected $checkout;
    /**
     * Set up
     *
     * @return void
     */

    protected function setUp(): void
    {
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
        ->setMethods(['create'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->page = $this->getMockBuilder(Page::class)
        ->setMethods(['getConfig', 'getLayout'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
        ->setMethods(['getTitle'])
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->title = $this->getMockBuilder(Title::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->index = $this->getMockBuilder(Index::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->block = $this->getMockBuilder(BlockInterface::class)
        ->setMethods(['setTitle'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->getlayoutInterface = $this->getMockBuilder(LayoutInterface::class)
        ->setMethods(['getBlock','setTitle'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $objectManger = new ObjectManager($this);
        $this->checkout = $objectManger->getObject(
                Checkout::class,
                [
                    'resultPageFactory' => $this->pageFactory
                ]
            );
    }

    /**
     * Test unitTest function
     */
   
    public function testAfterExecute()
    {
        $this->pageFactory->expects($this->once())->method('create')->willReturn($this->page);

        $this->page->expects($this->once())->method('getConfig')->willReturn($this->config);

        $this->config->expects($this->once())->method('getTitle')->willReturn($this->title);

        $this->title->expects($this->once())->method('set')->willReturn($this->page);

        $this->page->expects($this->once())->method('getLayout')->willReturn($this->getlayoutInterface);

        $this->getlayoutInterface->expects($this->once())->method('getBlock')->willReturn($this->block);

        $this->getlayoutInterface->expects($this->any())->method('setTitle')->willReturn($this->page);

        $this->assertNotNull($this->checkout->afterExecute($this->index, $this->pageFactory));
    }
}

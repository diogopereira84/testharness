<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\CusomizedMegamenu\Test\Unit\Plugin\Block;

use Fedex\CustomizedMegamenu\Plugin\Block\TopMenuBlock;
use Magedelight\Megamenu\Model\MegamenuManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TopMenuBlockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var Layout|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $layoutMock;

    /**
     * @var TopMenuBlock|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $topMenuBlockMock;

    /**
     * @var MegamenuManagement|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $megamenuManagementMock;

    /**
     * @var \Magedelight\Megamenu\Api\Data\MenuItemsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $menuItemsMock;

    /**
     * @var Template|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $templateMock;

    /**
     * @var Magedelight\Megamenu\Block\TopMenu|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subjectMock;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var TopMenuBlock|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $topMenuBlock;

    // @codingStandardsIgnoreEnd

    protected function setUp(): void
    {

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(Layout::class)
            ->setMethods(['setData', 'createBlock', 'setTemplate', 'toHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->topMenuBlockMock = $this->getMockBuilder(TopMenuBlock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->megamenuManagementMock = $this->getMockBuilder(MegamenuManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuItemsMock = $this->getMockBuilder(\Magedelight\Megamenu\Api\Data\MenuItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->templateMock = $this->getMockBuilder(Template::class)
            ->setMethods(['createBlock'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectMock = $this->getMockBuilder(\Magedelight\Megamenu\Block\TopMenu::class)
            ->setMethods(['getLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->topMenuBlock = $this->objectManager->getObject(
            TopMenuBlock::class,
            [
                'context' => $this->contextMock,
                'megamenuManagement' => $this->megamenuManagementMock,
            ]
        );

    }

    /**
     *@test afterSetMegamenu
     *
     * @return  Svoid
     */
    public function testAfterSetMegamenu()
    {
        $result = '';
        $childrenWrapClass = "level0 nav-1 first parent main-parent";

        $this->subjectMock->expects($this->any())->method('getLayout')->willReturn($this->layoutMock);
        $this->layoutMock->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('setData')->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('setTemplate')->willReturnSelf();
        $this->menuItemsMock->expects($this->any())->method('getItemType')->willReturn('category');

        $this->topMenuBlock->afterSetMegamenu($this->subjectMock, $result, $this->menuItemsMock, $childrenWrapClass);
    }

    /**
     *@test afterSetMegamenuItem with megamenu type item
     *
     * @return  Svoid
     */
    public function testAfterSetMegamenuItem()
    {
        $result = '';
        $childrenWrapClass = "level0 nav-1 first parent main-parent";

        $this->subjectMock->expects($this->any())->method('getLayout')->willReturn($this->layoutMock);
        $this->layoutMock->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('setData')->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('setTemplate')->willReturnSelf();
        $this->menuItemsMock->expects($this->any())->method('getItemType')->willReturn('megamenu');

        $this->topMenuBlock->afterSetMegamenu($this->subjectMock, $result, $this->menuItemsMock, $childrenWrapClass);
    }
}

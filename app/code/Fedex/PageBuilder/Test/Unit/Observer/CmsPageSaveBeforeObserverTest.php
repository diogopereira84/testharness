<?php
/**
 * @category    Fedex
 * @package     Fedex_PageBuilder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PageBuilder\Test\Unit\Observer;

use Fedex\PageBuilder\Observer\CmsPageSaveBeforeObserver;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class CmsPageSaveBeforeObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var CmsPageSaveBeforeObserver
     */
    protected $cmsPageSaveBeforeObserver;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var Page|MockObject
     */
    protected $cmsPageMock;

    protected function setUp(): void
    {
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        $this->cmsPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setContent', 'getContent', 'getIdentifier'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->cmsPageSaveBeforeObserver = $this->objectManager->getObject(
            CmsPageSaveBeforeObserver::class,
            []
        );
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function testExecute()
    {
        $this->cmsPageMock->expects($this->once())->method('getData')->willReturn(['content' => true]);
        $this->cmsPageMock->expects($this->atMost(2))->method('getContent')
            ->willReturn('<div id="%identifier%"/>');
        $this->cmsPageMock->expects($this->once())->method('setContent')
            ->with('<div />')->willReturnSelf();

        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->cmsPageMock);

        $this->cmsPageSaveBeforeObserver->execute($this->observerMock);
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function testExecuteNoIdentifier()
    {
        $this->cmsPageMock->expects($this->once())->method('getData')->willReturn(['content' => true]);
        $this->cmsPageMock->expects($this->once())->method('getContent')
            ->willReturn('<div id="identifier"/>');

        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->cmsPageMock);

        $this->cmsPageSaveBeforeObserver->execute($this->observerMock);
    }
}

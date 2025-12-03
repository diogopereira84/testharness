<?php
/**
 * @category    Fedex
 * @package     Fedex_PageBuilder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PageBuilder\Test\Unit\Observer;

use Fedex\PageBuilder\Observer\CmsBlockSaveBeforeObserver;
use Magento\Cms\Model\Block;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class CmsBlockSaveBeforeObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var CmsBlockSaveBeforeObserver
     */
    protected $cmsBlockSaveBeforeObserver;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var Block|MockObject
     */
    protected $cmsBlockMock;

    protected function setUp(): void
    {
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        $this->cmsBlockMock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setContent', 'getContent', 'getIdentifier'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->cmsBlockSaveBeforeObserver = $this->objectManager->getObject(
            CmsBlockSaveBeforeObserver::class,
            []
        );
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function testExecute()
    {
        $this->cmsBlockMock->expects($this->once())->method('getData')->willReturn(['content' => true]);
        $this->cmsBlockMock->expects($this->once())->method('getIdentifier')->willReturn('jump-links');
        $this->cmsBlockMock->expects($this->atMost(2))->method('getContent')
            ->willReturn('<div id="%identifier%"/>');
        $this->cmsBlockMock->expects($this->atMost(2))->method('setContent')
            ->with('<div id="jump-links"/>')->willReturnSelf();

        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->cmsBlockMock);

        $this->cmsBlockSaveBeforeObserver->execute($this->observerMock);
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function testExecuteNoIdentifier()
    {
        $this->cmsBlockMock->expects($this->once())->method('getData')->willReturn(['content' => true]);
        $this->cmsBlockMock->expects($this->once())->method('getContent')
            ->willReturn('<div id="identifier"/>');

        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->cmsBlockMock);

        $this->cmsBlockSaveBeforeObserver->execute($this->observerMock);
    }
}

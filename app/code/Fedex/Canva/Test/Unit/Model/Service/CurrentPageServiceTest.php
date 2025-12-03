<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model\Service;

use Fedex\Canva\Model\Service\CurrentPageService;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrentPageServiceTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected CurrentPageService $currentPageserviceMock;
    protected Page|MockObject $pageMock;
    protected PageRepository|MockObject $pageRepositoryMock;

    protected function setUp(): void
    {
        $this->pageMock = $this
            ->getMockBuilder(Page::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageRepositoryMock = $this
            ->getMockBuilder(PageRepository::class)
            ->onlyMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->currentPageserviceMock = $this->objectManager->getObject(
            CurrentPageService::class,
            [
                'page' => $this->pageMock,
                'pageRepository' => $this->pageRepositoryMock
            ]
        );
    }

    /**
     * @param $pageId
     * @return void
     */
    public function testGetCurrentPage()
    {
        $pageId = 1;

        $this->pageMock->expects($this->once())->method('getId')->willReturn($pageId);
        $this->pageRepositoryMock->expects($this->once())->method('getById')->willReturn($this->pageMock);

        $this->assertInstanceOf(Page::class, $this->currentPageserviceMock->getCurrentPage());
    }

    /**
     * @param $pageId
     * @return void
     */
    public function testGetCurrentPageWillThrowException()
    {
        $pageId = 0;
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The CMS page with the "0" ID doesn\'t exist.');

        $this->pageMock->expects($this->once())->method('getId')->willReturn($pageId);
        $exception = new NoSuchEntityException(__('The CMS page with the "%1" ID doesn\'t exist.', $pageId));
        $this->pageRepositoryMock->expects($this->once())->method('getById')->willThrowException($exception);

        $this->assertSame(null, $this->currentPageserviceMock->getCurrentPage());
    }
}

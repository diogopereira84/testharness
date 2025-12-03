<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Controller\Adminhtml\CmsExport;

use Fedex\CmsImportExport\Controller\Adminhtml\CmsExport\Index;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    protected $pageFactoryMock;
    protected $cmsPageMock;
    protected $pageConfig;
    protected $pageTitle;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var Index
     */
    protected $controller;
    private object $index;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->setMethods(
                [
                    'create'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->cmsPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActiveMenu', 'getConfig', 'getTitle'])
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Controller test
     */
    public function testExecute()
    {
        $this->index = $this->objectManager->getObject(
            Index::class,
            [
                'pageFactory' => $this->pageFactoryMock,
            ]
        );

        $this->pageFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->cmsPageMock);

        $this->cmsPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Fedex_CmsImportExport::cmscontents_export')
            ->willReturnSelf();

        $this->cmsPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->pageConfig);

        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('prepend')->with(__('Cms content export'))
            ->willReturn($this->cmsPageMock);

        $this->assertSame($this->cmsPageMock, $this->index->execute());
    }
}

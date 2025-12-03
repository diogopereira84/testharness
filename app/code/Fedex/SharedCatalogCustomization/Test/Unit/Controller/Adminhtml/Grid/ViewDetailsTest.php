<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Controller\Adminhtml\ViewDetails;

use Fedex\SharedCatalogCustomization\Controller\Adminhtml\Grid\ViewDetails;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewDetailsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var PageFactory|MockObject
     */
    protected $pageFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $pageMock;
    private object $viewdetails;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Controller test
     */
    public function testExecute()
    {
        $this->viewdetails = $this->objectManager->getObject(
            ViewDetails::class,
            [
                'pageFactory' => $this->pageFactoryMock
            ]
        );

        $this->pageFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);

        $this->assertSame($this->pageMock, $this->viewdetails->execute());
    }
}

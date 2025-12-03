<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CategoryLayout\Test\Unit\Plugin\Result;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\View\Element\Context;
use Fedex\CategoryLayout\Plugin\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page as Subject;

/**
 * Test class for Page
 */
class PageTest extends TestCase
{
    protected $pageConfig;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var Context $contextMock
     */
    protected $contextMock;

    /**
     * @var ResponseInterface $responseMock
     */
    protected $responseMock;

    /**
     * @var Subject $subject
     */
    protected $subject;

    /**
     * @var Page $page
     */
    protected $page;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getPageLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getConfig', 'getPageLayout', 'addBodyClass'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->page = $this->objectManagerHelper->getObject(
            Page::class,
            [
                'context' => $this->contextMock,
                'pageConfig' => $this->pageConfig,
            ]
        );
    }

    /**
     * Test beforeRenderResult function
     *
     * @return void
     */
    public function testBeforeRenderResult()
    {
        $this->subject->expects($this->any())
        ->method('getConfig')
        ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
        ->method('getPageLayout')
        ->willReturn("custom-category-full-width");
        $this->subject->expects($this->any())
        ->method('addBodyClass')
        ->willReturnSelf();
        $this->assertIsArray($this->page->beforeRenderResult(
            $this->subject,
            $this->responseMock
        ));
    }
}

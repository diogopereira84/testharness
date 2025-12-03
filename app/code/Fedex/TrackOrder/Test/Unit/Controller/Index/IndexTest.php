<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @author Adithya Adithya <5174169@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Fedex\TrackOrder\Controller\Index\Index;
use Magento\Framework\App\Action\Context;
use Fedex\TrackOrder\Model\Config;

class IndexTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Index
     */
    private $indexController;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->config = $this->createMock(Config::class);
        $this->indexController = new Index($this->context, $this->resultPageFactory, $this->config);
        $configMock = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $titleMock = $this->createMock(\Magento\Framework\View\Page\Title::class);

        $configMock->method('getTitle')->willReturn($titleMock);
        $configMock->method('setDescription')->willReturnSelf();

        $resultPageMock = $this->createMock(\Magento\Framework\View\Result\Page::class);
        $resultPageMock->method('getConfig')->willReturn($configMock);

        $this->resultPageFactory->method('create')->willReturn($resultPageMock);
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $resultPage = $this->indexController->execute();
        $this->assertInstanceOf(\Magento\Framework\View\Result\Page::class, $resultPage);
    }
}

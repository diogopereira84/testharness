<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Pod\Test\Unit\Controller\Index;

use Fedex\Pod\Controller\Index\Index;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testExecute(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock->expects($this->once())->method('create')->willReturn($pageMock);
        $page = new Index($contextMock, $pageFactoryMock);
        $this->assertInstanceOf(Index::class, $page->execute());
    }
}
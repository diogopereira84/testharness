<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Pod\Test\Unit\Controller\Iframe;

use Fedex\Pod\Controller\Iframe\Index;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testExecute(): void
    {
        $pageMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock->expects($this->once())->method('create')->willReturn($pageMock);
        $page = new Index($pageFactoryMock);
        $this->assertInstanceOf(Index::class, $page->execute());
    }
}
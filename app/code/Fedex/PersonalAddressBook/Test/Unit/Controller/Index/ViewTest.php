<?php

declare(strict_types=1);

namespace Fedex\PersonalAddressBook\Test\Unit\Controller\Index;

use Fedex\PersonalAddressBook\Controller\Index\View;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testExecute(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageMock = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageFactoryMock->expects($this->once())->method('create')->willReturn($pageMock);
        $page = new View($contextMock, $pageFactoryMock);
        $this->assertInstanceOf(View::class, $page->execute());
    }
}

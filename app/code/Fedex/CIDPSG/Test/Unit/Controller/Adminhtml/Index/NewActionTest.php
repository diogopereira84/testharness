<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Forward;
use PHPUnit\Framework\TestCase;

/**
 * CIDPSG NewActionTest class
 */
class NewActionTest extends TestCase
{
    /**
     * @var $resultForwardFactoryMock
     */
    protected $resultForwardFactoryMock;

    /**
     * @var $resultForwardMock
     */
    protected $resultForwardMock;

    /**
     * @var $newAction
     */
    protected $newAction;

    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultForwardFactoryMock = $this->createMock(ForwardFactory::class);
        $this->resultForwardMock = $this->createMock(Forward::class);
        $this->newAction = new NewAction($this->resultForwardFactoryMock);
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultForwardFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultForwardMock);

        $this->resultForwardMock->expects($this->once())
            ->method('forward')
            ->with('edit')
            ->willReturn($this->resultForwardMock);

        $result = $this->newAction->execute();

        $this->assertInstanceOf(Forward::class, $result);
    }
}

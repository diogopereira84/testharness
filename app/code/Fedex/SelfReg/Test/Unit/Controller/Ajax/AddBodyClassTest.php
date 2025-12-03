<?php

namespace Fedex\SelfReg\Test\Unit\Controller\Ajax;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\SelfReg\Controller\Ajax\AddBodyClass;

class AddBodyClassTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    /**
     * @var AddBodyClass
     */
    private $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $resultFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogMvpMock;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->catalogMvpMock = $this->createMock(CatalogMvp::class);
        $this->registryMock = $this->createMock(Registry::class);

        $this->resultJsonFactoryMock->method('create')
            ->willReturn($this->resultJsonMock);

        $this->controller = new AddBodyClass(
            $this->contextMock,
            $this->resultFactoryMock,
            $this->createMock(PageFactory::class),
            $this->resultJsonFactoryMock,
            $this->registryMock,
            $this->catalogMvpMock
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $isCustomerAdmin = true;

        // Mock the helper's behavior
        $this->catalogMvpMock->method('isSharedCatalogPermissionEnabled')
            ->willReturn($isCustomerAdmin);

        // Mock the JSON result object
        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['isAdmin' => $isCustomerAdmin])
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertInstanceOf(Json::class, $result);
    }
}

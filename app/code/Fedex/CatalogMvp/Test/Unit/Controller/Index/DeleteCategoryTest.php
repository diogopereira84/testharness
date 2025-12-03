<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\DeleteCategory;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Phrase;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class DeleteCategory
 * Handle the BulkDelete test cases of the CatalogMvp controller
 */
class DeleteCategoryTest extends TestCase
{

    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $requestMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $contextMock;
    protected $jsonFactoryMock;
    protected $helperMock;
    protected $catalogMvp;
    const ID = '1947';

    protected $registry;
    protected $context;
    protected $logger;
    protected $helper;

    protected function setUp(): void
    {
        
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMockForAbstractClass();
        
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'deleteCategory', 'isSharedCatalogPermissionEnabled'])
            ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            DeleteCategory::class,
            [
                
                'registry' => $this->registryMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'context' => $this->contextMock,
                'helper' => $this->helperMock
            ]
        );
    }

    /**
     * @test Execute try case
     */
    public function testExecuteTryCase()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->helperMock->expects($this->any())
        ->method('deleteCategory')->willReturn(true);

        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(static::ID);
    }

}

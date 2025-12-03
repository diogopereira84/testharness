<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\CreateCategory;

use Exception;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

/**
 * Class UpdateProductTest
 * Handle the UpdateProduct test cases of the CatalogMvp controller
 */
class CreateCategoryTest extends TestCase
{

    protected $categoryFactoryMock;
    protected $categoryMock;
    protected $requestMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $contextMock;
    protected $categoryRepositoryInterfaceMock;
    protected $jsonFactoryMock;
    protected $catalogMvp;
    const ID = 123;

    protected $context;
    protected $categoryFactory;
    protected $logger;
    protected $categoryRepositoryInterface;
    protected $catalogMvpConfigInterface;
    protected $etagHelper;

    

    protected function setUp(): void
    {
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setParentId', 'setStoreId', 'setIsActive', 'setCustomAttributes','setEtag'])
            ->getMock();

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

        $this->categoryRepositoryInterfaceMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->catalogMvpConfigInterface = $this->getMockBuilder(CatalogMvpConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isB2371268ToggleEnabled'])
            ->getMockForAbstractClass();

        $this->etagHelper = $this->getMockBuilder(EtagHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateEtag'])
            ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            CreateCategory::class,
            [
                'categoryFactory' => $this->categoryFactoryMock,
                'logger' => $this->loggerMock,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterfaceMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'context' => $this->contextMock,
                'catalogMvpConfigInterface' => $this->catalogMvpConfigInterface,
                'etagHelper' => $this->etagHelper
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCase()
    {
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->prepareRequestMock();

        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('setName')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setParentId')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
        $this->catalogMvpConfigInterface->expects($this->any())->method('isB2371268ToggleEnabled')->willReturn(true);
        $this->etagHelper->expects($this->any())->method('generateEtag')->willReturn('test');
        $this->categoryMock->expects($this->any())->method('setEtag')->willReturn('test');

        $parentCategoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'save'])
            ->getMock();
        $this->categoryRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->with(static::ID)
            ->willReturn($parentCategoryMock);

        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

     /**
     * @test Execute if case
     */
    public function testExecuteCatchCase()
    {
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->prepareRequestMock();

        $phrase = new Phrase(__('Exception message'));

        $e = new \Exception($phrase);

        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())->method('setName')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setParentId')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('save')->willThrowException($e);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }



    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $id = static::ID;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['id', null, static::ID]
                ]
            );
    }
}

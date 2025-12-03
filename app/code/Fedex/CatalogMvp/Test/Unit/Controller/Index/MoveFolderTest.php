<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use Exception;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\MoveFolder;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

/**
 * Class MoveFolderTest
 *
 */
class MoveFolderTest extends TestCase
{
    /**
     * @var (\Magento\Catalog\Model\Category & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryMock;
    protected $requestMock;
    protected $MoveFolder;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var CatalogMvpHelper|MockObject
     */
    protected $catalogMvpHelperMock;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    protected $categoryRepositoryMock;

    protected $catalogMvpConfigInterface;
    protected $etagHelper;

    protected function setUp(): void
    {
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvpHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'isSharedCatalogPermissionEnabled', 'assignCategoryToCategory','getCategoryUrl'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','save','delete','deleteByIdentifier'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
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
        $this->MoveFolder = $objectManagerHelper->getObject(
            MoveFolder::class,
            [
                'logger' => $this->loggerMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'context' => $this->contextMock,
                'request' => $this->requestMock,
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
        $expectedData = ['status' => true, 'message' => 'Folder has been moved.', 'url' => 'test'];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(395, 1690);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignCategoryToCategory')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveFolder->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteCatchCase()
    {
        $expectedData = ['status' => false, 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);
        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(395, 1690);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignCategoryToCategory')->willThrowException($e);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveFolder->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCaseWithElse()
    {
        $expectedData = [];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(395, 1690);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignCategoryToCategory')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertEquals([], $this->MoveFolder->execute());
    }
}

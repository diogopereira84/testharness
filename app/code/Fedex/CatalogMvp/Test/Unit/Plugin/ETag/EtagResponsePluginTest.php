<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin\ETag;

use Fedex\CatalogMvp\Plugin\ETag\EtagResponsePlugin;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Category;

class EtagResponsePluginTest extends TestCase
{
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    /**
     * @var RequestInterface
     */
    private RequestInterface $requestInterface;

    /**
     * @var CatalogMvpConfigInterface
     */
    private CatalogMvpConfigInterface $catalogMvpConfigInterface;

     /**
      * @var LoggerInterface
      */
    private LoggerInterface $loggerInterface;

    /**
     * @var HttpResponse
     */
    private HttpResponse $httpResponse;

    /**
     * @var EtagResponsePlugin
     */
    private EtagResponsePlugin $etagResponsePlugin;

    /**
     * @var Category
     */
    private Category $category;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','getEtag'])
            ->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerName','getActionName','getParam','getHeader'])
            ->getMockForAbstractClass();

        $this->catalogMvpConfigInterface = $this->getMockBuilder(CatalogMvpConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isB2371268ToggleEnabled'])
            ->getMockForAbstractClass();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->httpResponse = $this->getMockBuilder(HttpResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHeader','setHttpResponseCode'])
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEtag'])
            ->getMock();

        $this->etagResponsePlugin = $objectManagerHelper->getObject(
            EtagResponsePlugin::class,
            [
                'categoryRepository' => $this->categoryRepository,
                'request' => $this->requestInterface,
                'catalogMvpConfigInterface' => $this->catalogMvpConfigInterface,
                'logger' => $this->loggerInterface
            ]
        );
    }

    public function testBeforeSendResponseWithToggleDisabled()
    {
        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(false);

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);
        $this->assertSame($this->httpResponse, $result, 'Toggle Disabled');
    }

    public function testBeforeSendResponseWithWrongControllerOrAction()
    {
        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->requestInterface->expects($this->any())
            ->method('getControllerName')
            ->willReturn('otherController');
        $this->requestInterface->expects($this->any())
            ->method('getActionName')
            ->willReturn('otherAction');

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);
        $this->assertSame($this->httpResponse, $result, 'controller or action do not match.');
    }

    public function testBeforeSendResponseWithMissingCategoryId()
    {
        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->requestInterface->expects($this->any())
            ->method('getControllerName')
            ->willReturn('category');
        $this->requestInterface->expects($this->any())
            ->method('getActionName')
            ->willReturn('view');
        $this->requestInterface->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn(null);

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);
        $this->assertSame($this->httpResponse, $result, 'category ID is missing.');
    }

    public function testBeforeSendResponseWithValidCategoryAndEtagMatch()
    {
        $categoryId = 1;
        $etag = '1234567890';

        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->requestInterface->expects($this->any())
            ->method('getControllerName')
            ->willReturn('category');
        $this->requestInterface->expects($this->any())
            ->method('getActionName')
            ->willReturn('view');
        $this->requestInterface->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn($categoryId);

        $this->category->expects($this->any())
            ->method('getEtag')
            ->willReturn($etag);

        $this->categoryRepository->expects($this->any())
            ->method('get')
            ->with($categoryId)
            ->willReturn($this->category);

        $this->requestInterface->expects($this->any())
            ->method('getHeader')
            ->with('If-None-Match')
            ->willReturn('W/"' . $etag . '"');

        $this->httpResponse->expects($this->any())
            ->method('setHttpResponseCode')
            ->with(304);
        $this->httpResponse->expects($this->any())
            ->method('setHeader')
            ->with('Etag', 'W/"' . $etag . '"', true);

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);

        $this->assertSame($this->httpResponse, $result, 'Return 304 with ETag header when ETag matches.');
    }

    public function testBeforeSendResponseWithValidCategoryAndEtagNoMatch()
    {
        $categoryId = 1;
        $etag = '1234567890';

        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->requestInterface->expects($this->any())
            ->method('getControllerName')
            ->willReturn('category');
        $this->requestInterface->expects($this->any())
            ->method('getActionName')
            ->willReturn('view');
        $this->requestInterface->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn($categoryId);

        $this->category->expects($this->any())
            ->method('getEtag')
            ->willReturn($etag);

        $this->categoryRepository->expects($this->any())
            ->method('get')
            ->with($categoryId)
            ->willReturn($this->category);

        $this->requestInterface->expects($this->any())
            ->method('getHeader')
            ->with('If-None-Match')
            ->willReturn('W/"wrong-etag"');

        $this->httpResponse->expects($this->any())
            ->method('setHeader')
            ->with('Etag', 'W/"' . $etag . '"', true);

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);

        $this->assertSame($this->httpResponse, $result, 'set ETag header when there is no match.');
    }

    public function testBeforeSendResponseWithCategoryNotFound()
    {
        $categoryId = 1;

        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->requestInterface->expects($this->any())
            ->method('getControllerName')
            ->willReturn('category');
        $this->requestInterface->expects($this->any())
            ->method('getActionName')
            ->willReturn('view');
        $this->requestInterface->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn($categoryId);

        $this->categoryRepository->expects($this->any())
            ->method('get')
            ->with($categoryId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Category not found')));

        $this->loggerInterface->expects($this->any())
            ->method('error')
            ->with('Category not found for ID: ' . $categoryId . ' - Category not found');

        $result = $this->etagResponsePlugin->beforeSendResponse($this->httpResponse);

        $this->assertSame($this->httpResponse, $result, 'return without modification if category is not found.');
    }
}

<?php
/**
 * @category    Fedex
 * @package     Fedex_CatalogSearch
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types = 1);

namespace Fedex\CatalogSearch\Test\Unit\Block\Product;

use Magento\Framework\UrlFactory;
use Fedex\CatalogSearch\Block\Product\ListProduct;
use Magento\Catalog\Block\Product\Context;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ListProductTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;
    /**
     * @var UrlFactory
     */
    private UrlFactory $urlFactory;

    /**
     * @var ListProduct
     */
    private ListProduct $listProduct;

    /**
     * Test setup
     * @return void
     */
    public function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $postDataHelper = $this->createMock(PostHelper::class);
        $layerResolver = $this->createMock(Resolver::class);
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $urlHelper = $this->createMock(Data::class);

        $this->urlFactory = $this->getMockBuilder(UrlFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['addQueryParams', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMock();    

        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getQueryValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();    
        
        $request->expects($this->once())->method('getQueryValue')->willReturnSelf();

        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->_objectManager = new ObjectManager($this);

        $this->listProduct = $this->_objectManager->getObject(
            ListProduct::class,
            [
                'context' => $context,
                'postDataHelper' => $postDataHelper,
                'layerResolver' => $layerResolver,
                'categoryRepository' => $categoryRepository,
                'urlHelper' => $urlHelper,
                'urlFactory' => $this->urlFactory
            ]
        );
    }

    /**
     * Test getFormUrl function
     * @return void
     */
    public function testGetFormUrl(): void
    {
        $this->urlFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->urlFactory->expects($this->once())->method('addQueryParams')->willReturnSelf();
        $this->urlFactory->expects($this->once())->method('getUrl')->willReturn('URL');

        $result = $this->listProduct->getFormUrl();

        $this->assertEquals("URL", $result);
        $this->assertIsString($result);
    }
}

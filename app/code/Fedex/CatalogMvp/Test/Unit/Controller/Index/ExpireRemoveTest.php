<?php

namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\ExpireRemove;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use \Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ExpireRemoveTest extends TestCase
{
    protected $catalogMvpHelperMock;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Product
     */
    protected $product;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var SessionFactory
     */
    protected $sessionFactory;
    /**
     * @var Collection
     */
    protected $productCollection;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var ExpireRemove
     */
    protected $expireRemove;

    protected $productRepositoryMock;
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'delete', 'getAttributeSetId', 'setStoreId'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['register'])
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelect', 'where', 'getData'])
            ->getMock();
        $this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFromMvpProductCreate'])
            ->getMock();
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttrSetIdByName'])
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','delete'])
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->expireRemove = $objectManagerHelper->getObject(
            ExpireRemove::class,
            [

                'registry' => $this->registry,
                'product' => $this->product,
                'logger' => $this->logger,
                'context' => $this->context,
                'sessionFactory' => $this->sessionFactory,
                'productCollectionFactory' => $this->productCollectionFactory,
                'catalogMvpHelper'=>$this->catalogMvpHelperMock,
                'productRepository'=>$this->productRepositoryMock,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );
    }

    /**
     * @test Execute try block
     */
    public function testExecuteTryCase()
    {
        $this->productCollectionFactory->expects($this->any())->method('create')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('where')->willReturnSelf();
        $this->registry->expects($this->any())->method('register')->willReturnSelf();
        $this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);
        $this->session->expects($this->any())->method('setFromMvpProductCreate')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->once())->method('getAttrSetIdByName')->willReturn(12);
        $this->productCollection->expects($this->any())->method('getData')->willReturn([['entity_id'=>112]]);
        $this->product->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())->method('delete')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->product);
        $this->assertEquals(null, $this->expireRemove->execute());
    }

    /**
     * @test Execute catch block
     */
    public function testExecuteWithException()
    {
        $this->productCollectionFactory->expects($this->any())->method('create')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->registry->expects($this->any())->method('register')->willReturnSelf();
        $this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);
        $this->session->expects($this->any())->method('setFromMvpProductCreate')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->once())->method('getAttrSetIdByName')->willReturn(12);
        $this->productCollection->expects($this->any())->method('getData')->willReturn([['entity_id'=>112]]);
        $this->product->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())->method('delete')->willThrowException(new \Exception());
        $this->assertEquals(null, $this->expireRemove->execute());
    }
}

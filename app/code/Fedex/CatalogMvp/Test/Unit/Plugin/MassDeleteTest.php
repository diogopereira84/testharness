<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Plugin\MassDelete as PluginMassDelete;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\MassDelete as CatalogMassDelete;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class MassDeleteTest extends TestCase
{
    protected $catalogMvpHelper;
    protected $filter;
    protected $collectionFactory;
    protected $collection;
    protected $productRepository;
    protected $productInterface;
    protected $product;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManager;
    protected $resultFactory;
    protected $result;
    protected $subject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    protected function setUp(): void
    {
        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->setMethods(['isMvpCustomerAdminEnable','getIsLegacyItemBySku'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = $this->getMockBuilder(Filter::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(Collection::class)
            ->setMethods(['addMediaGalleryData', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['get', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productInterface = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getExternalProd'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->product = $this->getMockBuilder(Product::class)
            ->setMethods(['getId', 'getSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccessMessage', 'addErrorMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->result = $this->getMockBuilder(Result::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(CatalogMassDelete::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->plugin = $this->objectManager->getObject(
            PluginMassDelete::class,
            [
                'filter' => $this->filter,
                'collectionFactory' => $this->collectionFactory,
                'productRepository' => $this->productRepository,
                'logger' => $this->logger,
                'catalogMvpHelper' => $this->catalogMvpHelper,
                'messageManager' => $this->messageManager,
                'resultFactory' => $this->resultFactory,
            ]
        );

    }
    /*Around plugin with toggle on*/
    public function testAroundExecute()
    {
        $externalPro = '{"id":1508784838900,"version":0,"name":"Legacy Catalog","qty":1,"priceable":true,"proofRequired":false,"catalogReference":{"catalogProductId":"7905e6d0-27f1-4c48-b55e-0b85a16a3878","version":"DOC_20210302_12564198625_1"},"isOutSourced":false,"instanceId":"0"}';
        $proceed = function () {
            $this->subject->execute();
        };
        $this->commonExtenralProd($externalPro);
        $this->catalogMvpHelper->expects($this->any())->method('getIsLegacyItemBySku')->willReturn(false);
       $this->assertEquals($this->result, $this->plugin->aroundExecute($this->subject, $proceed));
    }

    public function testAroundExecuteWithoutExternalPro()
    {
        $externalPro = '';
        $proceed = function () {
            $this->subject->execute();
        };
        $this->commonExtenralProd($externalPro);
        $this->assertEquals($this->result, $this->plugin->aroundExecute($this->subject, $proceed));
    }

    public function testAroundExecuteWithInsatce()
    {
        $externalPro = '{"id":1508784838900,"version":0,"name":"Legacy Catalog","qty":1,"priceable":true,"proofRequired":false,"catalogReference":{"catalogProductId":"7905e6d0-27f1-4c48-b55e-0b85a16a3878","version":"DOC_20210302_12564198625_1"},"isOutSourced":false,"instanceId":"3456"}';
        $proceed = function () {
            $this->subject->execute();
        };
        $this->commonExtenralProd($externalPro);
        
        $this->catalogMvpHelper->expects($this->any())->method('getIsLegacyItemBySku')->willReturn(true);
        $this->assertEquals($this->result, $this->plugin->aroundExecute($this->subject, $proceed));
    }

    public function testAroundExecuteWithException()
    {
        $externalPro = '{"id":1508784838900,"version":0,"name":"Legacy Catalog","qty":1,"priceable":true,"proofRequired":false,"catalogReference":{"catalogProductId":"7905e6d0-27f1-4c48-b55e-0b85a16a3878","version":"DOC_20210302_12564198625_1"},"isOutSourced":false,"instanceId":"3456"}';
        $proceed = function () {
            $this->subject->execute();
        };
        $phrase = new Phrase(__('Exception message'));
        $e = new LocalizedException($phrase);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpCustomerAdminEnable')->willReturn(true);
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collection);
        $this->filter->expects($this->any())->method('getCollection')->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addMediaGalleryData')->willReturnSelf();
        $this->collection->expects($this->any())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->any())->method('getSku')->willThrowException($e);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->result);
        $this->result->expects($this->any())->method('setPath')->willReturnSelf();
        $this->assertEquals($this->result, $this->plugin->aroundExecute($this->subject, $proceed));
    }

    public function commonExtenralProd($externalPro)
    {
        $this->catalogMvpHelper->expects($this->any())->method('isMvpCustomerAdminEnable')->willReturn(true);
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collection);
        $this->filter->expects($this->any())->method('getCollection')->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addMediaGalleryData')->willReturnSelf();
        $this->collection->expects($this->any())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->any())->method('getSku')->willReturn('test-sku');
        $this->productRepository->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getExternalProd')->willReturn($externalPro);
        $this->productRepository->expects($this->any())->method('delete')->willReturn($this->productInterface);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->result);
        $this->result->expects($this->any())->method('setPath')->willReturnSelf();
    }
}

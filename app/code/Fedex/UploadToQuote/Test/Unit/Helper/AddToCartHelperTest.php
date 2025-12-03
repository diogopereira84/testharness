<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\AddToCartHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Checkout\Model\Session as CheckoutSesion;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\Config;

class AddToCartHelperTest extends TestCase
{
    protected $productCollection;
    protected $quote;
    protected $quoteItem;
    protected $negotiableQuote;
    protected $adminConfigHelper;
    protected $addToCartHelperData;
    /**
     * @var CustomerSession $customerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var Product $product
     */
    protected $product;

    /**
     * @var CartRepositoryInterface $cartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var SerializerInterface $serializer
     */
    protected $serializer;

    /**
     * @var ProductCollectionFactory $productCollectionFactory
     */
    protected $productCollectionFactory;

     /**
     * @var CheckoutSesion $checkoutSesion
     */
    protected $checkoutSesion;

    /**
     * @var NegotiableQuoteFactory $negotiableQuoteFactory
     */
    protected $negotiableQuoteFactory;

    /**
     * @var NegotiableQuoteManagement $negotiableQuoteManagement
     */
    protected $negotiableQuoteManagement;

    /**
     * @var customerCartResolver $customerCartResolver
     */
    protected $customerCartResolver;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    protected Config $uploadToQuoteConfig;

    public function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();

        $this->cartRepositoryInterface = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'save'])
            ->getMockForAbstractClass();

        $this->customerCartResolver = $this->getMockBuilder(CustomerCartResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMockForAbstractClass();

        $this->productCollectionFactory = $this->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->setMethods([
                'setStore',
                'addIdFilter',
                'addStoreFilter',
                'getItems',
                'joinAttribute',
                'addAttributeToSelect',
                'addOptionsToResult'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                    'load',
                    'getAllVisibleItems',
                    'addProduct'
                ])
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getOptionByCode', 'getValue', 'getQty', 'getProductId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this->createMock(Product::class);

        $this->checkoutSesion = $this->getMockBuilder(CheckoutSesion::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getId', 'replaceQuote', 'setQuoteId'])
            ->getMock();

        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'setStatus', 'setIsRegularQuote', 'save', 'getStatus'])
            ->getMock();

        $this->negotiableQuoteManagement = $this->getMockBuilder(NegotiableQuoteManagement::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateNegotiableSnapShot'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkoutQuotePriceisDashable', 'updateQuoteStatusByKey', 'deactivateQuote', 'utoqApprovaFixToggle'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->uploadToQuoteConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isTk4674396ToggleEnabled'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->addToCartHelperData = $objectManagerHelper->getObject(
            AddToCartHelper::class,
            [
                'customerSession' => $this->customerSession,
                'cartRepositoryInterface' => $this->cartRepositoryInterface,
                'customerCartResolver' => $this->customerCartResolver,
                'serializer' => $this->serializer,
                'productCollectionFactory' => $this->productCollectionFactory,
                'productCollection' => $this->productCollection,
                'quote' => $this->quote,
                'checkoutSesion' => $this->checkoutSesion,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'negotiableQuote' => $this->negotiableQuote,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'adminConfigHelper' => $this->adminConfigHelper,
                'toggleConfig' => $this->toggleConfig,
                'uploadToQuoteConfig' => $this->uploadToQuoteConfig
            ]
        );
    }

    /**
     * Test AddQuoteItemsToCart
     *
     * @return void
     */
    public function testAddQuoteItemsToCart()
    {
        $storeId = 45;
        $quoteId = 423455;
        $productId = 578;
        $additionalOption = json_encode(['label' => 'fxoProductInstance', 'value' => '57854580254633540']);

        $this->quote->expects($this->any())->method('load')->willReturnSelf();
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getProductId')->willReturn(578);
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getValue')->willReturn($additionalOption);
        $this->uploadToQuoteConfig->expects($this->once())->method('isTk4674396ToggleEnabled')->willReturn(false);
        $this->quote->expects($this->any())->method('addProduct')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(23);
        $this->customerCartResolver->expects($this->once())->method('resolve')->willReturn($this->quote);
        $this->productCollectionFactory->method('create')->willReturn($this->productCollection);
        $this->productCollection->method('getItems')->willReturn([$productId => $this->product]);
        $this->productCollection->method('setStore')->willReturnSelf();
        $this->productCollection->method('addIdFilter')->willReturnSelf();
        $this->productCollection->method('addStoreFilter')->willReturnSelf();
        $this->productCollection->method('joinAttribute')->willReturnSelf();
        $this->productCollection->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->method('addOptionsToResult')->willReturnSelf();
        $this->negotiableQuoteFactory->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->method('load')->willReturnSelf();
        $this->negotiableQuote->method('getStatus')->willReturn('declined');
        $this->checkoutSesion->expects($this->once())->method('getQuote')->willReturnSelf();
        $this->checkoutSesion->expects($this->once())->method('getId')->willReturn(123);
        $this->adminConfigHelper->expects($this->any())->method('checkoutQuotePriceisDashable')->willReturn(1);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->checkoutSesion->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->adminConfigHelper->expects($this->any())->method('utoqApprovaFixToggle')->willReturn(null);

        $this->assertEquals(true, $this->addToCartHelperData->addQuoteItemsToCart($storeId, $quoteId));
    }

    /**
     * Test deactivateQuote
     *
     * @return void
     */
    public function testDeactivateQuote()
    {
        $this->adminConfigHelper->expects($this->once())->method('deactivateQuote')->willReturn(null);

        $this->assertNULL($this->addToCartHelperData->deactivateQuote(834536));
    }
}

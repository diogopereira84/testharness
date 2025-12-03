<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\UploadToQuote\Controller\Index\AddToCart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\Helper\AddToCartHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\App\Request\Http;
use Fedex\FuseBiddingQuote\Helper\RateQuoteHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

class AddToCartTest extends TestCase
{
    /**
     * @var RequestInterface $request
     */
    protected $storeManager;

    /**
     * @var AddToCartHelper $addToCartHelper
     */
    protected $addToCartHelper;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Http $requestMock
     */
    protected $requestMock;

    /**
     * @var QuoteEmailHelper $quoteEmailHelper
     */
    protected $quoteEmailHelper;

    /**
     * @var RateQuoteHelper $rateQuoteHelper
     */
    protected $rateQuoteHelper;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * @var ObjectManagerHelper $objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var AddToCart $addToCartData
     */
    protected $addToCartData;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addToCartHelper = $this->getMockBuilder(AddToCartHelper::class)
            ->setMethods(['addQuoteItemsToCart', 'deactivateQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateQuoteHelper = $this->getMockBuilder(RateQuoteHelper::class)
            ->setMethods(['getRateQuoteDetails'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['load', 'create', 'getIsBid', 'getFjmpQuoteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(['isFuseBidToggleEnabled', 'isRateQuoteDetailApiEnabed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->createMock(Http::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->addToCartData = $this->objectManagerHelper->getObject(
            AddToCart::class,
            [
                'storeManager' => $this->storeManager,
                'addToCartHelper' => $this->addToCartHelper,
                'jsonFactory' => $this->jsonFactory,
                '_request' => $this->requestMock,
                'rateQuoteHelper' => $this->rateQuoteHelper,
                'quoteFactory' => $this->quoteFactory,
                'fuseBidViewModel' => $this->fuseBidViewModel,
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $postData = [
            'quoteId' => 123423
        ];
        $rateQuoteDetailsData = [
            'isApiCallSucceed' => true,
            'message' => 'success'
        ];
        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($postData);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(45);
        $this->addToCartHelper->expects($this->any())->method('addQuoteItemsToCart')->willReturn(true);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('getIsBid')->willReturn(1);
        $this->quoteFactory->expects($this->once())
        ->method('getFjmpQuoteId')->willReturn('FSFDF3453');
        $this->fuseBidViewModel->expects($this->once())->method('isFuseBidToggleEnabled')->willReturn(1);
        $this->fuseBidViewModel->expects($this->once())->method('isRateQuoteDetailApiEnabed')->willReturn(1);
        $this->rateQuoteHelper->expects($this->once())
        ->method('getRateQuoteDetails')->willReturn($rateQuoteDetailsData);

        $this->assertIsObject($this->addToCartData->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $postData = [
            'quoteId' => 123423
        ];

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($postData);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(45);
        $this->addToCartHelper->expects($this->once())->method('deactivateQuote')->willReturnSelf();
        $this->addToCartHelper->expects($this->once())->method('addQuoteItemsToCart')->willThrowException($exception);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('getIsBid')->willReturn(1);

        $this->assertIsObject($this->addToCartData->execute()); 
    }
}

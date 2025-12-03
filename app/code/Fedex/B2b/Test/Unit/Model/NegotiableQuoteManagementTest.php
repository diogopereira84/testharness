<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\B2b\Test\Unit\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Model\Email\Sender;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcess;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Api\Data\ShippingAssignmentInterface;
use Psr\Log\LoggerInterface;

/**
 * Class for managing negotiable quotes.
 */
class NegotiableQuoteManagementTest extends TestCase
{
    protected $quoteRepositoryMock;
    protected $emailSenderMock;
    protected $commentMgmtMock;
    protected $negotiableQuoteItemMgmtMock;
    protected $negotiableQuoteConverterMock;
    protected $quoteUpdaterMock;
    protected $quoteHistoryMock;
    protected $validatorInterfaceFactoryMock;
    protected $validatorMock;
    protected $validatorResultMock;
    protected $sessionManagerInterfaceMock;
    protected $cartDataMock;
    protected $quoteAddressMock;
    protected $cartExtensionInterfaceMock;
    /**
     * @var (\Magento\Framework\Exception\InputException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inputExceptionMock;
    protected $shippingAssignmentMock;
    protected $shippingMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $negotiableQuoteMgmt;
    /**
     * Sample Quote_id
     * @var string
     */
    const QUOTE_ID = 243;
    
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Model\Email\Sender
     */
    private $emailSender;

    /**
     * @var \Magento\NegotiableQuote\Model\CommentManagementInterface
     */
    private $commentManagement;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteConverter
     */
    private $negotiableQuoteConverter;

    /**
     * @var \Magento\NegotiableQuote\Model\QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\History
     */
    private $quoteHistory;

    /**
     * @var \Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory
     */
    private $validatorFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManagerInterface;
    
    /**
     * @var \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface
     */
    protected $negotiableQuoteInterfaceMock;
    
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObj;
    
     /**
      * {@inheritdoc}
      */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
                                        ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();
        
        $this->emailSenderMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Email\Sender::class)
                                    ->setMethods(['sendChangeQuoteEmailToMerchant', 'sendChangeQuoteEmailToBuyer'])
                                    ->disableOriginalConstructor()
                                        ->getMock();
        
        $this->commentMgmtMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\CommentManagementInterface::class)
                                    ->disableOriginalConstructor()
                                        ->setMethods(['update'])
                                            ->getMockForAbstractClass();
        
        $this->negotiableQuoteItemMgmtMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface::class)
                                                ->setMethods(['recalculateOriginalPriceTax','updateQuoteItemsCustomPrices'])
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
        
        $this->negotiableQuoteConverterMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuoteConverter::class)
                                                ->disableOriginalConstructor()
                                                    ->getMock();
        
        $this->quoteUpdaterMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\QuoteUpdater::class)
                                        ->setMethods(['updateCurrentDate', 'updateQuote', 'updateQuoteItemsByCartData'])
                                                ->disableOriginalConstructor()
                                                    ->getMock();
                                                    
        $this->quoteHistoryMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Quote\History::class)
                                                ->disableOriginalConstructor()
                                                ->setMethods(['closeLog', 'collectOldDataFromQuote', 'updateStatusLog', 'checkPricesAndDiscounts',
                                                        'updateLog', 'removeFrontMessage', 'createLog', 'removeAdminMessage'])
                                                    ->getMock();
        
        $this->validatorInterfaceFactoryMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory::class)
                                                ->setMethods(['create'])
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
        
        $this->validatorMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Validator\Validator::class)
                                                ->setMethods(['validate'])
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
                                                    
        $this->validatorResultMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Validator\ValidatorResult::class)
                                    ->setMethods(['hasMessages', 'getMessages'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        
        $this->sessionManagerInterfaceMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
                                                ->setMethods(['start', 'getAdminQuoteView'])
                                                ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
        
        $this->cartDataMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
                                ->setMethods(['getExtensionAttributes', 'collectTotals', 'getGiftCards', 'setGiftCards',
                                                'getCouponCode', 'setCouponCode', 'getAppliedRuleIds', 'getShippingAddress',
                                                    'removeItem'])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();
        
        $this->quoteAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
                                ->setMethods(['setShippingMethod', 'setShippingDescription'])
                                ->disableOriginalConstructor()
                                ->getMock();
        
        $this->negotiableQuoteInterfaceMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getStatus', 'setStatus', 'getIsRegularQuote', 'setIsRegularQuote',
                                                        'setSnapshot', 'setExpirationPeriod', 'setHasUnconfirmedChanges', 'setIsCustomerPriceChanged',
                                                            'setIsShippingTaxChanged', 'getNegotiatedPriceValue', 'getSnapshot',
                                                                'setQuoteId', 'setAppliedRuleIds', 'setQuoteName', 'setNegotiatedPriceType',
                                                                    'setNegotiatedPriceValue', 'setShippingPrice', 'setIsAddressDraft','save'])
                                        ->getMockForAbstractClass();
                                        
        $this->cartExtensionInterfaceMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
                            ->disableOriginalConstructor()
                                ->setMethods(['getNegotiableQuote', 'setNegotiableQuote', 'getShippingAssignments'])
                                    ->getMockForAbstractClass();
                                    
                                    
        $this->inputExceptionMock = $this->getMockBuilder(\Magento\Framework\Exception\InputException::class)
                                    ->setMethods(['addError'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->shippingAssignmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShippingAssignmentInterface::class)
                                    ->setMethods(['getShipping'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();
        
        $this->shippingMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShippingInterface::class)
                                    ->setMethods(['setMethod'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dataObj = $this->getMockBuilder(\Magento\Framework\DataObject::class)
                            ->setMethods(['getIsTaxChanged', 'getIsPriceChanged', 'getIsDiscountChanged', 'getIsChanged'])
                            ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->objectManager = new ObjectManager($this);
        
        $this->negotiableQuoteMgmt = $this->objectManager->getObject(
            NegotiableQuoteManagement::class,
            [
            'quoteRepository' => $this->quoteRepositoryMock,
            'emailSender' => $this->emailSenderMock,
            'commentManagement' => $this->commentMgmtMock,
            'negotiableQuoteConverter' => $this->negotiableQuoteConverterMock,
            'quoteUpdater' => $this->quoteUpdaterMock,
            'quoteHistory' => $this->quoteHistoryMock,
            'validatorFactory' => $this->validatorInterfaceFactoryMock,
            'sessionManagerInterface' => $this->sessionManagerInterfaceMock,
            'logger' => $this->loggerMock
            ]
        );
    }
    
    /**
     * retrieveQuoteMock
     */
    public function retrieveQuoteMock()
    {
        $quoteId = static::QUOTE_ID;
        $this->quoteRepositoryMock->expects($this->any())->method('get')
                        ->with(static::QUOTE_ID, ['*'])->willReturn($this->cartDataMock);
    }
    
    /**
     * retrieveNegotiableQuoteMock
     */
    public function retrieveNegotiableQuoteMock()
    {
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getIsRegularQuote')->willReturn(true);
    }
    
    /**
     * getNegotiableQuoteMock
     */
    public function getNegotiableQuoteMock()
    {
        $this->retrieveQuoteMock();
        $this->retrieveNegotiableQuoteMock();
    }
    
    /**
     * updateSnapshotQuoteMock
     */
    public function updateSnapshotQuoteMock()
    {
        $this->sessionManagerInterfaceMock->expects($this->any())->method('start');
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->quoteRepositoryMock->expects($this->any())->method('save');
    }
    
    /**
     * @test execute
     *
     * @return void
     */
    public function testClose()
    {
        $quoteId = static::QUOTE_ID;
        $force = true;
        
        $this->getNegotiableQuoteMock();
                                        
        $validatorResultFactoryMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['create'])
                                        ->getMock();
        
        $validatorMock = $this->validatorInterfaceFactoryMock->expects($this->any())
        ->method('create')->with(['action' => 'close'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(true);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getStatus')
        ->willReturn(NegotiableQuoteInterface::STATUS_CLOSED);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_CLOSED)->willReturnSelf();
        
        $this->quoteHistoryMock->expects($this->any())->method('closeLog');
        
        $this->updateSnapshotQuoteMock();
        
        $this->assertIsBool(true, $this->negotiableQuoteMgmt->close($quoteId = static::QUOTE_ID, $force = true));
    }

    /**
     * @test execute
     *
     * @return void
     */
    public function testCloseNew()
    {
        $quoteId = static::QUOTE_ID;
        $force = true;
        
        $this->getNegotiableQuoteMock();
                                        
        $validatorResultFactoryMock =
        $this->getMockBuilder(\Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['create'])
                                        ->getMock();
        
        $validatorMock = $this->validatorInterfaceFactoryMock->expects($this->any())
        ->method('create')->with(['action' => 'close'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(true);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getStatus')->willReturn('TEST');
        
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_CLOSED)->willReturnSelf();
        
        $this->quoteHistoryMock->expects($this->any())->method('closeLog');
        
        $this->updateSnapshotQuoteMock();
        $this->assertIsBool(false, $this->negotiableQuoteMgmt->close($quoteId = static::QUOTE_ID, $force = true));
    }
    
    /**
     * saveMock
     */
    private function saveMock($quoteId, array $data, $status = null)
    {
        $this->getNegotiableQuoteMock();
        
        $this->quoteUpdaterMock->expects($this->any())->method('updateQuote')->with(static::QUOTE_ID, []);
        $this->retrieveNegotiableQuoteMock();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setHasUnconfirmedChanges')->with(false);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setIsCustomerPriceChanged')->with(false);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        $this->emailSenderMock->expects($this->any())->method('sendChangeQuoteEmailToBuyer')
        ->with($this->cartDataMock, Sender::XML_PATH_BUYER_QUOTE_UPDATED_BY_SELLER_TEMPLATE);
    }
    
    /**
     * @test adminSend
     */
    public function testAdminSend()
    {
        $files = [];
        $commentText = '';
        $this->getNegotiableQuoteMock();

        $phraseMock = $this->getMockBuilder(\Magento\Framework\Phrase::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
                                        
        $this->validatorInterfaceFactoryMock->expects($this->any())
        ->method('create')->with(['action' => 'send'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock, 'files' => $files])->willReturn($this->validatorResultMock);
        
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(false);
        
        $this->retrieveNegotiableQuoteMock();
        $this->negotiableQuoteInterfaceMock->expects($this->any())
        ->method('setHasUnconfirmedChanges')->with(false)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())
        ->method('setIsCustomerPriceChanged')->with(false)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())
        ->method('setIsShippingTaxChanged')->with(false)->willReturnSelf();
        
        $this->saveMock($quoteId = static::QUOTE_ID, [], NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        $this->commentMgmtMock->expects($this->any())->method('update')
        ->with(static::QUOTE_ID, $commentText, $files);//->willReturn(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER)
        
        $this->quoteHistoryMock->expects($this->any())->method('updateLog');
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getNegotiatedPriceValue')->willReturn(true);
        $this->quoteHistoryMock->expects($this->any())->method('removeFrontMessage');
        
        $this->updateSnapshotQuoteMock();
        $this->assertEquals(true, $this->negotiableQuoteMgmt
        ->adminSend($quoteId = static::QUOTE_ID, $commentText = null, $files = []));
    }
    
    /**
     * @test AdminSendWithInputException
     */
    public function testAdminSendWithInputException()
    {
        $exception = new InputException();
        $files = [];
        $commentText = '';
        $this->getNegotiableQuoteMock();

        $phraseMock = $this->getMockBuilder(\Magento\Framework\Phrase::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
                                        
        $this->validatorInterfaceFactoryMock->expects($this->any())->method('create')
        ->with(['action' => 'send'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock, 'files' => $files])->willReturn($this->validatorResultMock);
        
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(true);
        $this->validatorResultMock->expects($this->any())->method('getMessages')->willReturn([$phraseMock]);

        $expected = $this->expectException(InputException::class);
        $result = $this->negotiableQuoteMgmt->adminSend($quoteId = static::QUOTE_ID, $commentText = null, $files = []);
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @test updateProcessingByCustomerQuoteStatus
     */
    public function testUpdateProcessingByCustomerQuoteStatus()
    {
        $status = 'TEST_STATUS';
        $this->getNegotiableQuoteMock();
        $this->retrieveNegotiableQuoteMock();
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getStatus')->willReturn($status);
                
        $this->validatorInterfaceFactoryMock->expects($this->any())
        ->method('create')->with(['action' => 'edit'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(false);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER)->willReturnSelf();
        $this->quoteHistoryMock->expects($this->any())->method('updateStatusLog');
        
        $this->retrieveQuoteMock();
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getSnapshot');
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setSnapshot');
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        
        $result = $this->negotiableQuoteMgmt->updateProcessingByCustomerQuoteStatus($quoteId = static::QUOTE_ID, true);
        $this->assertEquals($status, $result);
    }
    
    /**
     * @test saveAsDraft
     */
    public function testSaveAsDraft()
    {
        $quoteId = static::QUOTE_ID;
        $quoteData = [];
        $files = [];
        $commentText = null;
        $commentData = ['message' => null];
        
        $this->saveMock($quoteId, $quoteData);
        $this->getNegotiableQuoteMock();
        
        $this->commentMgmtMock->expects($this->any())->method('getFilesNamesList')->willReturn([]);
        $this->commentMgmtMock->expects($this->any())->method('update')
        ->with(static::QUOTE_ID, $commentText, $files, false, true);
        $result = $this->negotiableQuoteMgmt->saveAsDraft($quoteId = static::QUOTE_ID, [], $commentData);
        
        $this->assertInstanceOf(\Fedex\B2b\Model\NegotiableQuoteManagement::class, $result);
    }
    
    /**
     * @test GetSnapshotQuote
     */
    public function testGetSnapshotQuote()
    {
        $this->retrieveQuoteMock();

        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getSnapshot')->willReturn('{}');
        $this->negotiableQuoteConverterMock->expects($this->any())->method('arrayToQuote');
        
        $result = $this->negotiableQuoteMgmt->getSnapshotQuote($quoteId = static::QUOTE_ID);

        $this->assertEquals(null, $result);
    }
    
    /**
     * resetCustomPriceMock
     */
    private function resetCustomPriceMock($quoteMock)
    {
        $quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setNegotiatedPriceType')
        ->with(null)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setNegotiatedPriceValue')
        ->with(null)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setShippingPrice')
        ->with(null)->willReturnSelf();
    }
    
    /**
     * @test decline
     */
    public function testDecline()
    {
        $reason = '';
        $quoteId = static::QUOTE_ID;
        
        $this->getNegotiableQuoteMock();
        
        $this->validatorInterfaceFactoryMock->expects($this->any())->method('create')
        ->with(['action' => 'decline'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(false);
        
        $this->quoteHistoryMock->expects($this->any())->method('collectOldDataFromQuote')
        ->with($this->cartDataMock)->willReturn($this->dataObj);
        
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_DECLINED)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setIsCustomerPriceChanged')
        ->with(false)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setHasUnconfirmedChanges')
        ->with(false)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setIsShippingTaxChanged')
        ->with(false)->willReturnSelf();

        $this->resetCustomPriceMock($this->cartDataMock);
        
        $this->cartDataMock->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->with(null)->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingDescription')->with(null)->willReturnSelf();
        
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getShippingAssignments')
        ->willReturn([$this->shippingAssignmentMock]);
        $this->shippingAssignmentMock->expects($this->any())->method('getShipping')->willReturn($this->shippingMock);
        $this->shippingMock->expects($this->any())->method('setMethod');
        
        $this->negotiableQuoteItemMgmtMock->expects($this->any())->method('recalculateOriginalPriceTax');
        $this->commentMgmtMock->expects($this->any())->method('update')->with($quoteId, $reason, [], true);
        $this->quoteHistoryMock->expects($this->any())->method('updateLog');
        $this->emailSenderMock->expects($this->any())->method('sendChangeQuoteEmailToBuyer')
        ->with($this->cartDataMock, Sender::XML_PATH_BUYER_QUOTE_DECLINED_BY_SELLER_TEMPLATE, $reason);
        
        $this->updateSnapshotQuoteMock();
        $this->quoteHistoryMock->expects($this->any())->method('checkPricesAndDiscounts')
        ->with($this->cartDataMock, $this->dataObj);
        $this->quoteHistoryMock->expects($this->any())->method('removeAdminMessage')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $result = $this->negotiableQuoteMgmt->decline($quoteId, $reason);
        $this->assertEquals(true, $result);
    }
    
    /**
     * @test declineWithInputException
     */
    public function testDeclineWithInputException()
    {
        $reason = '';
        $quoteId = static::QUOTE_ID;
        
        $this->getNegotiableQuoteMock();
        
        $this->validatorInterfaceFactoryMock->expects($this->any())->method('create')
        ->with(['action' => 'decline'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(true);
        
        $phraseMock = $this->getMockBuilder(\Magento\Framework\Phrase::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        
        $this->validatorResultMock->expects($this->any())->method('getMessages')->willReturn([$phraseMock]);
        $expected = $this->expectException(InputException::class);
        
        $result = $this->negotiableQuoteMgmt->decline($quoteId, $reason);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * updateSnapshotQuoteStatusMock
     */
    private function updateSnapshotQuoteStatusMock($quoteId, $status)
    {
        $this->retrieveQuoteMock();
        
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getSnapshot');
        
        $snapshot['negotiable_quote'][NegotiableQuoteInterface::QUOTE_STATUS] = $status;
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setSnapshot')
        ->with(json_encode($snapshot))->willReturnSelf();
    }
    
    /**
     * @test order
     */
    public function testOrder()
    {
        $quoteId = static::QUOTE_ID;
        $status = NegotiableQuoteInterface::STATUS_ORDERED;
        
        $this->getNegotiableQuoteMock();
        
        $this->validatorInterfaceFactoryMock->expects($this->any())->method('create')
        ->with(['action' => 'checkout'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(true);
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_ORDERED)->willReturnSelf();
        
        $this->updateSnapshotQuoteStatusMock($quoteId, $status);
        
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        $this->quoteHistoryMock->expects($this->any())->method('updateLog');
        
        $this->assertEquals(false, $this->negotiableQuoteMgmt->order($quoteId));
    }

    /**
     * @test order
     */
    public function testOrderNew()
    {
        $quoteId = static::QUOTE_ID;
        $status = NegotiableQuoteInterface::STATUS_ORDERED;
        
        $this->getNegotiableQuoteMock();
        
        $this->validatorInterfaceFactoryMock->expects($this->any())->method('create')
        ->with(['action' => 'checkout'])->willReturn($this->validatorMock);
        $this->validatorMock->expects($this->any())->method('validate')
        ->with(['quote' => $this->cartDataMock])->willReturn($this->validatorResultMock);
        $this->validatorResultMock->expects($this->any())->method('hasMessages')->willReturn(false);
        
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_ORDERED)->willReturnSelf();
        
        $this->updateSnapshotQuoteStatusMock($quoteId, $status);
        
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        $this->quoteHistoryMock->expects($this->any())->method('updateLog');
        
        $this->assertEquals(true, $this->negotiableQuoteMgmt->order($quoteId));
    }
    
       /**
        * @test getNegotiableQuoteWithNoSuchEntityException
        */
    public function testGetNegotiableQuoteWithNoSuchEntityException()
    {
        $this->retrieveQuoteMock();
        
        $exception = new NoSuchEntityException();
        $expected = $this->expectException(NoSuchEntityException::class);
        
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getIsRegularQuote')->willReturn(false);
        
        $result = $this->negotiableQuoteMgmt->getNegotiableQuote($quoteId = static::QUOTE_ID);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @test removeNegotiation
     */
    public function testRemoveNegotiation()
    {
        $quoteId = static::QUOTE_ID;
        
        $this->quoteRepositoryMock->expects($this->any())->method('get')
                        ->with(static::QUOTE_ID)->willReturn($this->cartDataMock);
        $this->quoteHistoryMock->expects($this->any())->method('collectOldDataFromQuote')
        ->with($this->cartDataMock)->willReturn($this->dataObj);
        $this->resetCustomPriceMock($this->cartDataMock);
        
        $this->negotiableQuoteItemMgmtMock->expects($this->any())->method('recalculateOriginalPriceTax')
        ->with($quoteId, true, true);
        $this->quoteHistoryMock->expects($this->any())->method('checkPricesAndDiscounts')
        ->with($this->cartDataMock, $this->dataObj);
        $this->quoteHistoryMock->expects($this->any())->method('updateLog');
        
        $this->updateSnapshotQuoteMock();
        $result = $this->negotiableQuoteMgmt->removeNegotiation($quoteId);
        
        $this->assertEquals(null, $result);
    }
    
   /**
    * @test recalculateQuote
    */
    public function testRecalculateQuote()
    {
        $quoteId = static::QUOTE_ID;
        $updatePrice = true;
        
        $this->quoteRepositoryMock->expects($this->any())->method('get')
                        ->with(static::QUOTE_ID)->willReturn($this->cartDataMock);
        $this->quoteHistoryMock->expects($this->any())->method('collectOldDataFromQuote')
        ->with($this->cartDataMock)->willReturn($this->dataObj);
        
        $this->negotiableQuoteItemMgmtMock->expects($this->any())->method('recalculateOriginalPriceTax')
        ->with($quoteId, true, true);
        $this->quoteHistoryMock->expects($this->any())->method('checkPricesAndDiscounts')
        ->with($this->cartDataMock, $this->dataObj)->willReturn($this->dataObj);
        $this->dataObj->expects($this->any())->method('getIsTaxChanged')->willReturn(true);
        $this->dataObj->expects($this->any())->method('getIsPriceChanged')->willReturn(true);
        $this->dataObj->expects($this->any())->method('getIsDiscountChanged')->willReturn(true);
        
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getStatus')->willReturn(' ');
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getNegotiatedPriceValue')->willReturn(' ');
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setIsCustomerPriceChanged')
        ->with(true)->willReturnSelf();
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setIsAddressDraft')
        ->with(false)->willReturnSelf();
        
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        
        $result = $this->negotiableQuoteMgmt->recalculateQuote($quoteId, $updatePrice = true);
        $this->assertEquals(null, $result);
    }
    
    /**
     * @test updateQuoteItems
     */
    public function testUpdateQuoteItems()
    {
        $quoteId = static::QUOTE_ID;
        $cartData = [];
        
        $this->quoteRepositoryMock->expects($this->any())->method('get')
                        ->with(static::QUOTE_ID)->willReturn($this->cartDataMock);
        
        $this->quoteHistoryMock->expects($this->any())->method('collectOldDataFromQuote')
        ->with($this->cartDataMock)->willReturn($this->dataObj);
        $this->quoteUpdaterMock->expects($this->any())->method('updateQuoteItemsByCartData')
        ->with($this->cartDataMock, $cartData);
        
        $this->negotiableQuoteItemMgmtMock->expects($this->any())->method('recalculateOriginalPriceTax')
        ->with($quoteId, true, true);
        $this->quoteHistoryMock->expects($this->any())->method('checkPricesAndDiscounts')
        ->with($this->cartDataMock, $this->dataObj)->willReturn($this->dataObj);
        
        $this->dataObj->expects($this->any())->method('getIsChanged')->willReturn(true);
        
        $this->quoteRepositoryMock->expects($this->any())->method('save');
        
        $result = $this->negotiableQuoteMgmt->updateQuoteItems($quoteId, []);
        $this->assertEquals(null, $result);
    }

    /**
     * @test setHasChangesInNegotiableQuote
     */
    public function testSetHasChangesInNegotiableQuote()
    {
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('getNegotiatedPriceValue')->willReturn(' ');
        $this->negotiableQuoteMgmt->setHasChangesInNegotiableQuote($this->cartDataMock);
    }

    /**
     * updatedSnapshotQuoteMock
     */
    private function updatedSnapshotQuoteMock($quoteId)
    {
        $this->sessionManagerInterfaceMock->expects($this->any())->method('start');
        $this->sessionManagerInterfaceMock->expects($this->any())->method('getAdminQuoteView')->willReturn([]);
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('save');
    }

    /**
     * @test closed
     */
    public function testclosed()
    {
        $quoteId = static::QUOTE_ID;
        $this->getNegotiableQuoteMock();
        $this->cartDataMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())->method('getNegotiableQuote')
        ->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())->method('setStatus')
        ->with(NegotiableQuoteInterface::STATUS_CLOSED)->willReturnSelf();
        
        $this->assertEquals(true, $this->negotiableQuoteMgmt->closed($quoteId));
    }
}

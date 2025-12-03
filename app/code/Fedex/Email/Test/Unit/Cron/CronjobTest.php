<?php

declare(strict_types=1);

namespace Fedex\Email\Test\Unit\Cron;

use Fedex\Email\Cron\Cronjob;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Fedex\Punchout\Helper\Data as PunchoutDataHelper;
use Fedex\Email\Helper\Data as EmailDataHelper;
use Fedex\Email\Helper\SendEmail;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;

class CronjobTest extends TestCase
{
    protected $dataMock;
    protected $cartinterface;
    protected $extensionAttributesMock;
    protected $negotiableQuoteInterface;
    /**
     * @var (\Fedex\Email\Helper\SendEmail & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sendEmail;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $cronjobMock;
    /**
     * @var LoggerInterface $logger
     */
    private $loggerMock;

    /**
     * @var EmailDataHelper
     */
    private $helperMock;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepositoryMock;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var FilterBuilder
     */
    private $filterBuilderMock;

    /**
     * @var TimezoneInterface
     */
    private $localeDateMock;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepositoryMock;

     /**
      * @var AdminConfigHelper
      */
    protected $adminConfigHelperMock;

     /**
      * @var QuoteEmailHelper
      */
    protected $quoteEmailHelperMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->helperMock = $this->getMockBuilder(PunchoutDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataMock = $this->getMockBuilder(EmailDataHelper::class)
            ->setMethods(['OrderExpiredEmail', 'OrderExpiringEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteRepositoryMock = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $methods = ['setField', 'setValue', 'setConditionType', 'create'];
        $this->filterBuilderMock = $this->createPartialMock(FilterBuilder::class, $methods);

        $this->localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->setMethods(['date'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartinterface = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributesMock = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setEmailNotificationStatus','save','GetQuoteName'])
            ->getMockForAbstractClass();

        $this->sendEmail = $this->getMockBuilder(SendEmail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteToggle'])
            ->getMock();

        $this->quoteEmailHelperMock = $this->getMockBuilder(QuoteEmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendQuoteGenericEmail'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->cronjobMock = $this->objectManager->getObject(
            Cronjob::class,
            [
                'logger' => $this->loggerMock,
                'helper' => $this->helperMock,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'filterBuilder' => $this->filterBuilderMock,
                'localeDate' => $this->localeDateMock,
                'quoteRepository' => $this->quoteRepositoryMock,
                'quoteEmailHelper' =>$this->quoteEmailHelperMock,
                'adminConfigHelper' => $this->adminConfigHelperMock
            ]
        );
    }

    /**
     * Test for expiredQuoteNotification method.
     *
     */
    public function testExpiredQuoteNotification()
    {
        $quoteId = 0;
        $allowedStatuses = [
            'processing_by_customer',
            'processing_by_admin',
            'created',
            'submitted_by_customer',
            'submitted_by_admin',
            'expired'
        ];
        $currentDate = date('Y-m-d');
        $ruleId = 0;

        $filterMock = $this->createMock(Filter::class);
        $searchresult = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $extensibledata = $this->getMockForAbstractClass(ExtensibleDataInterface::class);

        $searchCriteria = $this->createMock(SearchCriteria::class);

        $this->filterBuilderMock->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->expects($this->exactly(3))->method('setValue')
            ->withConsecutive([$currentDate], [$allowedStatuses], [0])
            ->willReturnOnConsecutiveCalls($this->returnSelf(), $this->returnSelf(), $this->returnSelf());

        $this->filterBuilderMock->expects($this->any())->method('create')->willReturn($filterMock);
        $this->searchCriteriaBuilderMock->expects($this->any())->method('addFilters')->with([$filterMock]);
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->negotiableQuoteRepositoryMock->expects($this->any())->method('getList')->with($searchCriteria)
            ->willReturn($searchresult);
        $searchresult->expects($this->once())->method('getItems')->willReturn(['1'=> $extensibledata]);
        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturn($this->cartinterface);

        $this->dataMock->expects($this->any())->method('OrderExpiredEmail')->with($this->cartinterface)
            ->willReturnSelf();

        $this->cartinterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $this->extensionAttributesMock->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuoteInterface);
        $this->adminConfigHelperMock->expects($this->any())->method('isUploadToQuoteToggle')->willReturn(true);
        $this->negotiableQuoteInterface->expects($this->any())
                        ->method('getQuoteName')
                        ->willReturnOnConsecutiveCalls(Cronjob::PUNCHOUT_QUOTE_CREATION,
                            Cronjob::UPLOAD_TO_QUOTE_CREATION,Cronjob::FUSE_BIDDING_QUOTE_CREATION);
        $this->negotiableQuoteInterface->expects($this->any())->method('setEmailNotificationStatus')->willReturnSelf();
        $this->negotiableQuoteInterface->expects($this->any())->method('save');
        $this->cronjobMock->expiredQuoteNotification();
    }

    /**
     * Test for QuoteExpiringNotificationn method.
     *
     */
    public function testQuoteExpiringNotification()
    {
        $invalidDate = "0000-00-00";
        $expiringDate = date('Y-m-d', strtotime("+5 days"));

        $allowedStatuses = [
            'processing_by_customer',
            'processing_by_admin',
            'created',
            'submitted_by_customer',
            'submitted_by_admin'
        ];

        $filterMock = $this->createMock(Filter::class);
        $searchresult = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $extensibledata = $this->getMockForAbstractClass(ExtensibleDataInterface::class);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->filterBuilderMock->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())->method('setConditionType')->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(4))->method('setValue')
            ->withConsecutive([$expiringDate], [$invalidDate], [$allowedStatuses], [0])
            ->willReturnOnConsecutiveCalls(
                $this->returnSelf(),
                $this->returnSelf(),
                $this->returnSelf(),
                $this->returnSelf()
            );

        $this->filterBuilderMock->expects($this->any())->method('create')->willReturn($filterMock);
        $this->searchCriteriaBuilderMock->expects($this->any())->method('addFilters')->with([$filterMock]);
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->negotiableQuoteRepositoryMock->expects($this->any())->method('getList')->with($searchCriteria)
            ->willReturn($searchresult);
        $searchresult->expects($this->once())->method('getItems')->willReturn(['1'=>$extensibledata]);
        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturn($this->cartinterface);
        $this->dataMock->expects($this->any())->method('OrderExpiringEmail')->willReturn(1);
        $this->cartinterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->extensionAttributesMock->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuoteInterface);
        $this->adminConfigHelperMock->expects($this->any())->method('isUploadToQuoteToggle')->willReturn(true);
        $this->negotiableQuoteInterface->expects($this->any())
            ->method('getQuoteName')
            ->willReturnOnConsecutiveCalls(Cronjob::PUNCHOUT_QUOTE_CREATION,
                Cronjob::UPLOAD_TO_QUOTE_CREATION,Cronjob::FUSE_BIDDING_QUOTE_CREATION);
        $this->negotiableQuoteInterface->expects($this->any())->method('setEmailNotificationStatus')->willReturnSelf();
        $this->negotiableQuoteInterface->expects($this->any())->method('save');
        $this->cronjobMock->quoteExpiringNotification();
    }
}

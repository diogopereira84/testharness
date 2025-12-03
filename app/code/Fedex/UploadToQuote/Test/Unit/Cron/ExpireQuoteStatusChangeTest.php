<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Email\Test\Unit\Cron;

use Fedex\UploadToQuote\Cron\ExpireQuoteStatusChange;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\Email\Helper\SendEmail;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class ExpireQuoteStatusChangeTest extends TestCase
{
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
     * @var AdminConfigHelper
     */
    private $adminConfigHelperMock;

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
    private $timezoneInterfaceMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $methods = ['setField', 'setValue', 'setConditionType', 'create'];
        $this->filterBuilderMock = $this->createPartialMock(FilterBuilder::class, $methods);

        $this->negotiableQuoteRepositoryMock = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->setMethods(['updateQuoteStatusByKey','quoteexpiryIssueFixToggle'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->timezoneInterfaceMock = $this->getMockBuilder(TimezoneInterface::class)
            ->setMethods(['date','modify','format'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->cronjobMock = $this->objectManager->getObject(
            ExpireQuoteStatusChange::class,
            [
                'logger' => $this->loggerMock,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'filterBuilder' => $this->filterBuilderMock,
                'timezoneInterface' => $this->timezoneInterfaceMock,
                'adminConfigHelper' => $this->adminConfigHelperMock
            ]
        );
    }

    /**
     * Test for expiredQuoteNotification method.
     *
     */
    public function testExecute()
    {
        $allowedStatuses = [
            'processing_by_customer',
            'processing_by_admin',
            'created',
            'submitted_by_customer',
            'submitted_by_admin'
        ];
        $currentDate = date('Y-m-d');
        $this->timezoneInterfaceMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterfaceMock->expects($this->any())->method('modify')->willReturnSelf();
        $this->timezoneInterfaceMock->expects($this->any())->method('format')
            ->willReturn($currentDate);
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
        $this->adminConfigHelperMock->expects($this->once())->method('quoteexpiryIssueFixToggle')->willReturn(1);
        $this->cronjobMock->execute();
    }
}

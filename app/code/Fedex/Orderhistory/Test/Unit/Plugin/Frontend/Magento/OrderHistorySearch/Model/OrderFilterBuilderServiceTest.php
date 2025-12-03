<?php

namespace Fedex\Orderhistory\Test\Unit\Frontend\Magento\OrderHistorySearch\Model;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use DateInterval;
use Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Model\OrderFilterBuilderService;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\OrderHistorySearch\Model\OrderFilterBuilderService as MageFilterBuilderService;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Fedex\Orderhistory\Helper\Data;

class OrderFilterBuilderServiceTest extends \PHPUnit\Framework\TestCase
{
    protected $orderFilterBuilderServiceMock;
    protected $salesOrderCollectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var \Fedex\Orderhistory\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    protected $localeDate;

    /**
     * @var \Magento\OrderHistorySearch\Model\OrderFilterBuilderService $orderFilterBuilderService
     */
    protected $orderFilterBuilderService;
    private \Closure $closureMock;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isModuleEnabled',
                'isRetailOrderHistoryEnabled',
                'isSDEHomepageEnable',
                'isEProHomepageEnable'
            ])->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'convertConfigTimeToUtc'])
            ->getMockForAbstractClass();

        $this->orderFilterBuilderServiceMock = $this->getMockBuilder(MageFilterBuilderService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->salesOrderCollectionMock = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getSelect', 'join', 'columns'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->orderFilterBuilderService = $this->objectManager->getObject(
            OrderFilterBuilderService::class,
            [
                'helper' => $this->helper,
                'localeDate' => $this->localeDate
            ]
        );
    }

    /**
     * The test itself, every test function must start with 'test'
     */
    public function testAroundApplyOrderFilters()
    {
        $subject = $this->orderFilterBuilderServiceMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $params = [
            'sortby' => 'date',
            'orderby' => 'DESC',
            'order-number' => '123456',
            'order-date' => '20/01/2022 - 24/01/2022',
            'invoice-number' => '6789',
            'order-status' => 'shipped;ready_for_pickup;delivered'
        ];
        // B-1145903

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->salesOrderCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('join')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->helper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(true);

        $result = $this->orderFilterBuilderService->aroundApplyOrderFilters(
            $this->orderFilterBuilderServiceMock,
            $this->closureMock,
            $this->salesOrderCollectionMock,
            $params
        );

        $this->assertInstanceOf(MageFilterBuilderService::class, $result);
    }

    public function testAroundApplyOrderFilterswithSDE()
    {
        $subject = $this->orderFilterBuilderServiceMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $params = [
            'sortby' => 'date',
            'orderby' => 'DESC',
            'order-number' => '123456',
            'order-date' => '20/01/2022 - 24/01/2022',
            'invoice-number' => '6789',
            'order-status' => 'shipped;ready_for_pickup;complete'
        ];
        // B-1145903

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->salesOrderCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('join')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->helper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(true);
        $result = $this->orderFilterBuilderService->aroundApplyOrderFilters(
            $this->orderFilterBuilderServiceMock,
            $this->closureMock,
            $this->salesOrderCollectionMock,
            $params
        );

        $this->assertInstanceOf(MageFilterBuilderService::class, $result);
    }

    public function testAroundApplyOrderFilterswithEpro()
    {
        $subject = $this->orderFilterBuilderServiceMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $params = [
            'sortby' => 'date',
            'orderby' => 'DESC',
            'order-number' => '123456',
            'order-date' => '20/01/2022 - 24/01/2022',
            'invoice-number' => '6789',
            'order-status' => 'shipped;ready_for_pickup;complete'
        ];
        // B-1145903

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->salesOrderCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('join')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->helper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(false);
        $this->helper->expects($this->any())->method('isEProHomepageEnable')->willReturn(true);
        $result = $this->orderFilterBuilderService->aroundApplyOrderFilters(
            $this->orderFilterBuilderServiceMock,
            $this->closureMock,
            $this->salesOrderCollectionMock,
            $params
        );

        $this->assertInstanceOf(MageFilterBuilderService::class, $result);
    }

    /**
     * The test itself, every test function must start with 'test'
     */
    public function testAroundApplyOrderFiltersWithRetail()
    {
        $subject = $this->orderFilterBuilderServiceMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $params = [
            'orderby' => 'DESC',
            'order-number' => '123456',
            'order-date' => '20/01/2022 - 24/01/2022',
            'invoice-number' => '6789'
        ];

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->salesOrderCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('join')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->helper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $result = $this->orderFilterBuilderService->aroundApplyOrderFilters(
            $this->orderFilterBuilderServiceMock,
            $this->closureMock,
            $this->salesOrderCollectionMock,
            $params
        );

        $this->assertInstanceOf(MageFilterBuilderService::class, $result);
    }
}

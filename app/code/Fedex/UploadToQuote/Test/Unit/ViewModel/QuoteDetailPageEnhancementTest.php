<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Helper\LocationApiHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\UploadToQuote\ViewModel\QuoteDetailPageEnhancement;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipto\Helper\Data as ShiptoData;

class QuoteDetailPageEnhancementTest extends TestCase
{
    /**
     * @var QuoteDetailPageEnhancement
     */
    private $viewModel;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var CartIntegrationInterface|MockObject
     */
    private $integrationMock;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private $quoteIntegrationMock;

    /**
     * @var RegionFactory|MockObject
     */
    private $regionFactoryMock;

    /**
     * @var AdminConfigHelper|MockObject
     */
    private $adminConfigHelperMock;

    /**
     * @var LocationApiHelper|MockObject
     */
    private $locationApiHelperMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepositoryMock;

     /**
      * @var Region|MockObject
      */
    private $regionMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var ProducingAddressFactory|MockObject
     */
    private $producingAddressFactoryMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $order;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface|MockObject
     */
    private $searchCriteriaInterface;

    /**
     * @var \Fedex\Shipment\Model\ProducingAddress|MockObject
     */
    private $orderProducingAddressMock;

    /**
     * @var ShiptoData|MockObject
     */
    private $shiptoDataMock;

    /**
     * Setup method
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Http::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->quoteIntegrationMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsEproQuote', 'getCreatedByLocationId', 'getQuoteMgntLocationCode'])
            ->getMockForAbstractClass();
        $this->integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->adminConfigHelperMock = $this->createMock(AdminConfigHelper::class);
        $this->locationApiHelperMock = $this->createMock(LocationApiHelper::class);
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create'])
            ->getMock();
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->negotiableQuoteRepositoryMock = $this->createMock(NegotiableQuoteRepositoryInterface::class);
        $this->regionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode','load'])
            ->getMockForAbstractClass();
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->producingAddressFactoryMock = $this->getMockBuilder(ProducingAddressFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getId', 'getBillingAddress', 'getCreatedByLocationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaInterface = $this->createMock(SearchCriteriaInterface::class);
        $this->orderProducingAddressMock = $this->getMockBuilder(ProducingAddress::class)
            ->setMethods(['getId', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shiptoDataMock = $this->getMockBuilder(ShiptoData::class)
            ->setMethods(['getAllLocationsByZip'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->viewModel = new QuoteDetailPageEnhancement(
            $this->requestMock,
            $this->quoteRepositoryMock,
            $this->quoteIntegrationMock,
            $this->regionFactoryMock,
            $this->adminConfigHelperMock,
            $this->locationApiHelperMock,
            $this->searchCriteriaBuilderMock,
            $this->orderRepositoryMock,
            $this->negotiableQuoteRepositoryMock,
            $this->producingAddressFactoryMock,
            $this->shiptoDataMock
        );
    }

    /**
     * Test getQuoteId
     *
     * @return void
     */
    public function testGetQuoteId()
    {
        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn('123');

        $this->assertSame(123, $this->viewModel->getQuoteId());
    }

    /**
     * Test testGetQuoteIdReturnsNull
     *
     * @return void
     */
    public function testGetQuoteIdReturnsNull()
    {
        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(null);

        $this->assertNull($this->viewModel->getQuoteId());
    }

    /**
     * Test when quote ID is null
     */
    public function testGetCreatedByLocationIdReturnsNullWhenQuoteIdIsNull()
    {
        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(null);
        $result = $this->viewModel->getCreatedByLocationId();
        $this->assertNull($result);
    }

    /**
     * Test testGetCreatedByLocationIdHandlesIntegration
     *
     * @return void
     */
    public function testGetCreatedByLocationIdHandlesIntegration()
    {
        $quoteId = 123;
        $locationId = 'LOC123';

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));
        $this->integrationMock
            ->method('getLocationId')
            ->willReturn($locationId);
        $this->quoteIntegrationMock
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willReturn($this->integrationMock);

        $this->assertEquals('LOC123', $this->viewModel->getCreatedByLocationId());
    }

    /**
     * Test when getIntegrationLocationId throws an exception
     */
    public function testGetCreatedByLocationId()
    {
        $quoteId = 123;
        $phrase = new Phrase(__('Exception message'));

        $exception = new LocalizedException($phrase);
        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn('123');
        $this->quoteIntegrationMock
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willThrowException($exception);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteMock
            ->method('getQuoteMgntLocationCode')
            ->willReturn('2261');
        $this->shiptoDataMock
            ->method('getAllLocationsByZip')
            ->willReturn([['locationId' => 'TX']]);

        $billingAddressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $billingAddressMock->method('getRegionId')->willReturn(1);
        $billingAddressMock->method('getCountryId')->willReturn('US');
        $this->quoteMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $this->quoteMock->method('getCreatedByLocationId')->willReturn("TX");
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->method('getCode')->willReturn('CA');
        $this->regionFactoryMock->method('create')->willReturn($this->regionMock);

        $result = $this->viewModel->getCreatedByLocationId();
    }

    /**
      * Test testGetProducingLocationIdReturnsLocationIdForOrderedQuoteIdNull
      *
      * @return void
      */
    public function testGetProducingLocationIdReturnsLocationIdForOrderedQuoteIdNull()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(null);

        $this->assertNull($this->viewModel->getProducingLocationId());
    }

    /**
     * Test testGetProducingLocationIdReturnsLocationIdForOrderedQuote
     *
     * @return void
     */
    public function testGetProducingLocationIdReturnsLocationIdForOrderedQuote()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $negotiableQuoteMock = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $negotiableQuoteMock
            ->method('getStatus')
            ->willReturn('ordered');

        $this->negotiableQuoteRepositoryMock
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuoteMock);

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderRepositoryMock
            ->method('getList')
            ->willReturn(new \Magento\Framework\DataObject(['items' => [$orderMock]]));

        $this->viewModel->getProducingLocationId();
    }

    /**
     * Test testGetResponsibleLocationId
     *
     * @return void
     */
    public function testGetResponsibleLocationId()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $negotiableQuoteMock = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $negotiableQuoteMock
            ->method('getStatus')
            ->willReturn('ordered');

        $this->negotiableQuoteRepositoryMock
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuoteMock);

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('quote_id', $quoteId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($this->searchCriteriaInterface);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderRepositoryMock
            ->method('getList')
            ->with($this->searchCriteriaInterface)
            ->willReturn(new \Magento\Framework\DataObject(['items' => [$orderMock]]));
        $this->order
            ->method('getId')
            ->willReturn(456);
        $this->producingAddressFactoryMock
            ->method('create')
            ->willReturnSelf();
        $this->producingAddressFactoryMock
            ->method('load')
            ->willReturn($this->orderProducingAddressMock);
        $this->orderProducingAddressMock
            ->method('getId')
            ->willReturn(1);
        $this->orderProducingAddressMock
            ->method('getAdditionalData')
            ->willReturn(json_encode(['responsible_location_id' => 'LOC456']));

        $this->assertEquals('LOC456', $this->viewModel->getResponsibleLocationId());
    }

    /**
     * Test testGetResponsibleLocationIdNoQuote
     *
     * @return void
     */
    public function testGetResponsibleLocationIdNoQuote()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn('');

        $this->viewModel->getResponsibleLocationId();
    }

    /**
     * Test testGetResponsibleLocationIdNotOrdered
     *
     * @return void
     */
    public function testGetResponsibleLocationIdNotOrdered()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $negotiableQuoteMock = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $negotiableQuoteMock
            ->method('getStatus')
            ->willReturn('draft');

        $this->negotiableQuoteRepositoryMock
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuoteMock);

        $this->assertEquals(null, $this->viewModel->getResponsibleLocationId());
    }

    /**
     * Test testGetResponsibleLocationIdNotOrdered
     *
     * @return void
     */
    public function testGetResponsibleLocationIdNoOrder()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $negotiableQuoteMock = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $negotiableQuoteMock
            ->method('getStatus')
            ->willReturn('ordered');

        $this->negotiableQuoteRepositoryMock
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuoteMock);

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('quote_id', $quoteId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($this->searchCriteriaInterface);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderRepositoryMock
            ->method('getList')
            ->with($this->searchCriteriaInterface)
            ->willReturn(new \Magento\Framework\DataObject(['items' => []]));
        $this->assertEquals(null, $this->viewModel->getResponsibleLocationId());
    }

    /**
     * Test testGetResponsibleLocationIdAddressNull
     *
     * @return void
     */
    public function testGetResponsibleLocationIdAddressNull()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $negotiableQuoteMock = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $negotiableQuoteMock
            ->method('getStatus')
            ->willReturn('ordered');

        $this->negotiableQuoteRepositoryMock
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuoteMock);

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('quote_id', $quoteId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($this->searchCriteriaInterface);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderRepositoryMock
            ->method('getList')
            ->with($this->searchCriteriaInterface)
            ->willReturn(new \Magento\Framework\DataObject(['items' => [$orderMock]]));
        $this->order
            ->method('getId')
            ->willReturn(456);
        $this->producingAddressFactoryMock
            ->method('create')
            ->willReturnSelf();
        $this->producingAddressFactoryMock
            ->method('load')
            ->willReturn($this->orderProducingAddressMock);
        $this->orderProducingAddressMock
            ->method('getId')
            ->willReturn('');
        $this->assertEquals(null, $this->viewModel->getResponsibleLocationId());
    }

    /**
     * Test testIsEproQuote
     *
     * @return void
     */
    public function testIsEproQuote()
    {
        $quoteId = 123;
        $isEproQuote = true;

        $this->quoteMock
            ->method('getIsEproQuote')
            ->willReturn($isEproQuote);

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);

        $this->assertTrue($this->viewModel->isEproQuote());
    }

     /**
      * Test testIsEproQuote
      *
      * @return void
      */
    public function testIsEproQuoteNull()
    {
        $quoteId = 123;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(null);

        $this->quoteRepositoryMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);

        $this->assertFalse($this->viewModel->isEproQuote());
    }
}

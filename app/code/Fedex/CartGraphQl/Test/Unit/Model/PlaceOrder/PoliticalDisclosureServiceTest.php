<?php
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\PlaceOrder;

use Fedex\CartGraphQl\Model\PlaceOrder\PoliticalDisclosureService;
use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosure;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Fedex\CartGraphQl\Model\PlaceOrder\DTO\PoliticalDisclosureDTOFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Region\RegionData;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PoliticalDisclosureServiceTest extends TestCase
{
    private $orderFactoryMock;
    private $regionDataMock;
    private $disclosureFactoryMock;
    private $disclosureRepositoryMock;
    private $politicalDisclosureConfigMock;
    private $loggerMock;
    private $dtoFactoryMock;
    private $loggerHelperMock;
    private $service;

    protected function setUp(): void
    {
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->regionDataMock = $this->createMock(RegionData::class);
        $this->disclosureFactoryMock = $this->createMock(OrderDisclosureFactory::class);
        $this->disclosureRepositoryMock = $this->createMock(OrderDisclosureRepositoryInterface::class);
        $this->politicalDisclosureConfigMock = $this->createMock(PoliticalDisclosureConfig::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dtoFactoryMock = $this->createMock(PoliticalDisclosureDTOFactory::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);

        $this->service = new PoliticalDisclosureService(
            $this->orderFactoryMock,
            $this->regionDataMock,
            $this->disclosureFactoryMock,
            $this->disclosureRepositoryMock,
            $this->politicalDisclosureConfigMock,
            $this->loggerMock,
            $this->dtoFactoryMock,
            $this->loggerHelperMock
        );
    }

    public function testSetDisclosureDetailsSuccessful(): void
    {
        $order = $this->createConfiguredMock(\Magento\Sales\Model\Order::class, [
            'getId' => 1,
            'getEntityId' => 100
        ]);
        $orderModel = $this->createConfiguredMock(\Magento\Sales\Model\Order::class, [
            'loadByIncrementId' => $order
        ]);
        $this->orderFactoryMock->method('create')->willReturn($orderModel);

        // Config mock
        $this->politicalDisclosureConfigMock
            ->method('getEnabledStates')
            ->willReturn(['CA', 'WA']);

        $regionMock = $this->createMock(\Magento\Directory\Model\Region::class);
        $regionMock->method('getId')->willReturn(55);

        $this->regionDataMock
            ->method('getRegionByCode')
            ->willReturn($regionMock);
        $this->regionDataMock
            ->method('getRegionById')
            ->willReturn($regionMock);

        // Mock disclosure object
        $disclosureMock = $this->createMock(OrderDisclosure::class);
        $this->disclosureRepositoryMock->method('getByOrderId')->willReturn($disclosureMock);
        $this->disclosureRepositoryMock->expects($this->once())->method('save');

        // Mock DTO
        $dtoMock = new DataObject([
            'candidate_pac_ballot_issue' => 'John Doe Campaign',
            'election_date' => '2025-11-05',
        ]);
        $this->dtoFactoryMock->method('create')->willReturn($dtoMock);

        $result = $this->service->setDisclosureDetails([
            'candidate_pac_ballot_issue' => 'John Doe Campaign',
            'election_date' => '2025-11-05',
            'election_state' => 'WA',
            'sponsoring_committee' => 'Friends of John',
            'address_street_lines' => ['123 Main St'],
            'city' => 'Seattle',
            'zipcode' => '98101',
            'state' => 'CA',
            'email' => 'john@example.com',
            'status' => 1
        ], '2020296880177622');

        $this->assertEquals(100, $result);
    }

    public function testSetDisclosureDetailsInvalidStateReturnsFalse(): void
    {
        $order = $this->createConfiguredMock(\Magento\Sales\Model\Order::class, [
            'getId' => 1,
            'getEntityId' => false
        ]);
        $orderModel = $this->createConfiguredMock(\Magento\Sales\Model\Order::class, [
            'loadByIncrementId' => $order
        ]);
        $this->orderFactoryMock->method('create')->willReturn($orderModel);

        // Config mock
        $this->politicalDisclosureConfigMock
            ->method('getEnabledStates')
            ->willReturn(['CA', 'WA']);

        $regionMock = $this->createMock(\Magento\Directory\Model\Region::class);
        $regionMock->method('getId')->willReturn(55);

        $this->regionDataMock
            ->method('getRegionByCode')
            ->willReturn($regionMock);
        $this->regionDataMock
            ->method('getRegionById')
            ->willReturn($regionMock);

        // Mock disclosure object
        $disclosureMock = $this->createMock(OrderDisclosure::class);
        $this->disclosureRepositoryMock->method('getByOrderId')->willReturn($disclosureMock);
        $this->disclosureRepositoryMock->expects($this->once())->method('save');

        // Mock DTO
        $dtoMock = new DataObject([
            'candidate_pac_ballot_issue' => 'John Doe Campaign',
            'election_date' => '2025-11-05',
        ]);
        $this->dtoFactoryMock->method('create')->willReturn($dtoMock);

        $result = $this->service->setDisclosureDetails([
            'candidate_pac_ballot_issue' => 'John Doe Campaign',
            'election_date' => '2025-11-05',
            'election_state' => 'WA',
            'sponsoring_committee' => 'Friends of John',
            'address_street_lines' => ['123 Main St'],
            'city' => 'Seattle',
            'zipcode' => '98101',
            'state' => 'CA',
            'email' => 'john@example.com',
            'status' => 1
        ], '2020296880177622');

        $this->assertFalse((bool)$result);
    }
}

<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasRegistrationService;

class StoreFrontUserIdServiceTest extends TestCase
{
    private Session $customerSessionMock;
    private CustomerRepositoryInterface $customerRepositoryMock;
    private Random $randomMock;
    private LoggerInterface $loggerMock;
    private StoreFrontUserIdService $service;
    private CustomerFactory $customerFactory;
    private CustomerCanvasRegistrationService $customerCanvasRegistrationService;

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->randomMock = $this->createMock(Random::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->customerFactory = $this->createMock(CustomerFactory::class);
        $this->customerCanvasRegistrationService = $this->createMock(CustomerCanvasRegistrationService::class);

        $this->service = new StoreFrontUserIdService(
            $this->customerSessionMock,
            $this->customerRepositoryMock,
            $this->randomMock,
            $this->loggerMock,
            $this->customerFactory,
            $this->customerCanvasRegistrationService
        );
    }

    public function testGetStoreFrontUserIdReturnsRandomUuidForGuest(): void
    {
        $expectedUuid = 'guest-uuid-123';
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);
        $this->randomMock->method('getRandomString')->willReturn($expectedUuid);

        $result = $this->service->getStoreFrontUserId();
        $this->assertSame($expectedUuid, $result);
    }

    public function testGetStoreFrontUserIdReturnsExistingUuidForLoggedInUser(): void
    {
        $expectedUuid = 'existing-uuid-456';

        $customerMock = $this->createMock(CustomerInterface::class);
        $attributeMock = $this->createMock(AttributeInterface::class);

        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(10);

        $this->customerRepositoryMock->method('getById')->willReturn($customerMock);
        $customerMock->method('getCustomAttribute')
            ->with('customer_canvas_uuid')
            ->willReturn($attributeMock);

        $attributeMock->method('getValue')->willReturn($expectedUuid);

        $result = $this->service->getStoreFrontUserId();
        $this->assertSame($expectedUuid, $result);
    }

    public function testGetStoreFrontUserIdGeneratesAndSavesNewUuidWhenNotSet(): void
    {
        $generatedUuid = '';

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getId')->willReturn(20); // ✅ Fix: return ID

        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(20);
        $this->customerRepositoryMock->method('getById')->willReturn($customerMock);

        $customerMock->method('getCustomAttribute')
            ->with('customer_canvas_uuid')
            ->willReturn(null); // No UUID exists

        $this->randomMock->method('getRandomString')->willReturn($generatedUuid);

        // Mock the customer model for saving
        $customerModelMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'setData', 'save'])
            ->getMock();

        $customerModelMock->expects($this->any())
            ->method('load')
            ->with(20)
            ->willReturnSelf();

        $customerModelMock->expects($this->any())
            ->method('setData')
            ->with('customer_canvas_uuid', $generatedUuid)
            ->willReturnSelf();

        $customerModelMock->expects($this->any())
            ->method('save');

        $this->customerFactory->method('create')->willReturn($customerModelMock);

        $result = $this->service->getStoreFrontUserId();
        $this->assertSame($generatedUuid, $result);
    }


    public function testGetStoreFrontUserIdLogsErrorAndReturnsGeneratedUuidOnException(): void
    {
        $fallbackUuid = 'fallback-uuid-999';

        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerId')->willReturn(30);

        $this->customerRepositoryMock->method('getById')
            ->willThrowException(new \Exception('Something went wrong'));

        $this->randomMock->method('getRandomString')->willReturn($fallbackUuid);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error generating storefront user ID'));

        $result = $this->service->getStoreFrontUserId();
        $this->assertSame($fallbackUuid, $result);
    }
}

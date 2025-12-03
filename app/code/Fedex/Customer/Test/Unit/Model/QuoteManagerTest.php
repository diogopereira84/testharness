<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Model\QuoteManager;
use Fedex\Customer\Model\ToggleConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fedex\Customer\Model\QuoteManager
 */
class QuoteManagerTest extends TestCase
{
    private MockObject&CartRepositoryInterface $quoteRepositoryMock;
    private MockObject&CartManagementInterface $quoteManagementMock;
    private MockObject&CustomerRepositoryInterface $customerRepositoryMock;
    private MockObject&StoreManagerInterface $storeManagerMock;
    private MockObject&ToggleConfig $configMock;
    private MockObject&Quote $quoteMock;
    private QuoteManager $quoteManager;

    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->quoteManagementMock = $this->createMock(CartManagementInterface::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->configMock = $this->createMock(ToggleConfig::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['getStoreId', 'setStoreId', 'getIsActive', 'setIsActive'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteManager = new QuoteManager(
            $this->quoteRepositoryMock,
            $this->quoteManagementMock,
            $this->customerRepositoryMock,
            $this->storeManagerMock,
            $this->configMock
        );
    }

    public function testResetCustomerQuoteReturnsEarlyIfConfigIsDisabled(): void
    {
        $this->configMock->expects($this->once())
            ->method('isAdminResetCardUpdateToggleEnabled')
            ->willReturn(false);

        $this->quoteMock->expects($this->never())->method('getIsActive');
        $this->quoteRepositoryMock->expects($this->never())->method('save');
        $this->quoteManagementMock->expects($this->never())->method('createEmptyCartForCustomer');

        $this->quoteManager->resetCustomerQuote($this->quoteMock);
    }

    public function testResetCustomerQuoteReturnsEarlyIfQuoteIsInactive(): void
    {
        $this->configMock->expects($this->once())
            ->method('isAdminResetCardUpdateToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(false);

        $this->quoteRepositoryMock->expects($this->never())->method('save');
        $this->quoteManagementMock->expects($this->never())->method('createEmptyCartForCustomer');

        $this->quoteManager->resetCustomerQuote($this->quoteMock);
    }

    public function testResetCustomerQuoteOnlyDeactivatesQuoteForGuest(): void
    {
        $customerId = 0;

        $this->configMock->expects($this->once())
            ->method('isAdminResetCardUpdateToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->quoteMock->expects($this->once())
            ->method('setIsActive')
            ->with(false);

        $this->quoteRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->quoteMock);

        $this->quoteManagementMock->expects($this->never())
            ->method('createEmptyCartForCustomer');

        $this->quoteManager->resetCustomerQuote($this->quoteMock);
    }

    public function testResetCustomerQuoteDeactivatesOldAndCreatesNewQuoteWithStoreUpdate(): void
    {
        $customerId = 123;
        $targetStoreId = 1;
        $newQuoteInitialStoreId = 0;
        $newQuoteId = 987;

        $newQuoteMock = $this->createMock(Quote::class);

        $this->configMock->expects($this->once())
            ->method('isAdminResetCardUpdateToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())->method('getIsActive')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getCustomerId')->willReturn($customerId);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn($targetStoreId);

        $this->quoteMock->expects($this->once())->method('setIsActive')->with(false);

        $this->quoteManagementMock->expects($this->once())
            ->method('createEmptyCartForCustomer')
            ->with($customerId)
            ->willReturn($newQuoteId);

        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($newQuoteId)
            ->willReturn($newQuoteMock);

        $newQuoteMock->expects($this->once())->method('getStoreId')->willReturn($newQuoteInitialStoreId);
        $newQuoteMock->expects($this->once())->method('setStoreId')->with($targetStoreId);

        $newQuoteMock->expects($this->once())->method('setIsActive')->with(true);

        $this->quoteRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$this->quoteMock], [$newQuoteMock]);

        $this->quoteManager->resetCustomerQuote($this->quoteMock);
    }

    public function testResetCustomerQuoteDoesNotSetStoreIdIfItAlreadyMatches(): void
    {
        $customerId = 123;
        $targetStoreId = 1;
        $newQuoteId = 987;

        $newQuoteMock = $this->createMock(Quote::class);

        $this->configMock->expects($this->once())
            ->method('isAdminResetCardUpdateToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())->method('getIsActive')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getCustomerId')->willReturn($customerId);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn($targetStoreId);

        $this->quoteManagementMock->expects($this->once())
            ->method('createEmptyCartForCustomer')
            ->with($customerId)
            ->willReturn($newQuoteId);

        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($newQuoteId)
            ->willReturn($newQuoteMock);

        $newQuoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($targetStoreId);
        $newQuoteMock->expects($this->never())
            ->method('setStoreId');

        $newQuoteMock->expects($this->once())->method('setIsActive')->with(true);

        $this->quoteRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$this->quoteMock], [$newQuoteMock]);

        $this->quoteManager->resetCustomerQuote($this->quoteMock);
    }
}
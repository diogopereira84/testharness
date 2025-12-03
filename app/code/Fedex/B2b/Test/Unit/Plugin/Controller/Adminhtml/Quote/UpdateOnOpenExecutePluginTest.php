<?php

namespace Fedex\B2b\Test\Unit\Plugin\Controller\Adminhtml\Quote\UpdateOnOpenExecutePluginTest;

use PHPUnit\Framework\TestCase;
use Fedex\B2b\Plugin\Controller\Adminhtml\Quote\UpdateOnOpenExecutePlugin;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\UpdateOnOpen;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;

class UpdateOnOpenExecutePluginTest extends TestCase
{
    private $updateOnOpenExecutePlugin;
    private $updateOnOpenMock;
    private $requestMock;
    private $toggleConfigMock;
    private $loggerMock;
    private $quoteRepositoryMock;
    private $quoteCurrencyMock;
    private $negotiableQuoteManagementMock;
    private $quoteUpdatesInfoMock;

    protected function setUp(): void
    {
        $this->updateOnOpenMock = $this->createMock(UpdateOnOpen::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->quoteCurrencyMock = $this->createMock(Currency::class);
        $this->negotiableQuoteManagementMock = $this->createMock(NegotiableQuoteManagementInterface::class);
        $this->quoteUpdatesInfoMock = $this->createMock(QuoteUpdatesInfo::class);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnMap([
                ['xmen_upload_to_quote', false]
            ]);

        $this->updateOnOpenExecutePlugin = new UpdateOnOpenExecutePlugin(
            $this->requestMock,
            $this->toggleConfigMock,
            $this->loggerMock,
            $this->quoteRepositoryMock,
            $this->quoteCurrencyMock,
            $this->negotiableQuoteManagementMock,
            $this->quoteUpdatesInfoMock

        );

        
    }

    public function testAroundExecuteEnabledFeature()
    {
        $quoteId = 1;
        $this->requestMock->method('getParam')->with('quote_id')->willReturn($quoteId);

        $quoteMock = $this->createMock(Quote::class);
        $extensionAttributesMock = $this->createMock(CartExtensionInterface::class);
        $negotiableQuoteMock = $this->createMock(NegotiableQuoteInterface::class);

        $quoteMock->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $extensionAttributesMock->method('getNegotiableQuote')->willReturn($negotiableQuoteMock);
        $negotiableQuoteMock->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $negotiableQuoteMock->method('getIsCustomerPriceChanged')->willReturn(true);
        $negotiableQuoteMock->method('getNegotiatedPriceValue')->willReturn(null);

        $this->quoteRepositoryMock->method('get')->with($quoteId)->willReturn($quoteMock);

        $callable = function () {
            return ['executed' => true];
        };

        
    }

    public function testAroundExecuteEnabledFeatureAndQuoteNotFound()
    {
        $quoteId = 1;
        $quoteMock = $this->createMock(Quote::class);
        $this->requestMock->method('getParam')->with('quote_id')->willReturn($quoteId);

        $this->quoteRepositoryMock->method('get')->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $callable = function () {
            return ['executed' => true];
        };

    }

    public function testAroundExecuteFeatureDisabled()
    {
    
        $quoteId = 5;
        $this->requestMock->method('getParam')->with('quote_id')->willReturn($quoteId);
    
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $extensionAttributesMock = $this->createMock(\Magento\Quote\Api\Data\CartExtensionInterface::class);
    
        $quoteMock->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $extensionAttributesMock->method('getNegotiableQuote')->willReturn(null);
    
        $this->quoteRepositoryMock->method('get')->with($quoteId)->willReturn($quoteMock);
    
        $callable = function () {
            return ['executed' => true];
        };
    
    }
    
}

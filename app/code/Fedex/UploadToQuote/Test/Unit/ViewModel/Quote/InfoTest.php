<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\ViewModel\Quote;

use Fedex\UploadToQuote\ViewModel\Quote\Info;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Company\DetailsProviderFactory;
use Magento\NegotiableQuote\Model\Company\DetailsProvider;
use Magento\NegotiableQuote\Model\Creator;
use Magento\Quote\Model\Quote as MagentoQuote;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use PHPUnit\Framework\TestCase;
use Magento\NegotiableQuote\Model\NegotiableQuote;

class InfoTest extends TestCase
{
    private $detailsProviderFactory;
    private $quoteHelper;
    private $creator;
    private $viewModel;
    private $detailsProviderMock;
    private $quoteMock;
    private $negotiableQuoteMock;
    private $extensionAttributesMock;

    protected function setUp(): void
    {
        $this->detailsProviderFactory = $this->createMock(DetailsProviderFactory::class);
        $this->quoteHelper = $this->createMock(Quote::class);
        $this->creator = $this->createMock(Creator::class);

        $this->detailsProviderMock = $this->createMock(DetailsProvider::class);
        $this->quoteMock = $this->createMock(MagentoQuote::class);
        $this->negotiableQuoteMock = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatorId', 'getCreatorType', 'getQuoteId'])
            ->getMockForAbstractClass();
        $this->extensionAttributesMock = $this->createMock(CartExtensionInterface::class);

        $objectManager = new ObjectManager($this);
        $this->viewModel = $objectManager->getObject(
            Info::class,
            [
                'companyDetailsProviderFactory' => $this->detailsProviderFactory,
                'quoteHelper' => $this->quoteHelper,
                'creator' => $this->creator
            ]
        );
    }

    public function testGetQuoteCreatedByCustomer()
    {
        $customerName = 'John Doe';
        $creatorName = 'Admin User';
        $expectedResult = __('%creator for %customer', ['creator' => $creatorName, 'customer' => $customerName]);

        $this->quoteHelper->method('resolveCurrentQuote')->willReturn($this->quoteMock);
        $this->detailsProviderFactory->method('create')->willReturn($this->detailsProviderMock);
        $this->detailsProviderMock->method('getQuoteOwnerName')->willReturn($customerName);

        $this->quoteMock->method('getExtensionAttributes')->willReturn($this->extensionAttributesMock);
        $this->extensionAttributesMock->method('getNegotiableQuote')->willReturn($this->negotiableQuoteMock);

        $this->negotiableQuoteMock->method('getCreatorId')->willReturn(5);
        $this->negotiableQuoteMock->method('getCreatorType')->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->negotiableQuoteMock->method('getQuoteId')->willReturn(100);

        $this->creator->method('retrieveCreatorName')
            ->with(UserContextInterface::USER_TYPE_ADMIN, 5, 100)
            ->willReturn($creatorName);

        $result = $this->viewModel->getQuoteCreatedBy();
        $this->assertEquals($expectedResult, $result);
    }
}

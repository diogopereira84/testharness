<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
declare(strict_types=1);
 
namespace Fedex\UploadToQuote\Test\Unit\ViewModel;
 
use Fedex\UploadToQuote\ViewModel\Info;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use PHPUnit\Framework\TestCase;
use Magento\Authorization\Model\UserContextInterface;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Framework\Phrase;
use Magento\NegotiableQuote\Model\Company\DetailsProviderFactory;
use Magento\NegotiableQuote\Model\Company\DetailsProvider;
use Magento\NegotiableQuote\Model\Creator;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
 
class InfoTest extends TestCase
{
    /**
     * @var AdminConfigHelper|\PHPUnit\Framework\MockObject\MockObject Mock object for AdminConfigHelper used in unit tests.
     */
    private AdminConfigHelper|\PHPUnit\Framework\MockObject\MockObject $adminConfigHelperMock;
    /**
     * @var CartInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object representing the CartInterface for unit testing purposes.
     */
    private CartInterface|\PHPUnit\Framework\MockObject\MockObject $quote;
    /**
     * @var Info Instance of the Info ViewModel used for testing.
     */
    private Info $viewModel;
    /**
     * @var DetailsProviderFactory Instance of the DetailsProviderFactory used to provide company details.
     */
    private DetailsProviderFactory $companyDetailsProviderFactory;
    /**
     * @var Creator Instance of the Creator class used for creating objects or data within the test.
     */
    private Creator $creator;
    /**
     * @var Quote Helper instance for managing quote-related operations in tests.
     */
    private Quote $quoteHelper;
    /**
     * @var DetailsProvider Provides company details for use within the ViewModel.
     */
    private DetailsProvider $companyDetailsProvider;
    /**
     * Mock object for the NegotiableQuote class used in unit tests.
     *
     * @var NegotiableQuote
     */
    private NegotiableQuote $negotiableQuoteMock;
 
    /**
     * Sets up the environment before each test.
     *
     * This method is called before each test is executed. It is typically used to initialize
     * objects, mock dependencies, or configure the environment required for the tests in this class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->adminConfigHelperMock = $this->createMock(AdminConfigHelper::class);
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->quoteHelper = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolveCurrentQuote'])
            ->getMockForAbstractClass();
        $this->companyDetailsProviderFactory = $this->getMockBuilder(DetailsProviderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->companyDetailsProvider = $this->getMockBuilder(DetailsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteOwnerName'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatorId', 'getCreatorType', 'getQuoteId'])
            ->getMockForAbstractClass();
        $this->creator = $this->createMock(Creator::class);
        $this->viewModel = new Info(
            $this->adminConfigHelperMock,
            $this->quoteHelper,
            $this->companyDetailsProviderFactory,
            $this->creator
        );
    }
 
    /**
     * Tests the toggle functionality for the UploadToQuote submit date.
     *
     * This test verifies that the submit date for the UploadToQuote feature
     * can be toggled correctly, ensuring the expected behavior in the ViewModel.
     *
     * @return void
     */
    public function testToggleUploadToQuoteSubmitDate(): void
    {
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('toggleUploadToQuoteSubmitDate')
            ->willReturn(true);
 
        $result = $this->viewModel->toggleUploadToQuoteSubmitDate();
        $this->assertTrue($result);
    }
 
    /**
     * Tests the toggle functionality for the Admin Quote Issue For Fuse Bidding.
     *
     * @return void
     */
 
    public function testisMazegeeksD234006Adminquoteissueforfusebidding(): void
    {
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('isMazegeeksD234006Adminquoteissueforfusebidding')
            ->willReturn(true);
 
        $result = $this->viewModel->isMazegeeksD234006Adminquoteissueforfusebidding();
        $this->assertTrue($result);
    }
 
    /**
     * Tests the Get Submit Date.
     *
     * @return void
     */
 
    public function testGetSubmitDate(): void
    {
        $quoteId = 123;
        $expectedDate = '2025-08-28';
 
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getSubmitDate')
            ->with($quoteId)
            ->willReturn($expectedDate);
 
        $result = $this->viewModel->getSubmitDate($quoteId);
        $this->assertSame($expectedDate, $result);
    }
 
    /**
     * Tests the getQuoteCreatedBy method to ensure it returns the correct creator information for a quote.
     *
     * @return void
     */
    public function testGetQuoteCreatedBy(): void
    {
        $expectedResult  = __('%customer', ['customer' => 'John Doe']);
        $this->quoteHelper
            ->expects($this->once())
            ->method('resolveCurrentQuote')
            ->willReturn($this->quote);
        $this->companyDetailsProviderFactory
            ->expects($this->once())
            ->method('create')
            ->with(['quote' => $this->quote])
            ->willReturn($this->companyDetailsProvider);
        $this->companyDetailsProvider
            ->expects($this->once())
            ->method('getQuoteOwnerName')
            ->willReturn('John Doe');  
       
        $this->negotiableQuoteMock->method('getCreatorId')->willReturn(0);
        $this->quote->method('getExtensionAttributes')->willReturn(
            $this->getMockBuilder(\Magento\NegotiableQuote\Model\QuoteExtension::class)
                ->disableOriginalConstructor()
                ->setMethods(['getNegotiableQuote'])
                ->getMockForAbstractClass()
        );
        $this->quote->getExtensionAttributes()->method('getNegotiableQuote')->willReturn($this->negotiableQuoteMock);
        $result = $this->viewModel->getQuoteCreatedBy();
        $this->assertEquals($expectedResult, $result);
    }
 
    /**
     * Tests the getQuoteCreatedBy2 method.
     *
     * This test verifies the behavior of the getQuoteCreatedBy2 method to ensure it returns
     * the expected result under the given test conditions.
     *
     * @return void
     */
    public function testGetQuoteCreatedBy2(): void
    {
        $expectedResult  = __('%customer', ['customer' => 'John Doe']);
        $this->quoteHelper
            ->expects($this->once())
            ->method('resolveCurrentQuote')
            ->willReturn($this->quote);
        $this->companyDetailsProviderFactory
            ->expects($this->once())
            ->method('create')
            ->with(['quote' => $this->quote])
            ->willReturn($this->companyDetailsProvider);
        $this->companyDetailsProvider
            ->expects($this->once())
            ->method('getQuoteOwnerName')
            ->willReturn('John Doe');
 
        $this->negotiableQuoteMock->method('getCreatorId')->willReturn(1);
        $this->quote->method('getExtensionAttributes')->willReturn(
            $this->getMockBuilder(\Magento\NegotiableQuote\Model\QuoteExtension::class)
                ->disableOriginalConstructor()
                ->setMethods(['getNegotiableQuote'])
                ->getMockForAbstractClass()
        );
        $this->quote->getExtensionAttributes()->method('getNegotiableQuote')->willReturn($this->negotiableQuoteMock);
        $this->negotiableQuoteMock->method('getCreatorType')->willReturn(1);
        $this->negotiableQuoteMock->method('getCreatorId')->willReturn(1);
        $this->negotiableQuoteMock->method('getQuoteId')->willReturn(1);
        $this->creator->method('retrieveCreatorName')->willReturn('Admin User');
        $expectedResult  = __('%creator for %customer', ['creator' => 'Admin User', 'customer' => 'John Doe']);    
        $result = $this->viewModel->getQuoteCreatedBy();
        $this->assertEquals($expectedResult, $result);
    }
}
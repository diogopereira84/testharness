<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Block;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Block\QuoteDetails;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Company\Model\CompanyRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogDocumentUserSettingsHelper;
use Magento\Company\Model\Company;

/**
 * Test class for OrderQuoteSuccess Block
 */
class QuoteDetailsTest extends TestCase
{

    protected $storeManager;
    protected $deliveryHelperMock;
    protected $catalogDocumentUserSettingsHelperMock;
    protected $companyModel;
    protected $quoteDetailsData;
    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var CompanyRepository $companyRepository
     */
    protected $companyRepository;

    /**
     * @var UploadToQuoteViewModel
     */
    protected UploadToQuoteViewModel $uploadToQuoteViewModel;

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected AdminConfigHelper $adminConfigHelper;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getId','getCode','getGroup'])
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOndemandCompanyInfo','getSiItems'])
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getuploadToQuoteNextStepContent', 'getAllowNextStepContent'])
            ->getMock();

        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteEnable'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUploadToQuoteConfigValue'])
            ->getMock();
        
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);

        $this->catalogDocumentUserSettingsHelperMock = $this->createMock(CatalogDocumentUserSettingsHelper::class);

        $this->companyModel = $this->getMockBuilder(Company::class)
        ->disableOriginalConstructor()
        ->setMethods(['load', 'getAllowOwnDocument', 'getAllowSharedCatalog'])
        ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteDetailsData = $objectManagerHelper->getObject(
            QuoteDetails::class,
            [
                'storeManager' => $this->storeManager,
                'customerSession' => $this->customerSession,
                'companyRepository' => $this->companyRepository,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'adminConfigHelper' => $this->adminConfigHelper,
                'deliveryHelper' => $this->deliveryHelperMock,
                'catalogDocumentUserSettingsHelper' => $this->catalogDocumentUserSettingsHelperMock
            ]
        );
    }

    /**
     * Test method for getNextStepContent
     *
     * @return void
     */
    public function testGetNextStepContent()
    {
        $returnValue = 'Next Step text';
        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')->willReturn(['company_id' => 71]);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->companyRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->any())
            ->method('getuploadToQuoteNextStepContent')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getNextStepContent());
    }

    /**
     * Test method for getAllowNextStepContent
     *
     * @return void
     */
    public function testGetAllowNextStepContent()
    {
        $returnValue = 1;
        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')->willReturn(['company_id' => 71]);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->storeManager->expects($this->once())->method('getGroup')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getCode')->willReturn('main_website_store');
        $this->companyRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->any())
            ->method('getAllowNextStepContent')->willReturn($returnValue);
        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('isUploadToQuoteEnable')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getAllowNextStepContent());
    }

    /**
     * Test getRequestChangeMessage
     *
     * @return void
     */
    public function testGetSiItems()
    {
        $returnValue = [
            'items' => [
                'si' => 'test',
                'item_id' => '1234'
            ]
        ];
        $this->customerSession
        ->expects($this->once())->method('getSiItems')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getSiItems());
    }

    /**
     * Test getRequestChangeMessage
     *
     * @return void
     */
    public function testGetRequestChangeMessage()
    {
        $returnValue = 'Test Message';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getRequestChangeMessage());
    }

    /**
     * Test getRequestChangeCancelCTALabel
     *
     * @return void
     */
    public function testGetRequestChangeCancelCTALabel()
    {
        $returnValue = 'Cancel';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getRequestChangeCancelCTALabel());
    }

    /**
     * Test getRequestChangeCTALabel
     *
     * @return void
     */
    public function testGetRequestChangeCTALabel()
    {
        $returnValue = 'Request Change';
        $this->adminConfigHelper
        ->expects($this->once())->method('getUploadToQuoteConfigValue')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteDetailsData->getRequestChangeCTALabel());
    }

    /**
     * @return boolean
     */
    public function testIsEproCustomer()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isEproCustomer')
        ->willReturn(true);

        $this->assertNotNull($this->quoteDetailsData->isEproCustomer());
    }

     /**
      * @test testgetCompanyConfiguration
      */
    public function testGetCompanyConfiguration()
    {
        $this->catalogDocumentUserSettingsHelperMock->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->assertNotNull($this->quoteDetailsData->getCompanyConfiguration());
    }
}

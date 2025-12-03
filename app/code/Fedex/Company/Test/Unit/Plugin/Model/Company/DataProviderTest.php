<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Plugin\Model\Company;

use Fedex\Company\Plugin\Model\Company\DataProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\Session;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Model\Company\Custom\Billing\Invoiced\Mapper as InvoicedMapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper as CreditCardMapper;
use Fedex\Company\Model\Company\Custom\Billing\Shipping\Mapper as ShippingMapper;
use Fedex\Company\Model\Company\DataProvider as DataProviderMain;

/**
 * Test class for DataProvider
 */
class DataProviderTest extends TestCase
{
    protected $dataProviderMain;
    protected $company;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataProvider;
    /**
     * @var ToggleConfig $toggleConfig
     */
    private $toggleConfig;

    /**
     * @var AdminConfigHelper $orderApprovalB2BHelper
     */
    private $orderApprovalB2BHelper;

    /**
     * @var ConfigInterface $configInterface
     */
    private $configInterface;

    /**
     * @var RequestInterface $request
     */
    private $request;

    /**
     * @var Session $adminSession
     */
    private $adminSession;

    /**
     * @var InvoicedMapper $invoicedMapper
     */
    private $invoicedMapper;

    /**
     * @var CreditCardMapper $creditCardMapper
     */
    private $creditCardMapper;

    /**
     * @var ShippingMapper $shippingMapper
     */
    private $shippingMapper;

    /**
     * Create mock for each contructor
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderApprovalB2BHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->setMethods(['isOrderApprovalB2bGloballyEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['getE414712HeroBannerCarouselForCommercial'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adminSession = $this->getMockBuilder(Session::class)
            ->setMethods(['setCompanyAdminId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invoicedMapper = $this->getMockBuilder(InvoicedMapper::class)
            ->setMethods(['fromJson', 'getItemsArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditCardMapper = $this->getMockBuilder(CreditCardMapper::class)
            ->setMethods(['fromJson', 'getItemsArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingMapper = $this->getMockBuilder(ShippingMapper::class)
            ->setMethods(['fromJson', 'getItemsArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProviderMain = $this->getMockBuilder(DataProviderMain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getCompanyUrl', 'getCompanyUrlExtention', 'getIsSensitiveDataEnabled', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->dataProvider = $this->objectManager->getObject(
            DataProvider::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'orderApprovalB2BHelper' => $this->orderApprovalB2BHelper,
                'configInterface' => $this->configInterface,
                'adminSession' => $this->adminSession,
                'request' => $this->request,
                'invoicedMapper' => $this->invoicedMapper,
                'creditCardMapper' => $this->creditCardMapper,
                'shippingMapper' => $this->shippingMapper
            ]
        );
    }

    /**
     * Test afterGetMeta
     *
     * @return void
     */
    public function testAfterGetMeta()
    {
        $arrData = [];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->dataProviderMain->expects($this->any())->method('getName')->willReturn('company_form_data_source');
        $this->request->expects($this->once())->method('getParam')->willReturn(1);

        $this->assertIsArray($this->dataProvider->afterGetMeta($this->dataProviderMain, $arrData));
    }

    /**
     * Test afterGetMeta with selfreg data source
     *
     * @return void
     */
    public function testAfterGetMetaWithSelfRegDataSource()
    {
        $arrData = [];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->dataProviderMain->expects($this->any())->method('getName')
        ->willReturn('selfreg_company_form_data_source');

        $this->assertIsArray($this->dataProvider->afterGetMeta($this->dataProviderMain, $arrData));
    }

    /**
     * Test afterGetMeta with MVP cata data source
     *
     * @return void
     */
    public function testAfterGetMetaWithMVPCatalogDataSource()
    {
        $arrData = [
            'company_admin' => 'test'
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->dataProviderMain->expects($this->any())->method('getName')
        ->willReturn('mvp_catalog_company_form_data_source');

        $this->assertIsArray($this->dataProvider->afterGetMeta($this->dataProviderMain, $arrData));
    }

    /**
     * Test afterGetGeneralData
     *
     * @return void
     */
    public function testAfterGetGeneralData()
    {
        $arrData = [];
        $this->company->expects($this->once())->method('getCompanyUrl')->willReturn('https://stage.fedex.com');
        $this->company->expects($this->once())->method('getCompanyUrlExtention')->willReturn('b2b_order');
        $this->company->expects($this->once())->method('getIsSensitiveDataEnabled')->willReturn(true);

        $this->assertIsArray($this->dataProvider->afterGetGeneralData(
            $this->dataProviderMain,
            $arrData,
            $this->company
        ));
    }

    /**
     * Test afterGetCompanyResultData
     *
     * @return void
     */
    public function testAfterGetCompanyResultData()
    {
        $arrData = [];
        $this->dataProviderMain->expects($this->once())->method('getGeneralData')->willReturn([]);
        $this->dataProviderMain->expects($this->once())->method('getStoreDetails')->willReturn([]);
        $this->dataProviderMain->expects($this->once())->method('getNewStoreDetails')->willReturn([]);
        $this->company->expects($this->any())->method('getData')->willReturn('test');

        $this->assertIsArray($this->dataProvider->afterGetCompanyResultData(
            $this->dataProviderMain,
            $arrData,
            $this->company
        ));
    }
}

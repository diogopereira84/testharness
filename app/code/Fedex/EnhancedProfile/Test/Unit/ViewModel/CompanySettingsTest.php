<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Helper\Data;
use Psr\Log\LoggerInterface;
use Fedex\EnhancedProfile\ViewModel\CompanySettings;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Fedex\Company\Model\AdditionalData;

class CompanySettingsTest extends TestCase
{
    protected $companyMock;
    protected $companyExtensionInterfaceMock;
    protected $additionalDataMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /** @var object|CompanySettings */
    protected $companySettingsMock;
    /**
     * @var Data
     */
    private $deliveryDataHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyLevelLogo', 'getAssignedCompany'])
            ->getMock();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyExtensionInterfaceMock = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->setMethods([
                'getCompanyPaymentOptions',
                'setCompanyPaymentOptions',
                'getFedexAccountOptions',
                'getCreditcardOptions',
                'getDefaultPaymentMethod',
                'getCcToken',
                'getCcData',
                'getCcTokenExpiryDateTime',
            ])
            ->addMethods([
                'getIsPromoDiscountEnabled',
                'getIsAccountDiscountEnabled',
                'getOrderNotes',
                'getTermsAndConditions',
                'getIsReorderEnabled',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyAdditionalData',
                'getIsBannerEnable',
                'getBannerTitle',
                'getDescription',
                'getCtaText',
                'getCtaLink',
                'getLinkOpenInNewTab',
                'getIconography',
                'getIsReorderEnabled'
            ])->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->companySettingsMock = $this->objectManager->getObject(
            CompanySettings::class,
            [
                'deliveryDataHelper' => $this->deliveryDataHelper,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Get company level site logo
     *
     * @return string|null
     */
    public function testGetCompanyLevelSiteLogo()
    {
        $this->deliveryDataHelper->expects($this->any())->method('getCompanyLevelLogo')->willReturn('companylogo');
        $this->assertNotNull($this->companySettingsMock->getCompanyLevelSiteLogo());
    }

    public function testGetCompanyConfigurationReturnsCompany()
    {
        $companyMock = $this->createMock(CompanyInterface::class);

        $this->deliveryDataHelper
            ->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($companyMock);

        $this->assertEquals($companyMock, $this->companySettingsMock->getCompanyConfiguration());
    }

    public function testGetCompanyConfigReorderEnabledReturnsTrue()
    {
        $companyMock = $this->createMock(CompanyInterface::class);
        $this->deliveryDataHelper
            ->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($companyMock);

        $companyMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCompanyAdditionalData')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->once())
            ->method('getIsReorderEnabled')
            ->willReturn(true);


        $this->assertTrue($this->companySettingsMock->getCompanyConfigReorderEnabled());
    }

    public function testGetCompanyConfigReorderEnabledReturnsFalseWhenNoCompany()
    {
        $this->deliveryDataHelper
            ->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn(null);

        $this->assertFalse($this->companySettingsMock->getCompanyConfigReorderEnabled());
    }

    public function testGetCompanyConfigNotificationBannerReturnsData()
    {
        $expectedData = [
            'is_banner_enable' => '0',
            'banner_title' => null,
            'description' => null,
            'iconography' => null,
            'cta_text' => null,
            'cta_link' => null,
            'link_open_in_new_tab' => '0'
        ];

        $companyMock = $this->createMock(CompanyInterface::class);
        $this->deliveryDataHelper
            ->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($companyMock);

        $companyMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCompanyAdditionalData')
            ->willReturnSelf();

        $this->assertEquals($expectedData, $this->companySettingsMock->getCompanyConfigNotificationBanner());
    }

    public function testGetCompanyConfigNotificationBannerReturnsEmptyArrayWhenNoCompany()
    {
        $this->deliveryDataHelper
            ->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn(null);

        $this->assertEquals([], $this->companySettingsMock->getCompanyConfigNotificationBanner());
    }
}

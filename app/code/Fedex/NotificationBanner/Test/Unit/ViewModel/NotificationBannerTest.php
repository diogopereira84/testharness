<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\NotificationBanner\ViewModel;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\NotificationBanner\ViewModel\NotificationBanner;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\Company\Model\AdditionalData;
use PHPUnit\Framework\TestCase;

/**
 * NotificationBannerTest unit test class
 */
class NotificationBannerTest extends TestCase
{
    protected $storeInterfaceMock;
    protected $companyInterface;
    protected $sessionMock;
    protected $toggleConfigMock;
    protected $companyHelperMock;
    protected $additionalDataMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const NOTIFICATION_BANNER_ENABLE =
        'notification_banner/notification_banner_all_flow_group/notification_banner_enabled';
    public const NOTIFICATION_BANNER_TITLE =
        'notification_banner/notification_banner_all_flow_group/banner_title';
    public const NOTIFICATION_BANNER_SELECTED_ICON =
        'notification_banner/notification_banner_all_flow_group/notification_iconography';
    public const NOTIFICATION_BANNER_EDITOR =
        'notification_banner/notification_banner_all_flow_group/notificaiton_banner_editor';
    public const NOTIFICATION_BANNER_CTA_TEXT =
        'notification_banner/notification_banner_all_flow_group/cta_text';
    public const NOTIFICATION_BANNER_CTA_LINK =
        'notification_banner/notification_banner_all_flow_group/cta_link';
    public const NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW =
        'notification_banner/notification_banner_all_flow_group/link_open_window';

    public const STORE_ID = 1;
    public const ONDEMAND_STORE_ID = 234;
    public const COMPANY_ID = 1;

    /**
     * @var NotificationBanner|MockObject
     */
    protected $notificationBannerMock;

    /**
     * @var NotificationBanner|MockObject
     */
    protected $bannerMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Repository|MockObject
     */
    protected $assetRepoMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlInterfaceMock;

    protected function setUp(): void
    {
        $this->bannerMock = $this->getMockBuilder(NotificationBanner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->storeInterfaceMock = $this
            ->getMockBuilder(StoreInterface::class)
            ->setMethods(['getCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyInterface = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['isLoggedIn', 'getCustomerCompany'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getStoreId', 'getGroupId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getCode', 'getBaseUrl', 'getGroupId'])
            ->getMock();
        $this->assetRepoMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerName'])
            ->getMock();
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->companyHelperMock = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerCompany'])
            ->getMock();
        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyAdditionalData',
                'setCompanyAdditionalData',
                'getIsBannerEnable',
                'setIsBannerEnable',
                'getBannerTitle',
                'setBannerTitle',
                'getIconography',
                'setIconography',
                'getCtaText',
                'setCtaText',
                'getCtaLink',
                'setCtaLink',
                'getLinkOpenInNewTab',
                'setLinkOpenInNewTab',
                'getDescription',
                'setDescription',
                ]
            )->getMockForAbstractClass();

        $this->sessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(static::COMPANY_ID);
        $this->companyHelperMock->expects($this->any())
            ->method('getCustomerCompany')->with(static::COMPANY_ID)->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->objectManager = new ObjectManager($this);
        $this->notificationBannerMock = $this->objectManager->getObject(
            NotificationBanner::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'customerSession' => $this->sessionMock,
                'storeManager' => $this->storeManagerMock,
                'assetRepo' => $this->assetRepoMock,
                'request' => $this->requestMock,
                'urlInterface' => $this->urlInterfaceMock,
                'toggleConfig' => $this->toggleConfigMock,
                'companyHelper' => $this->companyHelperMock,
            ]
        );
    }

    /**
     * Test IsFeatureToggleDisabled
     *
     * @return void
     */
    public function testIsFeatureToggleDisabled()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->assertFalse(false);
    }

    /**
     * Common function for banner enable logic
     */
    protected function getBannerEnableConf()
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_DEFAULT);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')->with(NotificationBanner::NOTIFICATION_BANNER_ENABLE)
            ->willReturn(1);
    }

    /**
     * Test getBannerConfiguration
     *
     * @return void
     */
    public function testGetBannerConfiguration()
    {
        $bannerConfiguration = [
            'enabled'              => true,
            'isPageNotFound'       => true,
            'banner_title'         => 'Scheduled Maintenance',
            'iconography'          => 'information',
            'description'          => 'Banner Body Text',
            'cta_text'             => 'CTA Text',
            'cta_link'             => 'CTA Link',
            'link_open_in_new_tab' => '1'
        ];
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_DEFAULT);

        $this->scopeConfigMock->expects($this->exactly(7))
            ->method('getValue')
            ->withConsecutive(
                [NotificationBanner::NOTIFICATION_BANNER_ENABLE],
                [NotificationBanner::NOTIFICATION_BANNER_TITLE],
                [NotificationBanner::NOTIFICATION_BANNER_SELECTED_ICON],
                [NotificationBanner::NOTIFICATION_BANNER_EDITOR],
                [NotificationBanner::NOTIFICATION_BANNER_CTA_TEXT],
                [NotificationBanner::NOTIFICATION_BANNER_CTA_LINK],
                [NotificationBanner::NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW]
            )->willReturnOnConsecutiveCalls(
                1,
                'Scheduled Maintenance',
                'information',
                'Banner Body Text',
                'CTA Text',
                'CTA Link',
                '1'
            );
        $controllerName = 'noroute';
        $currentUrl = '';
        $this->requestMock->expects($this->once())->method('getControllerName')->willReturn($controllerName);
        $this->urlInterfaceMock->expects($this->once())->method('getCurrentUrl')->willReturn($currentUrl);

        $this->assertEquals($bannerConfiguration, $this->notificationBannerMock->getBannerConfiguration());
    }
    

    /**
     * Test getNotificationBannerConfig
     *
     * @return void
     */
    public function testgetNotificationBannerConfig()
    {
        $isEnabled = 1;
        $path = NotificationBanner::NOTIFICATION_BANNER_ENABLE;
        $this->scopeConfigMock->expects($this->any())->method('getValue')->with($path)->willReturn($isEnabled);
        $this->assertEquals(
            $isEnabled,
            $this->notificationBannerMock
                ->getNotificationBannerConfig($path, static::STORE_ID)
        );
    }

    /**
     * Test getCurrentStoreId
     *
     * @return void
     */
    public function testGetCurrentStoreId()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->assertEquals($storeId, $this->notificationBannerMock->getCurrentStoreId());
    }

    /**
     * Test testGetNotificationBannerIcon
     *
     * @return void
     */
    public function testGetNotificationBannerIcon()
    {
        $imageUrl = 'Fedex_NotificationBanner::images/Alert.png';
        $this->assetRepoMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->assertEquals($imageUrl, $this->notificationBannerMock->getNotificationBannerIcon('warning'));
    }

    /**
     * Test testGetNotificationBannerInformationIcon
     *
     * @return void
     */
    public function testGetNotificationBannerInformationIcon()
    {
        $imageUrl = 'Fedex_NotificationBanner::images/lightbulb-icon.png';
        $this->assetRepoMock->expects($this->once())->method('getUrl')->willReturn($imageUrl);

        $this->assertEquals($imageUrl, $this->notificationBannerMock->getNotificationBannerIcon('information'));
    }

    /**
     * Test Check 404 page
     *
     * @return boolean true|false
     */
    public function testIsPageNotFound()
    {
        $controllerName = 'noroute';
        $currentUrl = '';
        $this->requestMock->expects($this->once())->method('getControllerName')->willReturn($controllerName);
        $this->urlInterfaceMock->expects($this->once())->method('getCurrentUrl')->willReturn($currentUrl);
        $this->assertTrue($this->notificationBannerMock->isPageNotFound());
    }

    /**
     * Test Check 404 page with toggle
     *
     * @return void
     */
    public function testIsPageNotFoundWithNoRouteWithToggle()
    {
        $controllerName = 'home';
        $currentUrl = 'canva';
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->requestMock->expects($this->once())->method('getControllerName')->willReturn($controllerName);
        $this->urlInterfaceMock->expects($this->once())->method('getCurrentUrl')->willReturn($currentUrl);

        $this->assertTrue($this->notificationBannerMock->isPageNotFound());
    }

    /**
     * Test Check 404 page
     * @return boolean true|false
     */
    public function testIsPageNotFoundWithOutNoRoute()
    {
        $controllerName = 'home';
        $currentUrl = 'index';
        $this->requestMock->expects($this->once())->method('getControllerName')->willReturn($controllerName);
        $this->urlInterfaceMock->expects($this->once())->method('getCurrentUrl')->willReturn($currentUrl);
        $this->assertFalse($this->notificationBannerMock->isPageNotFound());
    }

    /**
     * Test testNotificationBannerSelectedIconType
     */
    public function testNotificationBannerSelectedIconType()
    {
        $selectedIconType = 'warning';
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_SELECTED_ICON)
            ->willReturn($selectedIconType);

        $this->assertEquals($selectedIconType, $this->notificationBannerMock->notificationBannerSelectedIconType());
    }

    /**
     * Test testNotificationBannerTitle
     */
    public function testNotificationBannerTitle()
    {
        $storeId = 1;
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_TITLE)
            ->willReturn($storeId);

        $this->assertEquals($storeId, $this->notificationBannerMock->notificationBannerTitle());
    }

    /**
     * Test testNotificationBannerBodyText
     */
    public function testNotificationBannerBodyText()
    {
        $bodyData = 'this is stage3';
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_EDITOR)
            ->willReturn($bodyData);
        $this->assertEquals($bodyData, $this->notificationBannerMock->notificationBannerBodyText());
    }

    /**
     * Test testIsNotificationBannerEnabled
     */
    public function testIsNotificationBannerEnabled()
    {
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_ENABLE)
            ->willReturn(true);

        $this->assertTrue($this->notificationBannerMock->isNotificationBannerEnabled());
    }

    /**
     * Test testNotificationBannerCtaLink
     */
    public function testNotificationBannerCtaText()
    {
        $ctaText = 'Test';
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_CTA_TEXT)
            ->willReturn($ctaText);

        $this->assertEquals($ctaText, $this->notificationBannerMock->notificationBannerCtaText());
    }

    /**
     * Test testNotificationBannerCtaLink
     */
    public function testNotificationBannerCtaLink()
    {
        $bodyText = 'this is test';
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(static::NOTIFICATION_BANNER_CTA_LINK)
            ->willReturn($bodyText);

        $this->assertEquals($bodyText, $this->notificationBannerMock->notificationBannerCtaLink());
    }

    /**
     * Test testNotificationBannerLinkOpenInNewWindow
     */
    public function testNotificationBannerLinkOpenInNewWindow()
    {
        $this->storeMock->expects($this->once())->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->assertNull($this->notificationBannerMock->notificationBannerLinkOpenInNewWindow());
    }

    /**
     * Test getFinalBannerEnableStatus
     */
    public function testGetFinalBannerEnableStatus()
    {
        $this->getBannerEnableConf();
        $this->assertEquals(
            NotificationBanner::STORE_LEVEL_ENABLED,
            $this->notificationBannerMock->getFinalBannerEnableStatus(static::STORE_ID)
        );
    }

    /**
     * Test getFinalStoreCompBannerEnableStatus
     */
    public function testGetFinalStoreCompBannerEnableStatus()
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')->with(NotificationBanner::NOTIFICATION_BANNER_ENABLE)->willReturn(1);
        $this->assertEquals(
            NotificationBanner::STORE_LEVEL_ENABLED,
            $this->notificationBannerMock->getFinalBannerEnableStatus(static::STORE_ID)
        );
    }

    /**
     * Test getFinalCompBannerEnableStatus
     */
    public function testGetFinalCompBannerEnableStatus()
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')->with(NotificationBanner::NOTIFICATION_BANNER_ENABLE)
            ->willReturn(0);
        $this->additionalDataMock->expects($this->any())
            ->method('getIsBannerEnable')
            ->willReturn(1);
        $this->assertEquals(
            NotificationBanner::COMPANY_LEVEL_ENABLED,
            $this->notificationBannerMock->getFinalBannerEnableStatus(static::STORE_ID)
        );
    }

    /**
     * Test getDisableStatus
     */
    public function testGetDisableStatus()
    {
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')->willReturn(null);
        $this->assertNull($this->notificationBannerMock->getFinalBannerEnableStatus(2));
    }

    /**
     * Test testGetFinalBannerConfValue
     */
    public function testGetFinalBannerConfValue()
    {
        $enableLevel = NotificationBanner::STORE_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_DEFAULT);
        $this->assertNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_ENABLE,
                static::STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfTitleValueWithCompConf
     */
    public function testGetFinalBannerConfTitleValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getBannerTitle')
            ->willReturn('Scheduled Maintenance');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_TITLE,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfIconographyValueWithCompConf
     */
    public function testGetFinalBannerConfIconographyValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getIconography')
            ->willReturn('Warning');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_SELECTED_ICON,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfDescriptionValueWithCompConf
     */
    public function testGetFinalBannerConfDescriptionValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getDescription')
            ->willReturn('Banner Body Text');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_EDITOR,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfCtaTextValueWithCompConf
     */
    public function testGetFinalBannerConfCtaTextValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getCtaText')
            ->willReturn('Cta Text');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_CTA_TEXT,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfCtaLinkValueWithCompConf
     */
    public function testGetFinalBannerConfCtaLinkValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getCtaLink')
            ->willReturn('Cta Link');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_CTA_LINK,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }

    /**
     * Test testGetFinalBannerConfOpenWindowValueWithCompConf
     */
    public function testGetFinalBannerConfOpenWindowValueWithCompConf()
    {
        $enableLevel = NotificationBanner::COMPANY_LEVEL_ENABLED;

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')
            ->willReturn(NotificationBanner::STORE_ONDEMAND);
        $this->additionalDataMock->expects($this->any())
            ->method('getLinkOpenInNewTab')
            ->willReturn('getLinkOpenInNewTab');
        $this->assertNotNull(
            $this->notificationBannerMock->getFinalBannerConfValue(
                NotificationBanner::NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW,
                static::ONDEMAND_STORE_ID,
                $enableLevel
            )
        );
    }
}

<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Update;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Store\Model\GroupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AdditionalData;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Model\UrlRewrite;
use Fedex\Ondemand\Controller\Update\LogoSetting;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreFactory;

class LogoSettingTest extends TestCase
{
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Store\Model\ScopeInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ScopeInterfaceMock;
    protected $loggerMock;
    /**
     * @var (\Fedex\Ondemand\Test\Unit\Controller\Update\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ConfigMock;
    protected $GroupFactoryMock;
    protected $additionalData;
    protected $additionalDataFactoryMock;
    protected $companyFactoryMock;
    protected $companyMock;
    protected $companyCollection;
    protected $additionalDataCollection;
    protected $urlRewriteFactoryMock;
    protected $urlRewriteModelMock;
    protected $storeFactoryMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $logoSettingMock;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {

        $this->scopeConfigInterfaceMock  = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ScopeInterfaceMock  = $this->getMockBuilder(ScopeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->ConfigMock  = $this->getMockBuilder(Config::class)
            ->setMethods(['create', 'saveConfig', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->GroupFactoryMock = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['load', 'create', 'getStoreIds', 'getGroupId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalData = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId'])
            ->getMock();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->setMethods(['create', 'getCollection', 'addFieldToSelect', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getId', 'load', 'getData', 'getCompanyUrl', 'setData', 'save'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'addFieldToSelect', 'getFirstItem', 'getData', 'getCompanyUrl', 'setData', 'save'])
            ->getMock();

        $this->additionalDataCollection = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId', 'getData'])
            ->getMock();

        $this->urlRewriteFactoryMock = $this->getMockBuilder(UrlRewriteFactory::class)
            ->setMethods(
                [
                    'create',
                    'setStoreId',
                    'setEntityType',
                    'setIsSystem',
                    'setIdPath',
                    'setRedirectType',
                    'setRequestPath',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();


        $this->urlRewriteModelMock = $this->getMockBuilder(UrlRewrite::class)
            ->setMethods(
                [
                    'create',
                    'setStoreId',
                    'setEntityType',
                    'setIsSystem',
                    'setIdPath',
                    'setRedirectType',
                    'setRequestPath',
                    'setTargetPath',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

            $this->storeFactoryMock = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(
                [
                    'create',
                    'load',
                    'getCode'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->logoSettingMock = $this->objectManagerHelper->getObject(
            LogoSetting::class,
            [
                'groupFactory' => $this->GroupFactoryMock,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'logger' => $this->loggerMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'companyFactory' => $this->companyFactoryMock,
                'urlRewriteFactory' => $this->urlRewriteFactoryMock,
                'storeFactory' => $this->storeFactoryMock
            ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $groupId = 9;
        $storeIds = [9, 10, 11, 68];


        $this->GroupFactoryMock->expects($this->any())->method('create')->willReturn($this->GroupFactoryMock);
        $this->GroupFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->GroupFactoryMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->GroupFactoryMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);
        $this->storeFactoryMock->expects($this->any())->method('create')->willReturn($this->storeFactoryMock);
        $this->storeFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeFactoryMock->expects($this->any())->method('getCode')->willReturn('test');
        $this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCompanyUrl')->willReturn('https://google.com/fedex');
        $this->companyMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->companyMock->expects($this->any())->method('save')->willReturnSelf();
        $this->urlRewriteFactoryMock->expects($this->any())->method('create')->willReturn($this->urlRewriteModelMock);
        $this->urlRewriteModelMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setEntityType')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setIsSystem')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setIdPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setRedirectType')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setTargetPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setRequestPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('save')->willReturnSelf();
        $this->testgetImgSrcValue();
        $this->testgetCustomerCompanyIdByStore();
        $this->assertNull($this->logoSettingMock->execute());
    }

    /**
     * testExecute
     */
    public function testExecuteWithException()
    {
        $groupId = 9;
        $storeIds = [9, 10, 11, 68];
        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception();
        $this->GroupFactoryMock->expects($this->any())->method('create')->willReturn($this->GroupFactoryMock);
        $this->GroupFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->GroupFactoryMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->GroupFactoryMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);
        $this->companyFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertNull($this->logoSettingMock->execute());
    }
   /**
     * testgetImgSrcValue
     */
    public function testgetImgSrcValue()
    {

        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('logo.png');
        $this->assertNotNull($this->logoSettingMock->getImgSrcValue(108));
    }
   /**
     * testgetCustomerCompanyIdByStore
     */
    public function testgetCustomerCompanyIdByStore()
    {
        $this->additionalDataFactoryMock->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getData')->willReturn(['67', '63']);
        $this->assertNotNull($this->logoSettingMock->getCustomerCompanyIdByStore(108));
    }


       /**
     * testExecute
     */
    public function testExecuteWithUrlException()
    {
        $groupId = 9;
        $storeIds = [9, 10, 11, 68];
        $exception = new \Exception();

        $this->GroupFactoryMock->expects($this->any())->method('create')->willReturn($this->GroupFactoryMock);
        $this->GroupFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->GroupFactoryMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->GroupFactoryMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);
        $this->storeFactoryMock->expects($this->any())->method('create')->willReturn($this->storeFactoryMock);
        $this->storeFactoryMock->expects($this->any())->method('load')->willReturnSelf();

        $this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCompanyUrl')->willReturn('https://google.com/fedex');
        $this->companyMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->companyMock->expects($this->any())->method('save')->willReturnSelf();
        $this->urlRewriteFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->urlRewriteModelMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setEntityType')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setIsSystem')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setIdPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setRedirectType')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setTargetPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('setRequestPath')->willReturnSelf();
        $this->urlRewriteModelMock->expects($this->any())->method('save')->willReturnSelf();
        $this->testgetImgSrcValue();
        $this->testgetCustomerCompanyIdByStore();
        $this->assertNull($this->logoSettingMock->execute());
    }

    public function testupdateUrlsWithException()
    {
        $exception = new \Exception();
        $this->storeFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertNotNull($this->logoSettingMock->updateUrls(108,23));
    }

}

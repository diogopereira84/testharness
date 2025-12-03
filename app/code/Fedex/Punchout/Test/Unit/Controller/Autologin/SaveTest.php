<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Punchout\Test\Unit\Controller\Autologin;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Punchout\Controller\Autologin\Save;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Base\Helper\Auth;

class SaveTest extends TestCase
{
    protected $contextMock;
    protected $requestMock;
    protected $customerSessionMock;
    protected $customerFactoryMock;
    protected $customerMock;
    protected $storeManagerInterfaceMock;
    protected $storeInterfaceMock;
    protected $customerRepositoryInterfaceMock;
    protected $customerInterfaceMock;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestInterfaceMock;
    protected $resultJson;
    protected $resultJsonFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $cookieManagerInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $autoLoginSave;
    protected $companyFactory;
    protected $company;
    protected $companyCollection;
    protected CookieManagerInterface|MockObject $cookieManagerMock;
    protected CookieMetadataFactory|MockObject $cookieMetadataFactoryMock;
    protected PublicCookieMetadata|MockObject $publicCookieMetadataMock;
    protected ToggleConfig|MockObject $toggleConfig;
    protected Auth|MockObject $baseAuthMock;

    /**
     * Sample JSONSTRING
     * @var string
     */
    const JSONSTRING = '{"customer_id": 1,"email": "test@test.in","fname": "Test","lname": "Test","phone": "Test","ext": "Test","loginData": {"company_id": "123","redirect_url": "","response_url": "","cookie": "","company_name": "","gatewayToken": "","access_token": "","token_type": ""}}';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['setOndemandCompanyInfo', 'regenerateId', 'logout', 'setLastCustomerId', 'setCustomerAsLoggedIn', 'setCustomerCompany',
                'setBackUrl', 'setCommunicationUrl', 'setCommunicationCookie', 'setCompanyName', 'setGatewayToken',
                'setApiAccessToken', 'setApiAccessType', 'isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['loadByEmail', 'setWebsiteId', 'load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore', 'getWebsite'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMockForAbstractClass();

        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->company = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getData'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(CompanyCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMock();
        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['setPublicCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(cookieMetadataFactory::class)
            ->setMethods(['createPublicCookieMetadata', 'setPath',
                'setHttpOnly', 'setSecure', 'setSameSite', 'setDuration', 'setPublicCookie'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->setMethods(['setPath', 'setHttpOnly', 'setSecure', 'setSameSite', 'setDuration'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);

        $this->autoLoginSave = $this->objectManager->getObject(
            Save::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSessionMock,
                'customerFactory' => $this->customerFactoryMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'customerRepositoryInterface' => $this->customerRepositoryInterfaceMock,
                'logger' => $this->loggerMock,
                'resultJsonFactory' => $this->resultJsonFactory,
                'companyFactory' => $this->companyFactory,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'cookieManager' => $this->cookieManagerInterfaceMock,
                'toggleConfig' => $this->toggleConfig,
                'authHelper' => $this->baseAuthMock
            ]
        );

    }

    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnMap(
                [
                    ['data', null, static::JSONSTRING]
                ]
            );
    }

    /**
     * Test Execute
     */
    public function testExecute()
    {
        $testWebsiteId = 1;

        $this->prepareRequestMock();

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn($testWebsiteId);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerInterfaceMock);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->company);
        $this->company->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->company);
        $this->company->expects($this->any())->method('getData')->willReturn(['entity_id' => 0, 'name' => 'Test']);
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();

        $this->customerSessionMock->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setLastCustomerId');//->willReturnSelf();
        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cookieMetadataFactoryMock->expects($this->once())->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->expects($this->once())->method('setPath')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setHttpOnly')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setSecure')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setSameSite')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setDuration')->willReturnSelf();
        $this->cookieManagerInterfaceMock->expects($this->once())->method('setPublicCookie')->willReturnSelf();
        $this->assertIsObject($this->autoLoginSave->execute());
    }

    public function testExecuteWithSameCustomerEmail()
    {
        $testWebsiteId = 1;
        $this->prepareRequestMock();

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn($testWebsiteId);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn(5);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->company);
        $this->company->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->company);
        $this->company->expects($this->any())->method('getData')->willReturn(['entity_id' => 0, 'name' => 'Test']);
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->autoLoginSave->execute());
    }

    public function testExecuteWithLoggedInCustomer()
    {
        $testWebsiteId = 1;
        $this->prepareRequestMock();

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn($testWebsiteId);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerInterfaceMock);

        $this->customerSessionMock->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setLastCustomerId');//->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->company);
        $this->company->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->company);
        $this->company->expects($this->any())->method('getData')->willReturn(['entity_id' => 0, 'name' => 'Test']);
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->cookieMetadataFactoryMock->expects($this->once())->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->expects($this->once())->method('setPath')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setHttpOnly')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setSecure')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setSameSite')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->once())->method('setDuration')->willReturnSelf();
        $this->cookieManagerInterfaceMock->expects($this->once())->method('setPublicCookie')->willReturnSelf();

        $this->assertIsObject($this->autoLoginSave->execute());
    }

    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $testWebsiteId = 1;
        $this->prepareRequestMock();

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn($testWebsiteId);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSessionMock->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setLastCustomerId');//->willReturnSelf();

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->company);
        $this->company->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->company);
        $this->company->expects($this->any())->method('getData')->willReturn(['entity_id' => 0, 'name' => 'Test']);
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('load')->willThrowException($exception);
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willThrowException($exception);

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->autoLoginSave->execute());
    }
}

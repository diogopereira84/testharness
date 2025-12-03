<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Punchout\Test\Unit\Controller\Autologin;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Punchout\Controller\Autologin\Index;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Fedex\Login\Helper\Login;

class IndexTest extends TestCase
{  
	protected $contextMock;
 protected $customerSessionMock;
 protected $puchoutHelperDataMock;
 protected $resultPageFactoryMock;
 protected $resultPageMock;
 protected $resultLayoutMock;
 protected $resultRedirectFactoryMock;
 protected $resultRedirectMock;
 protected $requestInterfaceMock;
 protected $cookieManagerInterfaceMock;
 protected $cookieMetadataFactoryMock;
 protected $publicCookieMetadataMock;
 protected $loginHelper;
 /**
  * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
  */
 protected $objectManager;
 protected $autoLoginIndexTest;
 /**
     * Sample Token
     * @var string
     */
	const TOKEN = 'eyJhbGciOiJIUzI1Ni.IsInR5cCI6IkpXVCJ9';
	
	/**
     * Sample URL_403
     * @var string
     */
	const URL_403 = '/403';
	
	protected $requestMock;
    protected $companyFactory;
    protected $company;
    protected $companyCollection;
	
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
										->setMethods(['setOndemandCompanyInfo','isLoggedIn', 'logout', 'setLastCustomerId', 'getOndemandCompanyInfo'])
										->disableOriginalConstructor()
											->getMock();
									
		$this->puchoutHelperDataMock = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
										->setMethods(['autoLogin'])
											->disableOriginalConstructor()
												->getMock();
            
		$this->resultPageFactoryMock = $this->getMockBuilder(\Magento\Framework\View\Result\PageFactory::class)
										->setMethods(['create'])
											->disableOriginalConstructor()
												->getMock();
												
		$this->resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
										->setMethods(['getLayout'])
											->disableOriginalConstructor()
												->getMock();
            
		$this->resultLayoutMock = $this->getMockBuilder(\Magento\Framework\View\Result\Layout::class)
										->setMethods(['getBlock', 'setData'])
											->disableOriginalConstructor()
												->getMock();
            
		$this->resultRedirectFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RedirectFactory::class)
										->setMethods(['create'])
											->disableOriginalConstructor()
												->getMock();
            
		$this->resultRedirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
											->disableOriginalConstructor()
												->getMock();
												
		$this->requestInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
											->disableOriginalConstructor()
												->getMock();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->company = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(CompanyCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getFirstItem'])
            ->getMock();

        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['setPublicCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(cookieMetadataFactory::class)
            ->setMethods(['createPublicCookieMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->setMethods(['setPath', 'setHttpOnly','setSecure', 'setSameSite','setDuration'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loginHelper = $this->getMockBuilder(Login::class)
            ->setMethods(['setUrlExtensionCookie','getUrlExtensionCookie'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        
        $this->autoLoginIndexTest = $this->objectManager->getObject(
            Index::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSessionMock,
                'helper' => $this->puchoutHelperDataMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'resultRedirectFactory' => $this->resultRedirectFactoryMock,
                'companyFactory' => $this->companyFactory,
                'cookieManager' => $this->cookieManagerInterfaceMock,
				'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'loginHelper' => $this->loginHelper
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
            ->method('getParam')
            ->willReturnMap(
                [
                    ['token', null, static::TOKEN]
                ]
            );
    }
    
    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMockForMissingToken()
    {	
		$this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['token', null, null]
                ]
            );
    }
    
    /** 
     * Test for  createCsrfValidationException()
     * 
     * @return null
     */
    public function testCreateCsrfValidationException()
    {    
        $result = $this->autoLoginIndexTest->createCsrfValidationException($this->requestInterfaceMock);
        $this->assertEquals(null, $result);
    }

    /**
     * Test for  validateForCsrf()
     * 
     * @return bool
     */
    public function testValidateForCsrf()
    {    
        $result = $this->autoLoginIndexTest->validateForCsrf($this->requestInterfaceMock);
        $this->assertEquals(true, $result);
    }

    /**
     * Test for  executeWithSuccessfullyLogin()
     * 
     * @return  PageFactory
     */
    public function testExecuteWithSuccessfullyLogin()
    {    
		$this->prepareRequestMock();
		$autoLoginResponse = ['error' => 0, 'msg' => 'Customer Logged in Successfully', 'url' => '', 'customer_id' => '', 'allow' => 0, 'loginData' => ''];
		
		$this->puchoutHelperDataMock->expects($this->any())->method('autoLogin')->with(self::TOKEN)->willReturn($autoLoginResponse);
		$this->customerSessionMock->expects($this->any())->method('logout')->willReturnSelf();
		
		$this->resultPageFactoryMock->expects($this->any())->method('create')->willReturn($this->resultPageMock);
		$this->resultPageMock->expects($this->any())->method('getLayout')->willReturn($this->resultLayoutMock);
		$this->resultLayoutMock->expects($this->any())->method('getBlock')->willReturnSelf();
		
		$result = $this->autoLoginIndexTest->execute();
		$expected = \Magento\Framework\View\Result\Page::class;
        $this->assertInstanceOf($expected ,$result);
    }  
     
    /**
     * Test for executeWithMissingToken()
     */
    public function testExecuteWithMissingToken()
    {    
		$this->prepareRequestMockForMissingToken();
		$this->resultRedirectFactoryMock->expects($this->any())->method('create')->willReturn($this->resultRedirectMock);
		
		$result = $this->autoLoginIndexTest->execute();
		$this->assertEquals($this->resultRedirectMock ,$result);
    }   
    
    /**
     * Test for executeWithErrorInLogin()
     */
    public function testExecuteWithErrorInLogin()
    {    
		$this->prepareRequestMock();
		$autoLoginResponse = ['error' => 1, 'msg' => 'Unable to Login', 'url' => ''];
		
		$this->puchoutHelperDataMock->expects($this->any())->method('autoLogin')->with(self::TOKEN)->willReturn($autoLoginResponse);
		$this->resultRedirectFactoryMock->expects($this->any())->method('create')->willReturn($this->resultRedirectMock);
		
		$result = $this->autoLoginIndexTest->execute();
		$this->assertEquals($this->resultRedirectMock ,$result);
    }   
    
    /**
     * Test for executeWithAllowAccess()
     */
    public function testExecuteWithAllowAccess()
    {    
        
		$this->prepareRequestMock();
		$autoLoginResponse = ['error' => 0, 'msg' => 'Customer Logged in Successfully', 'url' => '', 'customer_id' => '', 'allow' => 1, 'loginData' => ['company_id'=>23]];
		
		$this->puchoutHelperDataMock->expects($this->any())->method('autoLogin')->with(self::TOKEN)->willReturn($autoLoginResponse);
		$this->resultRedirectFactoryMock->expects($this->any())->method('create')->willReturn($this->resultRedirectMock);
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->company);
        $this->company->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->company);
        $this->company->expects($this->any())->method('getData')->willReturn(['entity_id'=>0,'name'=>'Test','company_url_extention'=>'l6site51']);
        $ondemandInfo = [
            'company_data' => [
                'company_url_extention' => 'l6site51'
            ]
        ];
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandInfo);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('createPublicCookieMetadata')->willReturn($this->publicCookieMetadataMock);
		$this->publicCookieMetadataMock->expects($this->any())->method('setPath')->willReturnSelf();
		$this->publicCookieMetadataMock->expects($this->any())->method('setHttpOnly')->willReturnSelf();
		$this->publicCookieMetadataMock->expects($this->any())->method('setSecure')->willReturnSelf();
		$this->publicCookieMetadataMock->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setDuration')->willReturnSelf();
		$this->cookieManagerInterfaceMock->expects($this->any())->method('setPublicCookie')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
		
		$result = $this->autoLoginIndexTest->execute();
		$this->assertEquals($this->resultRedirectMock ,$result);
    }   
}

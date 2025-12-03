<?php

namespace Fedex\Purchaseorder\Test\Unit\Helper;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Helper\Context;
use Fedex\Purchaseorder\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class DataTest extends TestCase
{
    protected $toggleConfigMock;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $timezoneInterfaceMock;
    protected $dateMock;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Framework\Encryption\EncryptorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $encryptorInterfaceMock;
    protected $tokenFactoryMock;
    protected $tokenMock;
    protected $countryFactoryMock;
    protected $countryMock;
    protected $countryCollectionMock;
    protected $resourceConnectionMock;
    protected $loggerInterfaceMock;
    protected $regionCollectionFactoryMock;
    protected $regionCollectionMock;
    protected $quoteGridInterfaceMock;
    protected $negotiableQuoteManagementMock;
    /**
     * @var (\Fedex\Shipto\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shiptoHelperMock;
    /**
     * @var (\Magento\Integration\Model\AdminTokenService & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $adminTokenServiceMock;
    protected $quoteRepositoryMock;
    protected $quoteObjectMock;
    protected $customerModelMock;
    protected $cartInterfaceMock;
    protected $storeManagerInterface;
    protected $adapterInterfaceMock;
    protected $orderInterfaceMock;
    protected $orderMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $helperData;
    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->timezoneInterfaceMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
			->setMethods(['date'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->dateMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
			->setMethods(['gmtDate'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->scopeConfigInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->encryptorInterfaceMock = $this->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->tokenFactoryMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\TokenFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->tokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
			->setMethods(['createCustomerToken', 'getToken'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->countryFactoryMock = $this->getMockBuilder(\Magento\Directory\Model\CountryFactory::class)
			->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
			->setMethods(['getCollection', 'getCountryId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->countryCollectionMock = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Country\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->loggerInterfaceMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
			->setMethods(['info'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->regionCollectionFactoryMock = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->regionCollectionMock = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
			->setMethods(['addRegionNameFilter', 'getSize', 'getFirstItem', 'toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteGridInterfaceMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\ResourceModel\QuoteGridInterface::class)
            ->setMethods(['refreshValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->negotiableQuoteManagementMock = $this->getMockBuilder(\Fedex\B2b\Model\NegotiableQuoteManagement::class)
			->setMethods(['closed'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->shiptoHelperMock = $this->getMockBuilder(\Fedex\Shipto\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminTokenServiceMock = $this->getMockBuilder(\Magento\Integration\Model\AdminTokenService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteRepositoryMock = $this->getMockBuilder(\Magento\Quote\Model\QuoteRepository::class)
			->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->quoteObjectMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
			->setMethods(['getShippingAddress', 'getId', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->customerModelMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
			->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->cartInterfaceMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
			->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->storeManagerInterface = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getBaseUrl'])
            ->getMockForAbstractClass();

        $this->storeManagerInterface->expects($this->any())
                 ->method('getStore')->willReturnSelf();
        $this->storeManagerInterface->expects($this->any())
                 ->method('getBaseUrl')->willReturnSelf();

		$this->adapterInterfaceMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
					->setMethods(['getTableName', 'query'])
						->disableOriginalConstructor()
							->getMockForAbstractClass();
							
		$this->orderInterfaceMock = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
					->setMethods(['get'])
						->disableOriginalConstructor()
							->getMockForAbstractClass();
							
		$this->orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
					->setMethods(['setPickupAddress', 'save'])
						->disableOriginalConstructor()
							->getMock();
							
        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            Data::class,
            [
                'context'            => $this->context,
                'timezone'           => $this->timezoneInterfaceMock,
                'storeManager'       => $this->storeManagerInterface,
				'date'				 => $this->dateMock,
                'configInterface'    => $this->scopeConfigInterfaceMock,
                'encryptorInterface' => $this->encryptorInterfaceMock,
                'tokenModelFactory'  => $this->tokenFactoryMock,
                'countryFactory'     => $this->countryFactoryMock,
                'connection'         => $this->resourceConnectionMock,
                'collectionFactory'  => $this->regionCollectionFactoryMock,
                'quoteGrid'       	 => $this->quoteGridInterfaceMock,
                'negotiableQuoteManagement'       => $this->negotiableQuoteManagementMock,
                'shiptoHelper'       => $this->shiptoHelperMock,
                'adminTokenService'       => $this->adminTokenServiceMock,
                'quoteRepository'       => $this->quoteRepositoryMock,
                'orderRepository'       => $this->orderInterfaceMock,
                'toggleConfig' => $this->toggleConfigMock
            ]);
    }
    
    /**
     * Test for getPoNumber method.
     *
     * @return string
     */
    public function testGetPoNumber()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml='{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},
				"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},
				"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},
				"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com"
				,"SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan Translation Services"}},
				"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},
				"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},
				"Street":"123 Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United States"}}},
				"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},
				"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test doc", "UnitOfMeasure":"EA","Classification":"82121503",
				"Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        
        $poxml = json_decode($poxml, true);
        $expectedResult = 'po252';
        $actualResult = $this->helperData->getPoNumber($poxml);
        $this->assertEquals($expectedResult, $actualResult);
    }
    
    /**
     * Test for getActionType method.
     *
     * @return string
     */
    public function testGetActionType()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
       $poxml='{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},
				"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},
				"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},
				"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com"
				,"SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan Translation Services"}},
				"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},
				"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},
				"Street":"123 Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United States"}}},
				"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},
				"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test doc", "UnitOfMeasure":"EA","Classification":"82121503",
				"Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
       
		$poxml = json_decode($poxml,true);
		$expectedResult = 'new';
        $actualResult = $this->helperData->getActionType($poxml);
        $this->assertEquals($expectedResult, $actualResult);
    }
    
    /**
     * Test for uniqidReal method.
     *
     * @return string
     */
    public function testUniqidReal()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $length	=	16;
        $expectedResult	=	'5b4c38eb4be76333';
        
        $actualResult = $this->helperData->uniqidReal($length);
        $this->assertIsString($actualResult);
    }
    
    /**
     * Test for getAdminToken method.
     *
     * @return string|array
     */
    public function testGetAdminToken()
    {  
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$username = 'admin';
        $password = '0:3:57b1KnIoivsjoETztgLcO39loK87yHi/ItNANgjH2idUh2Gv';
       
		$actualResult = $this->helperData->getAdminToken();
        $this->assertNull($actualResult);
    }
    
    /**
     * Test for getCustomerToken method.
     *
     * @return string
     */
    public function testGetCustomerToken()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $customerId = 1;
        $this->tokenFactoryMock->expects($this->any())->method('create')->willReturn($this->tokenMock);    
        $this->tokenMock->expects($this->any())->method('createCustomerToken')->with($customerId)->willReturnSelf();    
        $this->tokenMock->expects($this->any())->method('getToken')->willReturn('57b1KnIoivsjoETztgLcO39loK87yHi');    
        
        $token = $this->helperData->getCustomerToken($customerId);
        $this->assertIsString($token); 
    }
    
    /**
     * Test for refreshCart method.
     *
     * @return array
     */
    public function testRefreshCart()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$quoteId = 1;
        $expectedResult = ['error' => 0, 'msg' => ''];
        $this->quoteRepositoryMock->expects($this->any())->method('get')->with($quoteId)->willReturn($this->cartInterfaceMock);
        $this->cartInterfaceMock->expects($this->any())->method('getId')->willReturn($quoteId);
        
        $this->loggerInterfaceMock->expects($this->any())->method('info');
        
        $actualResult = $this->helperData->refreshCart($quoteId);
        $this->assertEquals($expectedResult, $actualResult);
    }
    
    /**
     * Test for refreshCart method.
     *
     * @return array
     */
    public function testRefreshCartWithNullQuoteId()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$quoteId = 1;
		$this->quoteRepositoryMock->expects($this->any())->method('get')->with($quoteId)->willReturn($this->cartInterfaceMock);
        $this->cartInterfaceMock->expects($this->any())->method('getId')->willReturn(null);
        $expectedResult = ['error' => 1, 'msg' => 'Unable to refersh the cart']; 
        $actualResult = $this->helperData->refreshCart($quoteId);
        $this->assertEquals($expectedResult, $actualResult);
    }
    
    /**
     * Test for sendError method.
     *
     * @return string
     */
    //~ public function testSendError()
    //~ {   
		//~ $testMessage = 'Test Message';
		//~ $this->timezoneInterfaceMock->expects($this->any())->method('date')->willReturn(new \DateTime());
        //~ $result = $this->helperData->sendError($testMessage);
        //~ $this->assertIsString($result);
    //~ }
    
    /**
     * Test for sendSuccess method.
     *
     * @return string
     */
    public function testSendSuccess()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$testMessage = 'Test Message';
		$this->timezoneInterfaceMock->expects($this->any())->method('date')->willReturn(new \DateTime());
        $result = $this->helperData->sendSuccess($testMessage);
        $this->assertIsString($result);
    }
    
    /**
     * Test for getLocationID method.
     *
     * @return array
     */
    public function testGetLocationID()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$sampleAddress1 = [];
		$sampleAddress1['shipping_method'] = 'fedexshipping_PICKUP';
		$sampleAddress1['shipping_description'] = $expextedResult1 = 'Test Address';
		
		
		$sampleAddress2 = [];
        $sampleAddress2['shipping_method'] = 'NO_METHOD';
        $expextedResult2 = 0;
        $this->quoteObjectMock->method('getShippingAddress')->withConsecutive([],[])->willReturnOnConsecutiveCalls($sampleAddress1,$sampleAddress2);
		
		$actualResult1 = $this->helperData->getLocationID($this->quoteObjectMock);
        $this->assertEquals($actualResult1, $expextedResult1);
        
		$actualResult2 = $this->helperData->getLocationID($this->quoteObjectMock);
        $this->assertEquals($actualResult2, $expextedResult2);
    }
    
    /**
     * Test for getCountryCode method.
     *
     * @return String
     */
    public function testGetCountryCode()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$countryName = 'INDIA';
		$countryId = $expectedResult = 'IN';
		
		$this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
		$this->countryMock->expects($this->any())->method('getCollection')->willReturn($this->countryCollectionMock);

		$countryIteratorMock = new \ArrayIterator([1 => $this->countryMock]);
        $this->countryCollectionMock->expects($this->any())->method('getIterator')->willReturn($countryIteratorMock);
        
        $this->countryMock->expects($this->any())->method('getName')->willReturn($countryName);
        $this->countryMock->expects($this->any())->method('getCountryId')->willReturn($countryId);

		$actualResult = $this->helperData->getCountryCode($countryName);
        $this->assertEquals($actualResult, $expectedResult);
    }
    
    /**
     * Test for getRegionCode method.
     *
     * @return string[]
     */
    public function testGetRegionCode()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$regionName = 'Uttar Pradesh';
		$expectedResult = ['UP'];
		
		$this->regionCollectionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionCollectionMock);
		$this->regionCollectionMock->expects($this->any())->method('addRegionNameFilter')->willReturnSelf();
		$this->regionCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
		$this->regionCollectionMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->regionCollectionMock->expects($this->any())->method('toArray')->willReturn(['UP']);

		$actualResult = $this->helperData->getRegionCode($regionName);
        $this->assertEquals($actualResult, $expectedResult);
    }
    
    /**
     * Test for changeQuoteStatusatdelete method.
     * 
     * @return Object
     */
    public function testChangeQuoteStatusatdelete()
    {  
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1); 
		$quoteId = 1;
		$exception = new \Exception();
        
		$this->quoteRepositoryMock->expects($this->any())->method('get')->with($quoteId)->willReturn($this->cartInterfaceMock);
        $this->cartInterfaceMock->expects($this->any())->method('getId')->willReturn($quoteId);
        
		$this->quoteGridInterfaceMock->expects($this->any())->method('refreshValue')->willReturnSelf();
		
		$this->negotiableQuoteManagementMock->expects($this->any())->method('closed');
		$actualResult = $this->helperData->changeQuoteStatusatdelete($this->cartInterfaceMock);
        $this->assertNull($actualResult);
    }
    
    /**
     * Test for changeQuoteStatusatdelete method.
     * 
     * @return Object
     */
    public function testChangeQuoteStatusatdeleteWithException()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$quoteId = 1;
		$exception = new \Exception();
		$this->quoteRepositoryMock->expects($this->any())->method('get')->with($quoteId)->willReturn($this->cartInterfaceMock);
        $this->cartInterfaceMock->expects($this->any())->method('getId')->willReturn($quoteId);
        
		$this->quoteGridInterfaceMock->expects($this->any())->method('refreshValue')->willThrowException($exception);
		
		$testMessage = 'Test Message';
		$this->timezoneInterfaceMock->expects($this->any())->method('date')->willReturn(new \DateTime());
		$actualResult = $this->helperData->changeQuoteStatusatdelete($this->cartInterfaceMock);
		$this->assertIsString($actualResult);
    }
    
    /**
     * Test for updateQuoteGridData method.
     *
     * @param Int $quoteId
     */
    public function testUpdateQuoteGridData()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$quoteId = 1;
		$table = 'negotiable_quote_grid';
		$date = '2021-10-18 16:55:15';
		
		$this->dateMock->expects($this->any())->method('gmtDate')->willReturn($date);
		
        $this->resourceConnectionMock->expects($this->once())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->once())->method('getTableName')->willReturn($table);
        $this->adapterInterfaceMock->expects($this->once())->method('query');
        $actualResult = $this->helperData->updateQuoteGridData($quoteId);
        $this->assertNull($actualResult);
    }
    
    /**
     * Test for changeQuoteStatus method.
     *
     * @param Int $quoteId
     */
    public function testChangeQuoteStatus()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$this->quoteObjectMock->expects($this->any())->method('getId')->willReturn(1);
		$this->quoteObjectMock->expects($this->any())->method('getCustomer')->willReturn($this->customerModelMock);
		$this->customerModelMock->expects($this->any())->method('getId')->willReturn(1);
		
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName');//->willReturn($table);
        $this->adapterInterfaceMock->expects($this->any())->method('query');
        $actualResult = $this->helperData->changeQuoteStatus($this->quoteObjectMock);
        $this->assertNull($actualResult);
    }
    
    /**
     * Test for changeNegotiableQuoteStatus method.
     *
     * @param Int $quoteId
     */
    public function testChangeNegotiableQuoteStatus()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$quoteId = 1;
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName');//->willReturn($table);
        $this->adapterInterfaceMock->expects($this->any())->method('query');
        $actualResult = $this->helperData->changeNegotiableQuoteStatus($quoteId);
        $this->assertNull($actualResult);
    }

    /**
     * Test Method for validateShipToFieldsLength method.
     */
    public function testValidateShipToFieldsLength()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shipAddress = [
            'street' => [
                   0 => 'Rm638 FL6 Sherrie A. King-Woods new tiger sippman volting power ignition plant',
                   1 => 'street second line address form field validation properly working with great'
            ],
            'companyName' => 'Temple University Temple UniversityTemple UniversityTemple UniversityTemple UniversityTemple University',
            'firstname' => 'Rm638 FL6 Sherrie A.',
            'lastname' => 'King-Woods',
            'email' => 'testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttes@yopmail.com',
            'telephone' => 2149982754
        ];
        $this->helperData->validateShipToFieldsLength($shipAddress);
    }
    
     /**
     * Test for updatePickupLocationAddress method.
     *
     * @return String
     */
    public function testUpdatePickupLocationAddress()
    {   
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
		$tempOrderId = '23';
		$tempAddress = 'Test Address';
		
		$this->orderInterfaceMock->expects($this->any())->method('get')->willReturn($this->orderMock);
		$this->orderMock->expects($this->any())->method('setPickupAddress')->willReturnSelf();
		$this->orderMock->expects($this->any())->method('save')->willReturnSelf();

		$actualResult = $this->helperData->updatePickupLocationAddress($tempOrderId, $tempAddress);
		$this->assertNull($actualResult);
    }

    public function testGetNameFromXML()
    {
        $deliveryTo = "Mary Anne J Nonan/Mary Anne J";
        $expectedResult = [];
        $expectedResult['firstname'] = "Mary Anne J";
        $expectedResult['lastname'] = "Nonan";
        $actualResult = $this->helperData->getNameFromXML($deliveryTo);
        $this->assertEquals($expectedResult,$actualResult);

        $deliveryTo = "Cruz, Diana E";
        $expectedResult = [];
        $expectedResult['firstname'] = "Diana E";
        $expectedResult['lastname'] = "Cruz";
        $actualResult = $this->helperData->getNameFromXML($deliveryTo);
        $this->assertEquals($expectedResult,$actualResult);

        $deliveryTo = "Room 313E Floor 3";
        $expectedResult = [];
        $expectedResult['firstname'] = "Room";
        $expectedResult['lastname'] = "313E Floor 3";
        $actualResult = $this->helperData->getNameFromXML($deliveryTo);
        $this->assertEquals($expectedResult,$actualResult);

        $deliveryTo = "Room 313E";
        $expectedResult = [];
        $expectedResult['firstname'] = "Room";
        $expectedResult['lastname'] = "313E";
        $actualResult = $this->helperData->getNameFromXML($deliveryTo);
        $this->assertEquals($expectedResult,$actualResult);
    }
}

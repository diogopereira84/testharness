<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Punchout\Test\Unit\Model;

use Fedex\Punchout\Model\Customer;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Customer\Helper\Customer as CustomerHelper;

class CustomerTest extends \PHPUnit\Framework\TestCase

{
    protected $appRequestInterfaceMock;
    protected $customerFactoryMock;
    protected $customerMock;
    protected $customerCollectionMock;
    protected $customerAddressCollectionMock;
    /**
     * @var (\Magento\Framework\App\ResponseInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $appResponseInterfaceMock;
    protected $punchoutHelperMock;
    /**
     * @var (\Magento\Framework\Controller\Result\JsonFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonFactoryMock;
    protected $purchaseOrderPoMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $addressRepoInterfaceMock;
    protected $dataAddressInterfaceFactoryMock;
    protected $dataAddressInterfaceMock;
    protected $countryFactoryMock;
    protected $countryMock;
    protected $countryCollectionMock;
    protected $regionFactoryMock;
    protected $regionMock;
    protected $customerHelper;
    protected $dbSelectMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerObj;
    const CUSTOMER = 'customer';
    const EXTRINSIC = 'extrinsic';
    const BOTH = 'both';
    const CONTACT = 'contact';

    protected $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
        'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
        'rule' => array('contact' => array(0 => 'Name', 1 => 'Email'),
            'extrinsic' => array(0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname')),
        'type' => array('0' => 'extrinsic', 1 => 'contact'),
        'extra_data' => array('redirect_url' => '', 'response_url' => 'https://shop-staging2.fedex.com',
            'cookie' => 24941604898076815), 'legacy_site_name' => 'testeprosite'];
    protected $customerData = ['email' => 'test@test.in', 'firstname' => 'fname', 'lastname' => 'lname'];
    protected $xml = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo><Address><Name>vivek</Name><Email>testemail@gmail.com</Email><Phone><TelephoneNumber><AreaOrCityCode>+1</AreaOrCityCode><Number>989898989</Number></TelephoneNumber></Phone>
						<PostalAddress><DeliverTo>vivek</DeliverTo><Street>TestStreet</Street>
						<City>TestCity</City><State>TestState</State><PostalCode>222222</PostalCode><Country>TestCountry</Country></PostalAddress></Address></ShipTo></PunchOutSetupRequest></Request></cXML>';

    protected $xmlEmpty = '<?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
    <cXML xml:lang="en-US">
    </cXML>';

    protected $xmlWithDifferentInfo = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo><Address><Name>vivek</Name><Email>testemail@gmail.com</Email><Phone><TelephoneNumber><AreaOrCityCode>+1</AreaOrCityCode><Number>989898989</Number></TelephoneNumber></Phone>
						<PostalAddress><Street>Str1</Street><Street>Str2</Street>
						<City>TestCity</City><State>TestState</State><PostalCode>222222</PostalCode><Country>TestCountry</Country></PostalAddress></Address></ShipTo></PunchOutSetupRequest></Request></cXML>';

    protected $xmlWithStreet1 = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo><Address><Name>vivek</Name><Email>testemail@gmail.com</Email><Phone><TelephoneNumber><AreaOrCityCode>+1</AreaOrCityCode><Number>989898989</Number></TelephoneNumber></Phone>
						<PostalAddress><Street></Street><Street>Str2</Street>
						<City>TestCity</City><State>TestState</State><PostalCode>222222</PostalCode><Country>TestCountry</Country></PostalAddress></Address></ShipTo></PunchOutSetupRequest></Request></cXML>';

    protected $orderRequestXml = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<OrderRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo></ShipTo></OrderRequest></Request></cXML>';

    protected $verifiedCompanyResponse = [
        'status' => 'ok',
        'website_id' => 1,
        'website_url' => '',
        'group_id' => '',
        'company_id' => 1,
        'company_name' => '',
        'msg' => '',
        'store_id' => 1,
        'rule' => ['extrinsic' => ['UniqueName', 'UserEmail', 'Firstname']],
        'type' => [self::EXTRINSIC],
        'extra_data' => '',
        'legacy_site_name' => '',
    ];

    protected $nonVerifiedCompanyResponse = ['status' => 'error', 'website_id' => '', 'website_url' => '', 'group_id' => '', 'company_id' => '', 'msg' => 'Company Not Available'];
    protected $customerAddressMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->appRequestInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getContent'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['getCollection', 'getData', 'getAddresses', 'getAddressCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollectionMock = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Customer\Collection::class)
            ->setMethods(['addAttributeToSelect', 'addAttributeToFilter', 'load',
             'getSize','getSelect','where'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerAddressCollectionMock = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Address\Collection::class)
            ->setMethods(['addFieldToFilter', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerAddressMock = $this->getMockBuilder(\Magento\Customer\Model\Address::class)
            ->setMethods(['toArray', 'getStreet'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->appResponseInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setHttpResponseCode', 'sendHeaders', 'setBody', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->punchoutHelperMock = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->setMethods(['verifyCompany', 'extractCustomerData',
                'validateXmlRuleData', 'isActiveCustomer', 'getToken', 'sendToken', 'lookUpDetails',
                'throwError', 'validateCustomer', 'getCustomerNewId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->purchaseOrderPoMock = $this->getMockBuilder(\Fedex\Purchaseorder\Model\Po::class)
            ->setMethods(['getPoXml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addressRepoInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\AddressRepositoryInterface::class)
            ->setMethods(['getById', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dataAddressInterfaceFactoryMock = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataAddressInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->setMethods(['setCustomerId', 'setIsDefaultShipping', 'setFirstname', 'setLastname',
                'setCountryId', 'setRegionId', 'setCity', 'setPostcode', 'setStreet',
                'setTelephone', 'setCustomAttribute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->countryFactoryMock = $this->getMockBuilder(\Magento\Directory\Model\CountryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
            ->setMethods(['getName', 'getCountryId', 'getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->countryCollectionMock = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Country\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionFactoryMock = $this->getMockBuilder(\Magento\Directory\Model\RegionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->setMethods(['loadByCode', 'load', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerHelper = $this->getMockBuilder(CustomerHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isNewIdentifierLookUpActive', 'updateExternalIdentifier'])
            ->getMock();
        $this->dbSelectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['where'])
            ->getMock();
    
        $this->objectManager = new ObjectManager($this);

        $this->customerObj = $this->objectManager->getObject(
            Customer::class,
            [
                'request' => $this->appRequestInterfaceMock,
                'customerFactory' => $this->customerFactoryMock,
                'response' => $this->appResponseInterfaceMock,
                'helper' => $this->punchoutHelperMock,
                'jsonResultFactory' => $this->jsonFactoryMock,
                'po' => $this->purchaseOrderPoMock,
                'logger' => $this->loggerInterfaceMock,
                'addressRepository' => $this->addressRepoInterfaceMock,
                'dataAddressFactory' => $this->dataAddressInterfaceFactoryMock,
                'countryFactory' => $this->countryFactoryMock,
                'regionFactory' => $this->regionFactoryMock,
                'customerHelper' => $this->customerHelper
            ]
        );
    }

    /**
     * Test doPunchOutWithValidCompany
     **/
    public function testDoPunchOutWithValidCompany()
    {

        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $sampleLookUpDetailsResponse = ['error' => 1, 'token' => 'test_token', 'msg' => ''];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleLookUpDetailsResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(true);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')->willReturn(['error' => 1, 'msg' => 'Invalid Token']);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());

    }

    /**
     * Test doPunchOutWithInvalidToken
     **/
    public function testDoPunchOutWithInvalidToken()
    {
        $sampleResponse = ['error' => 1, 'msg' => 'error', 'unique_id' => 'test', 'email' => 'test@test.com','token'=>''];

        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(true);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')->willReturn(['error' => 1, 'msg' => 'Invalid Token']);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithInactiveCustomer
     **/
    public function testDoPunchOutWithInactiveCustomer()
    {
        $sampleResponse = ['error' => 1, 'msg' => 'error', 'unique_id' => 'test', 'email' => 'test@test.com','token'=>''];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerCollectionMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(0);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(true);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithInvalidCustomer
     **/
    public function testDoPunchOutWithInvalidCustomer()
    {
        $sampleResponse = ['error' => 1, 'msg' => 'error', 'unique_id' => 'test', 'email' => 'test@test.com','token'=>''];

        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerCollectionMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(0);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleResponse);

        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithInvalidCompany
     **/
    public function testDoPunchOutWithInvalidCompany()
    {
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->nonVerifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithOrderRequestandValidCompany
     **/
    public function testDoPunchOutWithOrderRequestandValidCompany()
    {
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->orderRequestXml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->purchaseOrderPoMock->expects($this->any())->method('getPoxml')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithOrderRequestandInValidCompany
     **/
    public function testDoPunchOutWithOrderRequestandInvalidCompany()
    {
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->orderRequestXml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->nonVerifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithInvalidXml
     **/
    public function testDoPunchOutWithInvalidXml()
    {
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn('');
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test doPunchOutWithBlankCorrectLookupDetailsResponse
     **/
    public function testDoPunchOutWithCorrectLookupDetailsResponse()
    {
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $sampleLookUpDetailsResponse = ['error' => 1, 'token' => '', 'msg' => 'Customer Email already exist','token'=>''];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleLookUpDetailsResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(true);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')->willReturn(['error' => 1, 'msg' => 'Invalid Token']);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());

    }


    /**
     * Test doPunchOutWithBlankIncorrectLookupResponse
     **/
    public function testdoPunchOutWithBlankIncorrectLookupResponse()
    {
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $sampleLookUpDetailsResponse = ['error' => 1, 'token' => '', 'msg' => 'Customer Email already exist','token'=>''];

        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->dbSelectMock);
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('lookUpDetails')->willReturn($sampleLookUpDetailsResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateCustomer')->willReturn(true);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')->willReturn(['error' => 1, 'msg' => 'Invalid Token']);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->doPunchOut());
    }

    /**
     * Test punchoutRequestWithInvalidXmlData
     **/
    public function testPunchoutRequestWithInvalidXmlData()
    {
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        //~ $xml = simplexml_load_string($cxml);
        $json = json_encode($this->xml);
        $output = json_decode($json, true);
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(0);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');
        $this->assertIsString($this->customerObj->punchoutRequest($this->verifiedCompanyResponse, $this->xml, $output));
    }

    /**
     * Test punchoutRequestWithInvalidCustomerData
     **/
    public function testPunchoutRequestWithInvalidCustomerData()
    {
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $json = json_encode($this->xml);
        $output = json_decode($json, true);
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);

        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn(['error' => 1, 'msg' => 'Invalid Email']);
        $this->punchoutHelperMock->expects($this->any())->method('throwError')->willReturn('<xml></xml>');

        $this->assertIsString($this->customerObj->punchoutRequest($this->verifiedCompanyResponse, $this->xml, $output));
    }

    /**
     * Test checkAddresses
     **/
    public function testcheckAddresses()
    {
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $xml = simplexml_load_string($this->xml);
        $json = json_encode($xml);
        $output = json_decode($json, true);

        $this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('getCollection')->willReturn($this->countryCollectionMock);

        $countryIteratorMock = new \ArrayIterator([1 => $this->countryMock]);
        $this->countryCollectionMock->expects($this->any())->method('getIterator')->willReturn($countryIteratorMock);
        $this->countryMock->expects($this->any())->method('getName')->willReturn('United States');
        $this->countryMock->expects($this->any())->method('getCountryId')->willReturn(1);

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->regionMock->expects($this->any())->method('getName')->willReturn('TestRegion');

        $addressArray = ['entity_id' => 1, 'customer_id' => 1, 'city' => 'TestCity', 'region' => 'TestRegion', 'postcode' => '222222', 'country_id' => 1];
        $this->customerAddressMock->expects($this->any())->method('toArray')->willReturn($addressArray);

        $this->customerAddressMock->method('getStreet')->withConsecutive([], [])->willReturnOnConsecutiveCalls(['TestStreet'], ['TestStreet1']);

        $this->addressRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();

        $this->addressRepoInterfaceMock->expects($this->any())->method('save');

        $this->customerMock->expects($this->any())->method('getAddressCollection')->willReturn($this->customerAddressCollectionMock);
        $this->customerAddressCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->customerAddressCollectionMock->expects($this->any())->method('getData')->willReturn([]);

        $this->dataAddressInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomAttribute')->willReturnSelf();

        $this->assertNull($this->customerObj->checkAddresses($this->customerAddressMock, $output, $this->customerMock));
        $this->assertNull($this->customerObj->checkAddresses($this->customerAddressMock, $output, $this->customerMock));
    }

    /**
     * Test checkAddresses
     **/
    public function testCheckAddressesWithDifferentXml()
    {
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xmlWithDifferentInfo);
        $xml = simplexml_load_string($this->xmlWithDifferentInfo);
        $json = json_encode($xml);
        $output = json_decode($json, true);

        $this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('getCollection')->willReturn($this->countryCollectionMock);

        $countryIteratorMock = new \ArrayIterator([1 => $this->countryMock]);
        $this->countryCollectionMock->expects($this->any())->method('getIterator')->willReturn($countryIteratorMock);
        $this->countryMock->expects($this->any())->method('getName')->willReturn('United States');
        $this->countryMock->expects($this->any())->method('getCountryId')->willReturn(1);

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->regionMock->expects($this->any())->method('getName')->willReturn('TestRegion');

        $addressArray = ['entity_id' => 1, 'customer_id' => 1, 'city' => 'TestCity', 'region' => 'TestRegion', 'postcode' => '222222', 'country_id' => 1];
        $this->customerAddressMock->expects($this->any())->method('toArray')->willReturn($addressArray);

        $this->customerAddressMock->expects($this->any())->method('getStreet')->willReturn(['str1', 'str2']);

        //~ $exception = new \Exception();
        //~ $this->addressRepoInterfaceMock->expects($this->any())->method('getById')->willThrowException($exception);
        $this->addressRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();

        $this->addressRepoInterfaceMock->expects($this->any())->method('save');

        $this->customerMock->expects($this->any())->method('getAddressCollection')->willReturn($this->customerAddressCollectionMock);
        $this->customerAddressCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->customerAddressCollectionMock->expects($this->any())->method('getData')->willReturn([]);

        $this->dataAddressInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomAttribute')->willReturnSelf();

        $this->assertNull($this->customerObj->checkAddresses($this->customerAddressMock, $output, $this->customerMock));
    }

    /**
     * Test checkAddresses
     **/
    public function testCheckAddressesWithStreet1Xml()
    {
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xmlWithStreet1);
        $xml = simplexml_load_string($this->xmlWithStreet1);
        $json = json_encode($xml);
        $output = json_decode($json, true);

        $this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('getCollection')->willReturn($this->countryCollectionMock);

        $countryIteratorMock = new \ArrayIterator([1 => $this->countryMock]);
        $this->countryCollectionMock->expects($this->any())->method('getIterator')->willReturn($countryIteratorMock);
        $this->countryMock->expects($this->any())->method('getName')->willReturn('United States');
        $this->countryMock->expects($this->any())->method('getCountryId')->willReturn(1);

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->regionMock->expects($this->any())->method('getName')->willReturn('TestRegion');

        $addressArray = ['entity_id' => 1, 'customer_id' => 1, 'city' => 'TestCity', 'region' => 'TestRegion', 'postcode' => '222222', 'country_id' => 1];
        $this->customerAddressMock->expects($this->any())->method('toArray')->willReturn($addressArray);

        $this->customerAddressMock->expects($this->any())->method('getStreet')->willReturn(['str1', 'str2']);

        $this->addressRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();

        $this->addressRepoInterfaceMock->expects($this->any())->method('save');

        $this->customerMock->expects($this->any())->method('getAddressCollection')->willReturn($this->customerAddressCollectionMock);
        $this->customerAddressCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->customerAddressCollectionMock->expects($this->any())->method('getData')->willReturn([]);

        $this->dataAddressInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomAttribute')->willReturnSelf();

        $this->assertNull($this->customerObj->checkAddresses($this->customerAddressMock, $output, $this->customerMock));
    }

    /**
     * Test saveCustomerNewAddress
     **/
    public function testSaveCustomerNewAddress()
    {
        $xml = simplexml_load_string($this->xml);
        $json = json_encode($xml);
        $output = json_decode($json, true);

        $xmlWithMultilineStreet = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo><Address><Name>vivek</Name><Email>testemail@gmail.com</Email><Phone><TelephoneNumber><AreaOrCityCode>+1</AreaOrCityCode><Number>989898989</Number></TelephoneNumber></Phone>
						<PostalAddress><DeliverTo>vivek</DeliverTo><Street>str1</Street><Street>str2</Street>
						<City>TestCity</City><State>TestState</State><PostalCode>222222</PostalCode><Country>TestCountry</Country></PostalAddress></Address></ShipTo></PunchOutSetupRequest></Request></cXML>';

        $xml1 = simplexml_load_string($xmlWithMultilineStreet);
        $json1 = json_encode($xml1);
        $output1 = json_decode($json1, true);

        $xmlWithSingleLineStreet = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo><Address><Name>vivek</Name><Email>testemail@gmail.com</Email><Phone><TelephoneNumber><AreaOrCityCode>+1</AreaOrCityCode><Number>989898989</Number></TelephoneNumber></Phone>
						<PostalAddress><DeliverTo>vivek</DeliverTo><Street></Street><Street>str2</Street>
						<City>TestCity</City><State>TestState</State><PostalCode>222222</PostalCode><Country>TestCountry</Country></PostalAddress></Address></ShipTo></PunchOutSetupRequest></Request></cXML>';

        $xml2 = simplexml_load_string($xmlWithSingleLineStreet);
        $json2 = json_encode($xml2);
        $output2 = json_decode($json2, true);

        $customerId = 1;

        $this->countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('getCollection')->willReturn($this->countryCollectionMock);

        $countryIteratorMock = new \ArrayIterator([1 => $this->countryMock]);
        $this->countryCollectionMock->expects($this->any())->method('getIterator')->willReturn($countryIteratorMock);
        $this->countryMock->expects($this->any())->method('getName')->willReturn('United States');
        $this->countryMock->expects($this->any())->method('getCountryId')->willReturn(1);

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->regionMock->expects($this->any())->method('getName')->willReturn('TestRegion');

        $addressArray = ['entity_id' => 1, 'customer_id' => 1, 'city' => 'TestCity', 'region' => 'TestRegion', 'postcode' => '222222', 'country_id' => 1];
        $this->customerAddressMock->expects($this->any())->method('toArray')->willReturn($addressArray);

        $this->customerAddressMock->expects($this->any())->method('getStreet')->willReturn(['str1', 'str2']);

        $this->addressRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();

        $this->addressRepoInterfaceMock->expects($this->any())->method('save');

        $this->customerMock->expects($this->any())->method('getAddressCollection')->willReturn($this->customerAddressCollectionMock);
        $this->customerAddressCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->customerAddressCollectionMock->expects($this->any())->method('getData')->willReturn([]);

        $this->dataAddressInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->testgetStreetIsarray();
        $this->assertNull($this->customerObj->saveCustomerNewAddress($output, $customerId));
        $this->assertNull($this->customerObj->saveCustomerNewAddress($output1, $customerId));
        $this->assertNull($this->customerObj->saveCustomerNewAddress($output2, $customerId));
    }


    /**
     * Test saveCustomerNewAddress
     **/
    public function testIsCustomerValid()
    {
        $customerArr = array(
            "foo" => "bar",
            "bar" => "foo",
        );
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $this->assertNull(
            $this->customerObj->isCustomerValid(
                $customerArr,
                $this->verifiedCompanyResponse,
                $sampleResponse
            )
        );
    }
    public function testgetStreetinfoempty()
    {
        $streetInfo = '';
        $street = [
            "Test",
            "Yogesh",
        ];

        $this->assertNotNull($this->customerObj->getStreet($streetInfo, $street));

    }
    
    public function testgetStreetIsarray()
    {
        $streetInfo = [
            "Test",
            "Yogesh",
        ];
        $street = [
            "Test",
            "Yogesh",
        ];

        $this->assertNotNull($this->customerObj->getStreet($streetInfo, $street));

    }


    public function testcustomerRegistor()
    {
        $customerData = ['email' => 'test@test.in', 'firstname' => 'fname', 'lastname' => 'lname'];

         $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
            'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
                'rule' => array('contact' => array(0 => 'Name', 1 => 'Email'),
                    'extrinsic' => array(0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname')),
                        'type' => array('0' => 'extrinsic', 1 => 'contact'),
                            'extra_data' => array(
                                'redirect_url' => '',
                                'response_url' => 'https://shop-staging2.fedex.com',
                                'cookie' => 24941604898076815),
                                'legacy_site_name' => 'testeprosite',
                                'error' => 0
                            ];

        $sampleLookUpDetailsResponse = [
            'error' => 0,
            'token' => 'TestToken',
            'msg' => ''];
        $this->punchoutHelperMock->expects($this->any())
        ->method('lookUpDetails')
        ->willReturn($sampleLookUpDetailsResponse);
        $this->assertNull($this->customerObj->customerRegistor($customerData, $verified));
    }

    public function testcustomerRegistorErrorResponse()
    {
        $customerData = ['email' => 'test@test.in', 'firstname' => 'fname', 'lastname' => 'lname'];

         $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
            'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
                'rule' => array('contact' => array(0 => 'Name', 1 => 'Email'),
                    'extrinsic' => array(0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname')),
                        'type' => array('0' => 'extrinsic', 1 => 'contact'),
                            'extra_data' =>
                            array(
                                'redirect_url' => '',
                                 'response_url' => 'https://shop-staging2.fedex.com',
                                'cookie' => 24941604898076815), 'legacy_site_name' => 'testeprosite',
                            'error' => 0];

        $sampleLookUpDetailsResponse = ['msg' => 'Customer data not found'];
        $this->punchoutHelperMock->expects($this->any())
            ->method('lookUpDetails')
            ->willReturn($sampleLookUpDetailsResponse);
        $this->assertNotNull($this->customerObj->customerRegistor($customerData, $verified));
    }

    public function testgetCustomer()
    {
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())
        ->method('verifyCompany')->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn([$this->customerMock]);
        $this->testisValidPunchoutCustomer();
        $this->assertNotNull($this->customerObj->getCustomer());
    }

    public function testgetCustomerNotEmpty()
    {
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];

        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xmlEmpty);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')
        ->willReturn($this->verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->testisValidPunchoutCustomer();
        $this->assertNotNull($this->customerObj->getCustomer());
    }

    public function testgetCustomerNotOkay()
    {
        $verifiedCompanyResponse = [
            'status' => 'error',
            'website_id' => 1,
            'website_url' => '',
            'group_id' => '',
            'company_id' => 1,
            'company_name' => '',
            'msg' => '',
            'store_id' => 1,
            'rule' => ['extrinsic' => ['UniqueName', 'UserEmail', 'Firstname']],
            'type' => [self::EXTRINSIC],
            'extra_data' => '',
            'legacy_site_name' => '',
        ];
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn([$this->customerMock]);
        $this->testisValidPunchoutCustomer();
        $this->assertNotNull($this->customerObj->getCustomer());
    }

    public function testisValidPunchoutCustomer()
    {
        $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
            'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
                'rule' => array('contact' => array(0 => 'Name', 1 => 'Email'),
                    'extrinsic' => array(0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname')),
                        'type' => array('0' => 'extrinsic', 1 => 'contact'),
                            'extra_data' =>
                            array(
                                'redirect_url' => '',
                                 'response_url' => 'https://shop-staging2.fedex.com',
                                'cookie' => 24941604898076815), 'legacy_site_name' => 'testeprosite',
                            'error' => 0];
        $customerData = ['error' => 1, 'firstname' => 'fname', 'lastname' => 'lname'];
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->assertNotNull($this->customerObj->isValidPunchoutCustomer($verified, $this->xml));
    }

    public function testisValidPunchoutCustomertrue()
    {
        $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
            'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
                'rule' => array('contact' => array(0 => 'Name', 1 => 'Email'),
                    'extrinsic' => array(0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname')),
                        'type' => array('0' => 'extrinsic', 1 => 'contact'),
                            'extra_data' =>
                            array(
                                'redirect_url' => '',
                                 'response_url' => 'https://shop-staging2.fedex.com',
                                'cookie' => 24941604898076815), 'legacy_site_name' => 'testeprosite',
                            'error' => 0];
        $customerData = ['firstname' => 'fname', 'lastname' => 'lname'];
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(0);
        $this->assertNotNull($this->customerObj->isValidPunchoutCustomer($verified, $this->xml));
    }


    public function testgetCustomerResponseCountZero()
    {
        $customerArr = [];

        $sampleResponse = [
            'error' => 0,
            'msg' => 'Success',
            'unique_id' => 'test',
            'email' => 'test@test.com',
        ];
        $sampleLookUpDetailsResponse = [ 'msg' => 'customer details not found'];
        $this->punchoutHelperMock->expects($this->any())
        ->method('lookUpDetails')
        ->willReturn($sampleLookUpDetailsResponse);
        $this->assertNotNull(
            $this->customerObj->getCustomerResponse(
                $customerArr,
                $sampleResponse,
                $this->verifiedCompanyResponse
            )
        );
    }

    public function testgetCustomerResponse()
    {
        $sampleResponse = [
            'error' => 0,
            'msg' => 'Success',
            'unique_id' => 'test',
            'email' => 'test@test.com',
        ];
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')
        ->willReturn(['error' => 0, 'token' => 'TestToken']);
        $this->punchoutHelperMock->expects($this->any())->method('sendToken')->willReturn('<xml></xml>');
        $this->assertNotNull(
            $this->customerObj->getCustomerResponse(
                [$this->customerMock],
                $sampleResponse,
                $this->verifiedCompanyResponse
            )
        );
    }

    public function testgetCustomerResponseErrorOne()
    {
        $sampleResponse = [
            'error' => 0,
            'msg' => 'Success',
            'unique_id' => 'test',
            'email' => 'test@test.com',
        ];
        $this->customerMock->expects($this->any())->method('getData')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('isActiveCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getToken')
        ->willReturn(['error' => 1, 'msg' => 'TestToken']);
        $this->punchoutHelperMock->expects($this->any())->method('sendToken')->willReturn('<xml></xml>');
        $this->assertNotNull(
            $this->customerObj->getCustomerResponse(
                [$this->customerMock],
                $sampleResponse,
                $this->verifiedCompanyResponse
            )
        );
    }

    public function testValidateCustomerObject(){
        $sampleResponse = [
            'error' => 0,
            'msg' => 'Success',
            'unique_id' => 'test',
            'email' => 'test@test.com',
        ];
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->assertNotNull(
            $this->customerObj->ValidateCustomerObject(
                $this->verifiedCompanyResponse,
                false
            )
        );
    }
    
    public function testValidateCustomerObjectError(){
        $sampleResponse = [
            'error' => 1,
            'msg' => 'Success',
            'unique_id' => 'test',
            'email' => 'test@test.com',
        ];
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->assertNotNull(
            $this->customerObj->ValidateCustomerObject(
                $this->verifiedCompanyResponse,
                false
            )
        );
    }

    public function testUpdateOldExternalIdentifer()
    {
        $verifiedCompanyResponse = [
            'status' => 'error',
            'website_id' => 1,
            'website_url' => '',
            'group_id' => '',
            'company_id' => 1,
            'company_name' => '',
            'msg' => '',
            'store_id' => 1,
            'rule' => ['extrinsic' => ['UniqueName', 'UserEmail', 'Firstname']],
            'type' => [self::EXTRINSIC],
            'extra_data' => '',
            'legacy_site_name' => '',
        ];
        $sampleResponse = ['error' => 0, 'msg' => 'Success', 'unique_id' => 'test', 'email' => 'test@test.com'];
        $this->appRequestInterfaceMock->expects($this->any())->method('getContent')->willReturn($this->xml);
        $this->punchoutHelperMock->expects($this->any())->method('verifyCompany')->willReturn($verifiedCompanyResponse);
        $this->punchoutHelperMock->expects($this->any())->method('validateXmlRuleData')->willReturn(1);
        $this->punchoutHelperMock->expects($this->any())->method('extractCustomerData')->willReturn($sampleResponse);
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('load')->willReturn($this->customerMock);
        $this->assertIsArray(
            $this->customerObj->updateOldExternalIdentifer(
                $this->verifiedCompanyResponse,
                'test@test22.com',
                [$this->customerMock]
            )
        );
    }

    /**
     * Test Case for findCustomerInEntity
     */
    public function testFindCustomerInEntity()
    {
        $externalIdentifier = "l6site51_neeraj_himkinfogaincom@nol6site51.com";
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('where')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->assertEquals($this->customerCollectionMock,
            $this->customerObj->findCustomerInEntity($externalIdentifier));
    }

    /**
     * Test Case for findCustomerInEntity With not customer found
     */
    public function testFindCustomerInEntityWithNoCustomer()
    {
        $externalIdentifier = "l6site51_neeraj_himkinfogaincom@nol6site51.com";
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('where')->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn(0);
        $this->assertEquals(false, $this->customerObj->findCustomerInEntity($externalIdentifier));
    }
}
<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block;

use Fedex\CIDPSG\Block\AccountRequest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\App\Request\Http;

/**
 * Test class for AccountRequest Block
 */
class AccountRequestTest extends TestCase
{
    protected $accountRequestMock;
    /**
     * @var AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;
        
    /**
     * @var Http $requestMock
     */
    protected $requestMock;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAllStates',
                'getBothStates',
                'getAccountTermConditionText',
                'getConfirmationPopupMessage',
                'getPegaRetryCount',
                'getAuthorizedUserPopupMessage'
            ])->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->accountRequestMock = $objectManagerHelper->getObject(
            AccountRequest::class,
            [
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'request' => $this->requestMock
            ]
        );
        $this->accountRequestMock->allStates = [
            ['label' => 'AL', 'title'=>'Alabama'],
            ['label' => 'AK', 'title'=>'Alaska'],
            ['label' => 'CA', 'title'=>'California'],
            ['label' => 'IN', 'title'=>'Indiana'],
            ['label' => 'AB', 'title'=>'Alberta'],
            ['label' => 'MB', 'title'=>'Manitoba'],
            ['label' => 'NB', 'title'=>'New Brunswick'],
            ['label' => 'NL', 'title'=>'Newfoundland']
        ];
        $this->accountRequestMock->natureOfBusinessOptions = [
            ['label' => 'Aerospace', 'title'=>'Aerospace'],
            ['label' => 'Agriculture_Forestry', 'title'=>'Agriculture Forestry'],
            ['label' => 'Automotive', 'title'=>'Automotive'],
            ['label' => 'Business_Services_Consultant', 'title'=>'Business Services'],
            ['label' => 'Communication_Carriers_Transportation_Utilities', 'title'=>'Communication Utilities'],
            ['label' => 'Computer_Manufacturer', 'title'=>'Computer Manufacturer'],
            ['label' => 'Computer_Related_Retailer_Wholesaler_Distributor', 'title'=>'Computer Wholesaler'],
            ['label' => 'Computer_services_consulting', 'title'=>'Computer consulting'],
            ['label' => 'Computer_Technology_reseller', 'title'=>'Computer Reseller'],
            ['label' => 'Construction_Architecture_Engineering', 'title'=>'Construction Engineering'],
            ['label' => 'Data_Processing_Services', 'title'=>'Data Processing'],
            ['label' => 'Education', 'title'=>'Education'],
            ['label' => 'Electronics', 'title'=>'Electronics'],
            ['label' => 'Federal_Government', 'title'=>'Federal Government'],
            ['label' => 'Financial_Services', 'title'=>'Financial Services'],
            ['label' => 'Healthcare_Health_services', 'title'=>'Healthcare Services'],
            ['label' => 'Insurance', 'title'=>'Insurance'],
            ['label' => 'Internet_Access_Providers_ISP', 'title'=>'Internet Providers'],
            ['label' => 'Legal', 'title'=>'Legal'],
            ['label' => 'Manufacturing_consumer_goods', 'title'=>'Manufacturing Goods'],
            ['label' => 'Manufacturing_Industrial', 'title'=>'Manufacturing Industrial'],
            ['label' => 'Marketing_Advertising_Entertainment', 'title'=>'Marketing Entertainment'],
            ['label' => 'Oil_Gas_Mining_Other_natural_resources', 'title'=>'Oil Gas Mining'],
            ['label' => 'Publishing_Broadcast_Media', 'title'=>'Publishing Media'],
            ['label' => 'Real_Estate', 'title'=>'Real Estate'],
            ['label' => 'Research_Development_Lab', 'title'=>'Research Lab'],
            ['label' => 'Retail_Wholesale', 'title'=>'Retail Wholesale'],
            ['label' => 'Service_Provider', 'title'=>'Service Provider'],
            ['label' => 'Software_Technology_Developer', 'title'=>'Software Developer'],
            ['label' => 'State_Local_Government', 'title'=>'Local Government'],
            ['label' => 'Travel_Hospitality_Recreation_Entertainment', 'title'=>'Travel Hospitality'],
            ['label' => 'VAR_VAD_Systems_or_Network_Integrators', 'title'=>'Network Integrators'],
            ['label' => 'Web_Development_Production', 'title'=>'Web Development'],
            ['label' => 'Wholesale_Retail_Distribution', 'title'=>'Retail Distribution']
        ];
    }

    /**
     * Test method for getAllStates
     *
     * @return void
     */
    public function testGetAllStates()
    {
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getAllStates')
            ->with('CA')
            ->willReturn($this->accountRequestMock->allStates);

        $this->assertEquals($this->accountRequestMock->allStates, $this->accountRequestMock->getAllStates('CA'));
    }

    /**
     * Test method for getBothStates for US and Canada
     *
     * @return void
     */
    public function testGetBothStates()
    {
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getBothStates')
            ->willReturn($this->accountRequestMock->allStates);

        $this->assertEquals($this->accountRequestMock->allStates, $this->accountRequestMock->getBothStates());
    }

    /**
     * Test method for getUrlParams
     *
     * @return void
     */
    public function testGetUrlParams()
    {
        $expectedResult = ['account'=>1];
        $this->requestMock
            ->expects($this->once())
            ->method('getParams')
            ->willReturn(['account'=>1]);

        $this->assertEquals($expectedResult, $this->accountRequestMock->getUrlParams());
    }

    /**
     * Test method for get account request form terms and condition text
     *
     * @return void
     */
    public function testGetAccountTermConditionText()
    {
        $termConditionTestMessage = "terms and condition text message";
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getAccountTermConditionText')
            ->willReturn($termConditionTestMessage);

        $this->assertEquals($termConditionTestMessage, $this->accountRequestMock->getAccountTermConditionText());
    }

    /**
     * Test method for getNatureOfBusinessOptions
     *
     * @return void
     */
    public function testGetNatureOfBusinessOptions()
    {
        $this->assertEquals(
            $this->accountRequestMock->natureOfBusinessOptions,
            $this->accountRequestMock->getNatureOfBusinessOptions()
        );
    }

    /**
     * Test method for getNatureOfBusinessOptions
     *
     * @return void
     */
    public function testGetConfirmationPopupMessage()
    {
        $popupTestMessage = "test popup message";
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getConfirmationPopupMessage')
            ->willReturn($popupTestMessage);

        $this->assertEquals($popupTestMessage, $this->accountRequestMock->getConfirmationPopupMessage());
    }

    /**
     * Test method for getPegaRetryCount
     *
     * @return void
     */
    public function testGetPegaRetryCount()
    {
        $retryCount = 3;
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getPegaRetryCount')
            ->willReturn(3);

        $this->assertEquals($retryCount, $this->accountRequestMock->getPegaRetryCount());
    }

    /**
     * Test method for getAuthorizedUserPopupMessage
     *
     * @return void
     */
    public function testGetAuthorizedUserPopupMessage()
    {
        $popupTestMessage = "test popup message";
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getAuthorizedUserPopupMessage')
            ->willReturn($popupTestMessage);

        $this->assertEquals($popupTestMessage, $this->accountRequestMock->getAuthorizedUserPopupMessage());
    }
}

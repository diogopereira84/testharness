<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Directory\Model\Country;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Test class for AdminConfigHelper
 */
class AdminConfigHelperTest extends TestCase
{
    protected $adminConfigHelperMock;
    public const XML_PATH_ACCOUNT_TERM_CONDITION = 'fedex/cid_psg_configuration_group/account_request_terms_condition';
    public const XML_PATH_CONFIRMATION_POPUP = 'fedex/cid_psg_configuration_group/confirmation_popup';
    public const XML_PATH_PEGA_ACCOUNT_CREATE_API = 'fedex/cid_psg_configuration_group/pega_account_create_api_url';
    public const XML_PATH_ENABLE_LOG = 'fedex/cid_psg_configuration_group/enable_log';
    public const XML_PATH_PEGA_RETRY_COUNT = 'fedex/cid_psg_configuration_group/pega_retry_count';
    public const XML_PATH_SUPPORT_TEAM_EMAIL = 'fedex/cid_psg_configuration_group/support_team_email';
    public const XML_PATH_FROM_EMAIL = 'fedex/cid_psg_configuration_group/from_email';
    public const PEGA_API_REQUEST = 'pega_api_request';
    public const PEGA_API_RESPONSE = 'pega_api_response';
    public const XML_PATH_AUTHORIZED_USER_POPUP = 'fedex/cid_psg_configuration_group/authorized_user_popup';

    /**
     * @var Country $countryMock
     */
    protected $countryMock;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var PunchoutHelper $punchoutHelperMock
     */
    protected $punchoutHelperMock;

    /**
     * @var Curl $curl
     */
    protected $curl;

    /**
     * @var TimezoneInterface $timezoneMock
     */
    protected $timezoneMock;

    /**
     * @var LoggerInterface $loggerMock
     */
    protected $loggerMock;

    /**
     * @var DataPersistorInterface $dataPersistor
     */
    protected $dataPersistor;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->countryMock = $this->getMockBuilder(Country::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCode', 'getRegions', 'loadData', 'toOptionArray'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'isSetFlag'])
            ->getMockForAbstractClass();

        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGatewayToken', 'getTazToken'])
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions', 'post'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dataPersistor = $this->getMockBuilder(DataPersistorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['set', 'get', 'clear'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->adminConfigHelperMock = $objectManagerHelper->getObject(
            AdminConfigHelper::class,
            [
                'country' => $this->countryMock,
                'scopeConfig' => $this->scopeConfigMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'logger' => $this->loggerMock,
                'curl' => $this->curl,
                'timezoneInterface' => $this->timezoneMock,
                'dataPersistor' => $this->dataPersistor
            ]
        );
        $this->adminConfigHelperMock->canadaStates = [
            ['label' => 'AB', 'title' => 'Alberta'],
            ['label' => 'BC', 'title' => 'British Columbia'],
            ['label' => 'MB', 'title' => 'Manitoba'],
            ['label' => 'NB', 'title' => 'New Brunswick'],
            ['label' => 'NL', 'title' => 'Newfoundland'],
            ['label' => 'NU', 'title' => 'Northwest Territories / Nunavut'],
            ['label' => 'NS', 'title' => 'Nova Scotia'],
            ['label' => 'ON', 'title' => 'Ontario'],
            ['label' => 'PE', 'title' => 'Prince Edward Island'],
            ['label' => 'QC', 'title' => 'Quebec'],
            ['label' => 'SK', 'title' => 'Saskatchewan'],
            ['label' => 'YT', 'title' => 'Yukon Territories']
        ];
        $this->adminConfigHelperMock->usStates = [
            ['label' => 'AL', 'title' => 'Alabama'],
            ['label' => 'AK', 'title' => 'Alaska'],
            ['label' => 'AZ', 'title' => 'Arizona'],
            ['label' => 'AR', 'title' => 'Arkansas'],
            ['label' => 'CA', 'title' => 'California']
        ];
        $this->adminConfigHelperMock->combinedStates = [
            ['label' => 'AL', 'title' => 'Alabama'],
            ['label' => 'AK', 'title' => 'Alaska'],
            ['label' => 'AZ', 'title' => 'Arizona'],
            ['label' => 'AR', 'title' => 'Arkansas'],
            ['label' => 'CA', 'title' => 'California'],
            ['label' => 'AB', 'title' => 'Alberta'],
            ['label' => 'BC', 'title' => 'British Columbia'],
            ['label' => 'MB', 'title' => 'Manitoba'],
            ['label' => 'NB', 'title' => 'New Brunswick'],
            ['label' => 'NL', 'title' => 'Newfoundland'],
            ['label' => 'NU', 'title' => 'Northwest Territories / Nunavut'],
            ['label' => 'NS', 'title' => 'Nova Scotia'],
            ['label' => 'ON', 'title' => 'Ontario'],
            ['label' => 'PE', 'title' => 'Prince Edward Island'],
            ['label' => 'QC', 'title' => 'Quebec'],
            ['label' => 'SK', 'title' => 'Saskatchewan'],
            ['label' => 'YT', 'title' => 'Yukon Territories']
        ];

        $this->adminConfigHelperMock->formData = [
            'cid_psg_country' => 'US',
            'legal_company_name' => 'company name',
            'pre_acc_name' => 'pre_acc_name',
            'charge_acc_bill_checkbox_val' => 0,
            'tax_exempt_checkbox_val' => 0,
            'tc_checkbox_val' => 1
        ];
    }

    /**
     * Test method for get CA States
     *
     * @return void
     */
    public function testGetAllStatesCA()
    {
        $this->assertEquals(
            $this->adminConfigHelperMock->canadaStates,
            $this->adminConfigHelperMock->getAllStates('CA')
        );
    }

    /**
     * Test method for getRegionsOfCountry
     *
     * @return void
     */
    public function testGetRegionsOfCountry()
    {
        $this->countryMock->expects($this->any())->method('loadByCode')->with('US')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getRegions')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('loadData')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('toOptionArray')
            ->willReturn($this->adminConfigHelperMock->usStates);
        $this->assertEquals(
            $this->adminConfigHelperMock->usStates,
            $this->adminConfigHelperMock->getRegionsOfCountry('US')
        );
    }

    /**
     * Test method for get US States
     *
     * @return void
     */
    public function testGetBothStates()
    {
        $this->countryMock->expects($this->once())->method('loadByCode')->with('US')->willReturnSelf();
        $this->countryMock->expects($this->once())->method('getRegions')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('loadData')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('toOptionArray')
            ->willReturn($this->adminConfigHelperMock->combinedStates);
        $this->assertNotEquals(
            $this->adminConfigHelperMock->combinedStates,
            $this->adminConfigHelperMock->getBothStates()
        );
    }

    /**
     * Test method for get account request form terms and condition text.
     *
     * @return void
     */
    public function testGetAccountTermConditionText()
    {
        $expectedResult = self::XML_PATH_ACCOUNT_TERM_CONDITION;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_ACCOUNT_TERM_CONDITION);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getAccountTermConditionText());
    }

    /**
     * Test method for confirmnation popup message.
     *
     * @return void
     */
    public function testGetConfirmationPopupMessage()
    {
        $expectedResult = self::XML_PATH_CONFIRMATION_POPUP;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_CONFIRMATION_POPUP);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getConfirmationPopupMessage());
    }

    /**
     * Test method for isLogEnabled.
     *
     * @return void
     */
    public function testIsLogEnabled()
    {
        $expectedResult = true;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->isLogEnabled());
    }

    /**
     * Test method for getPegaAccountCreateApiUrl.
     *
     * @return void
     */
    public function testGetPegaAccountCreateApiUrl()
    {
        $expectedResult = self::XML_PATH_PEGA_ACCOUNT_CREATE_API;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_PEGA_ACCOUNT_CREATE_API);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getPegaAccountCreateApiUrl());
    }

    /**
     * Test method for getPegaApiSupportEmail.
     *
     * @return void
     */
    public function testGetPegaApiSupportEmail()
    {
        $expectedResult = self::XML_PATH_SUPPORT_TEAM_EMAIL;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_SUPPORT_TEAM_EMAIL);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getPegaApiSupportEmail());
    }

    /**
     * Test method for getPegaSupportEmailSubject.
     *
     * @return void
     */
    public function testGetFromEmail()
    {
        $expectedResult = self::XML_PATH_FROM_EMAIL;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_FROM_EMAIL);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getFromEmail());
    }

    /**
     * Test method for getAuthorizedUserEmail
     *
     * @return void
     */
    public function testGetAuthorizedUserEmail()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn("no-reply@fedex.com");

        $this->assertNotNull($this->adminConfigHelperMock->getAuthorizedUserEmail());
    }

    /**
     * Test method for getAuthorizedEmailTemplate
     *
     * @return void
     */
    public function testGetAuthorizedEmailTemplate()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn("4");

        $this->assertNotNull($this->adminConfigHelperMock->getAuthorizedEmailTemplate());
    }

    /**
     * Test method for getPaAgreementUserEmail
     *
     * @return void
     */
    public function testGetPaAgreementUserEmail()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn("no-reply@fedex.com");

        $this->assertNotNull($this->adminConfigHelperMock->getPaAgreementUserEmail());
    }

    /**
     * Test method for getPaAgreementEmailTemplate
     *
     * @return void
     */
    public function testGetPaAgreementEmailTemplate()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn("4");

        $this->assertNotNull($this->adminConfigHelperMock->getPaAgreementEmailTemplate());
    }

    /**
     * Test method for getPegaRetryCount.
     *
     * @return void
     */
    public function testGetPegaRetryCount()
    {
        $expectedResult = self::XML_PATH_PEGA_RETRY_COUNT;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_PEGA_RETRY_COUNT);

        $this->assertNotEquals($expectedResult, $this->adminConfigHelperMock->getPegaRetryCount());
    }

    /**
     * Test method for GetValue.
     *
     * @return void
     */
    public function testGetValue()
    {
        $this->dataPersistor->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test_key'))
            ->willReturn('test_value');
 
        $result = $this->adminConfigHelperMock->getValue('test_key');

        $this->assertEquals('test_value', $result);
    }

    /**
     * Test method for GetValue.
     *
     * @return void
     */
    public function testClearValue()
    {
        $this->dataPersistor->expects($this->once())
            ->method('clear')
            ->with($this->equalTo('test_key'))
            ->willReturn('test_value');
 
        $result = $this->adminConfigHelperMock->clearValue('test_key');

        $this->assertNotEquals('test_value', $result);
    }

    /**
     * Test method for GetValue.
     *
     * @return void
     */
    public function testSetValue()
    {
        $this->dataPersistor->expects($this->once())
            ->method('set')
            ->with($this->equalTo('test_key'), $this->equalTo('test_value'));

        $result = $this->adminConfigHelperMock->setValue('test_key', 'test_value');

        $this->assertEquals(null, $result);
    }

    /**
     * Test method for authorized user popup message.
     *
     * @return void
     */
    public function testGetAuthorizedUserPopupMessage()
    {
        $expectedResult = self::XML_PATH_AUTHORIZED_USER_POPUP;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_AUTHORIZED_USER_POPUP);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getAuthorizedUserPopupMessage());
    }
}

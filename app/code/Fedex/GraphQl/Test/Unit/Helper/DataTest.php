<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use PHPUnit\Framework\TestCase;
use Fedex\GraphQl\Helper\Data as OauthHelper;

class DataTest extends TestCase
{
    // @codingStandardsIgnoreStart
    const CURRENT_DATE = '1970-01-01 00:00:00';
    const EXPIRES_AT = '1970-01-01 04:00:00';
    const JWT = 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiI1MDM0MTE4QGNvcnAuZHMuZmVkZXguY29tIiwibmJmIjoxNjcxMTM0MjU2LCJ1c2VyX25hbWUiOiI1MDM0MTE4QGNvcnAuZHMuZmVkZXguY29tIiwibmFtZSI6IkFiaGlzaGVrIFVwYWRoeWF5YSAoT1NWKSIsImV4cCI6MTY3MTEzNzg2NiwiaWF0IjoxNjcxMTM0MjY2LCJ1dWlkIjoiNTAzNDExOCIsImp0aSI6IjVkNjg5NjU3LWQzNjQtNDcwNS04ZmM3LTUxOTcwYTE2ZDdjMiIsImVtYWlsIjoiYXVwYWRoeWF5YS5vc3ZAZmVkZXguY29tIiwiYXV0aG9yaXRpZXMiOlsicHJpbnRfb25fZGVtYW5kX3Rlc3RfYWRtaW5fYXBwMzUzMTMyOSIsInByaW50X29uX2RlbWFuZF9wcm9kX3N1cHBvcnRfYXBwMzUzMTMyOSIsImZ4b190bSIsInByaW50X29uX2RlbWFuZF90ZXN0X3N1cHBvcnRfYXBwMzUzMTMyOSIsImZ4c19ydGxfcHJvZF91c2VyX2FwcDM1MzA5NDAiLCJwcmludF9vbl9kZW1hbmRfYWRtaW5fYXBwMzUzMTMyOSJdLCJlbXBsb3llZU51bWJlciI6IjUwMzQxMTgifQ.LZEzdeQ3k_HIqH4QFDliKqNjcE6hpBgxZSxGKi5YIq46KXfQfGmhEdt6cDGEHQDl5KETml_Eq8nvgLZRaE60pi3Wt4KqBTqACdVha4uOuiag72ainwHSpDxOcw_Sv97SAFtdYQh_8JA-h0U9MlthXEKCUwpz6gLwC_6kNjWS5e7zw8HDaczuk5Jq7zgFW3ndgugrTJdK806kzFdU3-JcZlnPH5gEmBp-lgNXfR3bnGQ1qzjb1Rs_oM682qIH0mBBh4R4pPkjHLrXBHopUbpCi_WK9unxvMwwfjoi82SBYS-aB-ehgyJgq30XFX9Y2-xa_lcuJbCR_lYa9OmObz7zaRIytsDj3aGfl47qAQdzpTF-a7BKUa4ShBGOmJxRfUSvSKI1UkHri4YLeM8CdZdMHgz4ZiiAKMmrXLOjJac4VP0DaNRjxfxLsRufncP4zpoiVxlboPoilWGlRjOzGlfxJBvM7K0De01FLi4ynHgXE_YFvrN09Q7AtvDUMW8uKwqVfM5nCc7yTmonFgkocoSfP4jqOg7AdNOd1u14sMA1SeWf_Oz3iNN8x3VIQooDyez-Tejd_suYsb-zVr4Z-w0HRSQl_9ZKsQkGsaygYWv_EJZXbpkZTXVVXqCtij5o2Q16ulfmweVDHBdLCY9RRj2cGDbBg8wlDJLNZ3pYbTky4Z4';
    const JWT_EMPLOYEE_NUMBER = '5034118';
    // @codingStandardsIgnoreEnd

    /**
     * @var DateTimeFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTimeFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigInterfaceMock;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sessionMock;

    /**
     * @var OauthHelper
     */
    protected OauthHelper $oauthHelper;

    public function setUp(): void
    {
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOnBehalfOf'])
            ->getMock();

        $this->oauthHelper = new OauthHelper(
            $this->dateTimeFactoryMock,
            $this->scopeConfigInterfaceMock,
            $this->sessionMock
        );
    }

    public function testGenerateAccessTokenExpirationDate()
    {
        $dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['gmtDate'])
            ->getMockForAbstractClass();

        $dateTimeMock->expects($this->any())->method('gmtDate')
            ->willReturnOnConsecutiveCalls(self::CURRENT_DATE, self::EXPIRES_AT);

        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateTimeMock);
        $this->scopeConfigInterfaceMock->expects($this->once())->method('getValue')->willReturn("4");

        $testResult = $this->oauthHelper->generateAccessTokenExpirationDate();

        $this->assertEquals(self::EXPIRES_AT, $testResult);
    }

    public function testGetJwtParamByKey()
    {
        $this->sessionMock->expects($this->once())->method('getOnBehalfOf')->willReturn(self::JWT);
        $testResult = $this->oauthHelper->getJwtParamByKey('employeeNumber');
        $this->assertEquals(self::JWT_EMPLOYEE_NUMBER, $testResult);
    }

    public function testGetJwtParamWithInvalidKey()
    {
        $this->sessionMock->expects($this->once())->method('getOnBehalfOf')->willReturn(self::JWT);
        $testResult = $this->oauthHelper->getJwtParamByKey('invalidKey');
        $this->assertNull($testResult);
    }

    public function testGetJwtParamWithEmptySession()
    {
        $this->sessionMock->expects($this->once())->method('getOnBehalfOf')->willReturn(null);
        $testResult = $this->oauthHelper->getJwtParamByKey('employeeNumber');
        $this->assertNull($testResult);
    }
}

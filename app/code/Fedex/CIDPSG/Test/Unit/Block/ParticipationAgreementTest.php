<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block;

use Fedex\CIDPSG\Block\ParticipationAgreement;
use Fedex\CIDPSG\Helper\PsgHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ParticipationAgreementTest extends TestCase
{
    /**
     * @var PsgHelper $psgHelperMock
     */
    private $psgHelperMock;

    /**
     * @var Http $requestMock;
     */
    private $requestMock;

    /**
     * @var ParticipationAgreement $participationAgreement
     */
    private $participationAgreement;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->psgHelperMock = $this->getMockBuilder(PsgHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->participationAgreement = $objectManager->getObject(
            ParticipationAgreement::class,
            [
                'psgHelper' => $this->psgHelperMock,
                'request' => $this->requestMock,
            ]
        );
    }

    /**
     * Test method for get URl Parameter
     *
     * @return void
     */
    public function testGetUrlParams()
    {
        $params = ['client_id' => '123', 'param1' => 'value1', 'param2' => 'value2'];
        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $result = $this->participationAgreement->getUrlParams();

        $this->assertEquals($params, $result);
    }

    /**
     * Test method for get PSG customer info
     *
     * @return void
     */
    public function testGetCustomerInfo()
    {
        $clientId = '123';
        $customerInfo = [];
        $this->psgHelperMock->expects($this->once())
            ->method('getPSGCustomerInfo')
            ->with($clientId)
            ->willReturn($customerInfo);

        $result = $this->participationAgreement->getCustomerInfo($clientId);

        $this->assertEquals($customerInfo, $result);
    }

    /**
     * Test method for getFieldValidationType
     *
     * @return void
     */
    public function testGetFieldValidationType()
    {
        $result = $this->participationAgreement->getFieldValidationType("email");

        $this->assertEquals("v-validate validate-email validate-length", $result);
    }

    /**
     * Test method for getParticipationCodeMaskedLast4
     */
    public function testgetParticipationCodeMaskedLast4()
    {
        $result = $this->participationAgreement->getParticipationCodeMaskedLast4("test123");

        $this->assertEquals("***t123", $result);
    }
}

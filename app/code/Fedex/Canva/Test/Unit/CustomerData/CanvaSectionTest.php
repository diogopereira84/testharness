<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\CustomerData;

use Fedex\Canva\Model\CanvaCredentials;
use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Canva\Api\Data\ConfigInterface as ModuleConfig;
use Fedex\Canva\ViewModel\PodConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Canva\CustomerData\CanvaSection;

class CanvaSectionTest extends TestCase
{
    /**
     * @var CanvaCredentials|MockObject
     */
    private $canvaCredentialsMock;
    /**
     * @var ModuleConfig|MockObject
     */
    private $moduleConfigMock;
    /**
     * @var PodConfiguration|MockObject
     */
    private $podConfigurationMock;
    /**
     * @var Session|MockObject
     */
    private Session $sessionMock;

    protected function setUp(): void
    {

        $this->moduleConfigMock = $this->getMockBuilder(ModuleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->canvaCredentialsMock = $this->getMockBuilder(CanvaCredentials::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->podConfigurationMock = $this->getMockBuilder(PodConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClientId', 'getCanvaAccessToken'])
            ->getMock();
    }

    /**
     * @param $userToken
     * @param $clientId
     * @param $partnerId
     * @param $partnershipSdkUrl
     * @param $designId
     * @dataProvider getSectionDataProvider
     */
    public function testGetSectionData($userToken, $clientId, $partnerId, $partnershipSdkUrl, $designId)
    {
        $this->canvaCredentialsMock->expects($this->once())
            ->method('fetchSectionData');
        $this->moduleConfigMock->expects($this->once())
            ->method('getPartnerId')->willReturn($partnerId);
        $this->moduleConfigMock->expects($this->once())
            ->method('getPartnershipSdkUrl')->willReturn($partnershipSdkUrl);
        $this->podConfigurationMock->expects($this->once())
            ->method('getDesignId')->willReturn($designId);
        $this->sessionMock->expects($this->once())
            ->method('getCanvaAccessToken')->willReturn($userToken);
        $this->sessionMock->expects($this->once())
            ->method('getClientId')->willReturn($clientId);

        $canvaSection = (new ObjectManager($this))->getObject(CanvaSection::class, [
            'canvaCredentials' => $this->canvaCredentialsMock,
            'moduleConfig' => $this->moduleConfigMock,
            'pod' => $this->podConfigurationMock,
            'customerSession' => $this->sessionMock
        ]);
        $sectionData = $canvaSection->getSectionData();
        $this->assertEquals([
            'partnerId' => $partnerId,
            'partnershipSdkUrl' => $partnershipSdkUrl,
            'userToken' => $userToken,
            'clientId' => $clientId,
            'designId' => $designId
        ], $sectionData);
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getSectionDataProvider(): array
    {
        return [
            [null, null, null, '', ''],
            ['', '', '', '', ''],
            [
                '{"transactionId":"14a1373a-8152-41fd-9234-703995b31c41","output":{"userTokenDetail":{"accessToken":null,"clientId":null,"expirationDateTime":"2022-03-01T05:21:45.936+0000"}}}',
                '',
                '',
                '',
                ''
            ],
            [
                '{"transactionId":"14a1373a-8152-41fd-9234-703995b31c41","output":{"userTokenDetail":{"accessToken":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2NDYwNjg5NjUsInN1YiI6IjAxZmI5N2U0LWM4ZjYtNDQ0Ni05YTk5LThhNjBmZmQxMmJhZCIsImlzcyI6Im1vdUoyYmxGRHdIbjgzaXRFcVFyYzh0SCIsImV4cCI6MTY0NjExMjEwNX0.CFQvW4PianCDVnbC1K5Za-F63LR8esW9AihQtxFT3DM","clientId":"mouJ2blFDwHn83itEqQrc8tH","expirationDateTime":"2022-03-01T05:21:45.936+0000"}}}',
                '',
                '',
                '',
                ''
            ],
        ];
    }
}

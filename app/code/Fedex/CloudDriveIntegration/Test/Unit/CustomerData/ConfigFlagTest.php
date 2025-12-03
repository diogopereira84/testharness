<?php
/**
 * @category    Fedex
 * @package     Fedex_CloudDriveintegration
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CloudDriveIntegration\Test\Unit\CustomerData;

use Fedex\CloudDriveIntegration\Helper\Data as ModuleConfig;
use Fedex\CloudDriveIntegration\CustomerData\ConfigFlag;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigFlagTest extends TestCase
{
    protected $companyMock;
    protected $companyFactoryMock;
    /**
     * @var ConfigFlag
     */
    private ConfigFlag $data;

    /**
     * @var ModuleConfig|MockObject
     */
    private ModuleConfig $moduleConfigMock;

    /**
     * @var CustomerSession|MockObject
     */
    private CustomerSession $customerSessionMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private CompanyRepositoryInterface $companyRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->moduleConfigMock = $this->createMock(ModuleConfig::class);
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['getOndemandCompanyInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepositoryMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->companyMock = $this->createMock(Company::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->companyFactoryMock = $this->createMock(CompanyFactory::class);
        $this->companyFactoryMock->method('create')->willReturn($this->companyMock);

        $this->data = new ConfigFlag(
            $this->moduleConfigMock,
            $this->customerSessionMock,
            $this->companyRepositoryMock,
            $this->companyMock,
            $this->companyFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetSectionData(): void
    {
        $this->companyMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->customerSessionMock->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(['company_id' => 1]);
        $this->companyMock->expects($this->any())
            ->method('getData')
            ->with($this->logicalOr(
                $this->equalTo('box_enabled'),
                $this->equalTo('dropbox_enabled'),
                $this->equalTo('google_enabled'),
                $this->equalTo('microsoft_enabled')
            ))
            ->willReturnCallback(function($key) {
                return 1;
            });
        $this->companyRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->companyMock);

        $this->moduleConfigMock->expects($this->any())->method('isEnabled')->willReturn(1);
        $this->moduleConfigMock->expects($this->any())->method('isBoxEnabled')->willReturn(1);
        $this->moduleConfigMock->expects($this->any())->method('isDropboxEnabled')->willReturn(1);
        $this->moduleConfigMock->expects($this->any())->method('isGoogleEnabled')->willReturn(1);
        $this->moduleConfigMock->expects($this->any())->method('isMicrosoftEnabled')->willReturn(1);
        $data = [
            'enableCloudDrives' => 1,
            'enableBox' => 1,
            'enableDropbox' => 1,
            'enableGoogleDrive' => 1,
            'enableMicrosoftOneDrive' => 1,
        ];
        $this->assertEquals($data, $this->data->getSectionData());
    }
}

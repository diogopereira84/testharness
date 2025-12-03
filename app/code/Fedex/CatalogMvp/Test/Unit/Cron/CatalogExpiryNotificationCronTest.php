<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\CatalogExpiryNotificationCron;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class CatalogExpiryNotificationCronTest extends TestCase
{
    protected $logger;
    protected $toggleConfig;
    protected $catalogDocumentRefranceApiHelper;
    protected $emailHelper;
    protected $date;
    protected $cron;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->catalogDocumentRefranceApiHelper = $this->createMock(CatalogDocumentRefranceApi::class);
        $this->emailHelper = $this->createMock(EmailHelper::class);
        $this->date = $this->createMock(TimezoneInterface::class);

        $this->cron = new CatalogExpiryNotificationCron(
            $this->logger,
            $this->toggleConfig,
            $this->catalogDocumentRefranceApiHelper,
            $this->emailHelper,
            $this->date
        );
    }

    public function testExecuteToggleOff()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(false);

        $this->catalogDocumentRefranceApiHelper->expects($this->never())
            ->method('getExpiryDocuments');

        $this->emailHelper->expects($this->never())
            ->method('sendCatalogExpirationEmail');

        $this->cron->execute();
    }

    public function testExecuteToggleOnNoDocuments()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $this->catalogDocumentRefranceApiHelper->method('getExpiryDocuments')
            ->willReturn([]);

        $this->emailHelper->expects($this->never())
            ->method('sendCatalogExpirationEmail');

        $this->cron->execute();
    }

    public function testExecuteToggleOnWithDocuments()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $expiryDocuments = [
            [
                'user_id' => 1,
                'company_id' => 1,
                'expiration_date' => '12/01/2024',
                'company_url_extention' => 'test',
                'category_url_path' => 'mg-browse-catalog',
                'name' => 'Test Document1'
            ]
        ];

        $this->catalogDocumentRefranceApiHelper->method('getExpiryDocuments')
            ->willReturn($expiryDocuments);

        $dateTime = new \DateTime('2024-12-01');
        $formattedDate = $dateTime->format('m/d/Y');

        $this->date->method('date')
            ->willReturn($dateTime);

        $this->emailHelper->expects($this->once())
            ->method('sendCatalogExpirationEmail')
            ->with($this->callback(function ($data) use ($formattedDate) {
                return isset($data['catalogExpirationData'][0]['catalog_expiration_date'])
                    && $data['catalogExpirationData'][0]['catalog_expiration_date'] === $formattedDate;
            }))
            ->willReturn(true);

        $this->logger->expects($this->any())
            ->method('info')
            ->with($this->stringContains('Catalog Expiration Email Sent Successfully'));

        $this->cron->execute();
    }

    public function testGetFolderPath()
    {
        $folderPath = 'mg-browse-catalog';
        $urlExtention = 'test';

        $expectedPath = 'mg-browse-catalog';
        $actualPath = $this->cron->getFolderPath($folderPath, $urlExtention);

        $this->assertNotNull($actualPath);
    }

    public function testExecuteWithException()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $this->catalogDocumentRefranceApiHelper->method('getExpiryDocuments')
            ->willReturn([
                [
                    'user_id' => 1,
                    'company_id' => 1,
                    'expiration_date' => '2024-08-01',
                    'company_url_extention' => 'company1',
                    'category_url_path' => 'test',
                    'name' => 'Test Document'
                ]
            ]);
        $this->emailHelper->method('sendCatalogExpirationEmail')
            ->willThrowException(new \Exception('Failed to send email'));
        $this->logger->expects($this->once())
            ->method('error');

        $this->cron->execute();
    }
}

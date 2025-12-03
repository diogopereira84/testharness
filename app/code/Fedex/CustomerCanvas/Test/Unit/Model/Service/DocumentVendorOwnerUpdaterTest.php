<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Service\DocumentVendorOwnerUpdater;
use Fedex\CustomerCanvas\Model\Service\DocumentExtractor;
use Fedex\CustomerCanvas\Model\Service\HttpFedexClient;
use Fedex\FXOCMConfigurator\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DocumentVendorOwnerUpdaterTest extends TestCase
{
    /** @var DocumentExtractor|MockObject */
    private $extractorMock;

    /** @var HttpFedexClient|MockObject */
    private $clientMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var Data|MockObject */
    private $helperMock;

    /** @var ScopeConfigInterface|MockObject */
    private $configMock;

    /** @var DocumentVendorOwnerUpdater */
    private $updater;

    protected function setUp(): void
    {
        $this->extractorMock = $this->createMock(DocumentExtractor::class);
        $this->clientMock = $this->createMock(HttpFedexClient::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->configMock = $this->createMock(ScopeConfigInterface::class);

        $this->updater = new DocumentVendorOwnerUpdater(
            $this->extractorMock,
            $this->clientMock,
            $this->loggerMock,
            $this->helperMock,
            $this->configMock
        );
    }

    public function testUpdateVendorOwnerIdSuccess(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $documents = [['documentId' => 'DOC123']];
        $this->extractorMock->method('extract')->willReturn($documents);

        $this->configMock->method('getValue')->willReturn('https://api.test/documents');
        $this->helperMock->method('getFxoCMClientId')->willReturn('testAuthToken');

        // Expect one successful PUT call
        $this->clientMock->expects($this->once())
            ->method('putWithRetry')
            ->with(
                'https://api.test/documents/DOC123/vendorownerid',
                '"owner123"',
                'testAuthToken'
            )
            ->willReturn(true);

        $result = $this->updater->updateVendorOwnerId($quoteMock, 'owner123');
        $this->assertTrue($result);
    }

    public function testThrowsExceptionWhenNoDocumentsFound(): void
    {
        $this->expectException(LocalizedException::class);
        $this->extractorMock->method('extract')->willReturn([]);
        $quoteMock = $this->createMock(Quote::class);

        $this->updater->updateVendorOwnerId($quoteMock, 'owner123');
    }

    public function testThrowsExceptionWhenBaseUrlMissing(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->extractorMock->method('extract')->willReturn([['documentId' => 'DOC123']]);

        $this->configMock->method('getValue')->willReturn(''); // no URL
        $this->helperMock->method('getFxoCMClientId')->willReturn('token123');

        $this->expectException(LocalizedException::class);
        $this->updater->updateVendorOwnerId($quoteMock, 'vendor123');
    }

    public function testThrowsExceptionWhenAuthTokenMissing(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->extractorMock->method('extract')->willReturn([['documentId' => 'DOC123']]);

        $this->configMock->method('getValue')->willReturn('https://api.test');
        $this->helperMock->method('getFxoCMClientId')->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->updater->updateVendorOwnerId($quoteMock, 'vendor123');
    }

    public function testSkipsInvalidDocumentStructure(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $documents = [
            ['documentId' => ''], // invalid
            'invalidString',      // invalid type
            ['documentId' => 'DOC1'] // valid
        ];

        $this->extractorMock->method('extract')->willReturn($documents);
        $this->configMock->method('getValue')->willReturn('https://api.test');
        $this->helperMock->method('getFxoCMClientId')->willReturn('token123');

        $this->clientMock->expects($this->once())
            ->method('putWithRetry')
            ->with(
                'https://api.test/DOC1/vendorownerid',
                '"owner123"',
                'token123'
            )
            ->willReturn(true);

        $this->loggerMock->expects($this->atLeastOnce())->method('error');

        $result = $this->updater->updateVendorOwnerId($quoteMock, 'owner123');
        $this->assertTrue($result);
    }

    public function testThrowsExceptionWhenClientFails(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->extractorMock->method('extract')->willReturn([['documentId' => 'DOC123']]);

        $this->configMock->method('getValue')->willReturn('https://api.test');
        $this->helperMock->method('getFxoCMClientId')->willReturn('token123');

        $this->clientMock->method('putWithRetry')->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->updater->updateVendorOwnerId($quoteMock, 'vendor123');
    }

    public function testThrowsExceptionWhenProcessDocumentThrows(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->extractorMock->method('extract')->willReturn([['documentId' => 'DOC999']]);
        $this->configMock->method('getValue')->willReturn('https://api.test');
        $this->helperMock->method('getFxoCMClientId')->willReturn('token123');

        // Simulate client throwing exception
        $this->clientMock->method('putWithRetry')->willThrowException(new \RuntimeException('Network error'));

        $this->expectException(\RuntimeException::class);
        $this->updater->updateVendorOwnerId($quoteMock, 'ownerX');
    }
}

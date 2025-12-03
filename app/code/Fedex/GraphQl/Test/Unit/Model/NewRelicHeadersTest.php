<?php
namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\GraphQl\Service\CheckLogEnabledForMutation;
use Magento\NewRelicReporting\Model\NewRelicWrapper;
use Magento\Framework\Webapi\Rest\Request;
use Fedex\GraphQl\Model\NewRelicHeaders;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NewRelicHeadersTest extends TestCase
{
    private $newRelicWrapper;
    private $checkLogEnabledForMutation;
    private $request;
    private $logger;
    private $instoreConfig;
    private $newRelicHeaders;

    protected function setUp(): void
    {
        $this->newRelicWrapper = $this->createMock(NewRelicWrapper::class);
        $this->request = $this->createMock(Request::class);
        $this->checkLogEnabledForMutation = $this->createMock(CheckLogEnabledForMutation::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);

        $this->newRelicHeaders = new NewRelicHeaders(
            $this->newRelicWrapper,
            $this->request,
            $this->checkLogEnabledForMutation,
            $this->logger,
            $this->instoreConfig
        );
    }

    public function testGetHeadersWhenLoggingDisabled(): void
    {
        $this->instoreConfig
            ->method('isLoggingToNewrelicEnabled')
            ->willReturn(false);

        $result = $this->newRelicHeaders->getHeaders();

        $this->assertEmpty($result);
    }

    public function testGetHeadersWhenLoggingEnabledWithValidHeaders(): void
    {
        $this->instoreConfig
            ->method('isLoggingToNewrelicEnabled')
            ->willReturn(true);

        $this->request
            ->method('getHeaders')
            ->willReturn([
                new class {
                    public function toString() { return 'X-Test-Header: Value'; }
                },
                new class {
                    public function toString() { return 'Another-Header: AnotherValue'; }
                }
            ]);

        $this->instoreConfig
            ->method('headersLoggedToNewrelic')
            ->willReturn(['x-test-header', 'another-header']);

        $this->newRelicWrapper
            ->expects($this->exactly(2))
            ->method('addCustomParameter')
            ->withConsecutive(
                ['X-Test-Header', 'Value'],
                ['Another-Header', 'AnotherValue']
            );

        $result = $this->newRelicHeaders->getHeaders();

        $expected = [
            'X-Test-Header' => 'Value',
            'Another-Header' => 'AnotherValue'
        ];

        $this->assertEquals($expected, $result);
    }

    public function testAddCustomParamToNewRelicLogsWithException(): void
    {
        $this->instoreConfig
            ->method('isLoggingToNewrelicEnabled')
            ->willReturn(true);

        $this->request
            ->method('getHeaders')
            ->willThrowException(new \Exception('Request error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Unable to log to Newrelic: Request error'));

        $result = $this->newRelicHeaders->getHeaders();

        $this->assertEmpty($result);
    }
}

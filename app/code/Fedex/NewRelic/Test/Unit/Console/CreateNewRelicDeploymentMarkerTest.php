<?php
/**
 * @category  Fedex
 * @package   Fedex_NewRelic
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\NewRelic\Test\Unit\Console;

use DateTime;
use ReflectionException;
use Psr\Log\LoggerInterface;
use Laminas\Http\Response;
use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Fedex\NewRelic\Api\ConfigProviderInterface;
use Fedex\NewRelic\Console\CreateNewRelicDeploymentMarker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateNewRelicDeploymentMarkerTest extends TestCase
{
    /**
     * Command name
     */
    private const NAME = "fedex:newrelic:create-deployment-marker";

    /**
     * Command description
     */
    private const DESCRIPTION = "Creates a new New Relic Deployment Marker.";

    /**
     * @var ConfigProviderInterface|MockObject
     */
    protected ConfigProviderInterface|MockObject $configProviderMock;

    /**
     * @var LaminasClientFactory|MockObject
     */
    protected LaminasClientFactory|MockObject $laminasClientFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    protected DateTime|MockObject $dateTimeMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected TimezoneInterface|MockObject $timezoneMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected LoggerInterface|MockObject $loggerMock;

    /**
     * @var TypeListInterface|MockObject
     */
    protected TypeListInterface|MockObject $typeListMock;

    /**
     * @var InputInterface|MockObject
     */
    protected InputInterface|MockObject $inputMock;

    /**
     * @var OutputInterface|MockObject
     */
    protected OutputInterface|MockObject $outputMock;

    /**
     * @var LaminasClient|MockObject
     */
    private LaminasClient|MockObject $httpClientMock;

    /**
     * @var Response|MockObject
     */
    private Response|MockObject $responseMock;

    /**
     * @var CreateNewRelicDeploymentMarker
     */
    protected CreateNewRelicDeploymentMarker $deploymentMarker;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->laminasClientFactoryMock = $this->getMockBuilder(
            LaminasClientFactory::class
        )
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->timezoneMock = $this->getMockForAbstractClass(
            TimezoneInterface::class
        );
        $this->timezoneMock->method('date')->willReturn($this->dateTimeMock);
        $this->loggerMock = $this->getMockForAbstractClass(
            LoggerInterface::class
        );
        $this->configProviderMock = $this->getMockForAbstractClass(
            ConfigProviderInterface::class
        );
        $this->typeListMock = $this->getMockForAbstractClass(
            TypeListInterface::class
        );
        $this->inputMock = $this->getMockForAbstractClass(
            InputInterface::class
        );
        $this->outputMock = $this->getMockForAbstractClass(
            OutputInterface::class
        );
        $this->httpClientMock = $this->createMock(LaminasClient::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->deploymentMarker = new CreateNewRelicDeploymentMarker(
            $this->configProviderMock,
            $this->laminasClientFactoryMock,
            $this->timezoneMock,
            $this->loggerMock,
            $this->typeListMock,
        );
    }

    /**
     * Test method configure
     *
     * @return void
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $configureMethod = new \ReflectionMethod(
            CreateNewRelicDeploymentMarker::class,
            'configure',
        );
        $configureMethod->setAccessible(true);

        $configureMethod->invoke($this->deploymentMarker);

        $this->assertEquals(self::NAME, $this->deploymentMarker->getName());
        $this->assertEquals(self::DESCRIPTION, $this->deploymentMarker->getDescription());
    }

    /**
     * Test method execute when success
     *
     * @return void
     * @throws ReflectionException
     */
    public function testExecuteSuccess(): void
    {
        $configureMethod = new \ReflectionMethod(
            CreateNewRelicDeploymentMarker::class,
            'execute',
        );
        $configureMethod->setAccessible(true);
        $this->configProviderMock
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('some_key');
        $this->configProviderMock
            ->expects($this->once())
            ->method('getAppIdentifier')
            ->willReturn('some_app_identifier');
        $this->configProviderMock
            ->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('some_api_URL');
        $this->configProviderMock
            ->expects($this->once())
            ->method('canPerformDeploymentMarker')
            ->willReturn(true);
        $this->loggerMock
            ->expects($this->exactly(4))
            ->method('info');
        $this->laminasClientFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->httpClientMock);
        $this->httpClientMock
            ->expects($this->once())
            ->method('setUri')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setMethod')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setHeaders')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setParameterPost')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($this->responseMock);
        $this->configProviderMock
            ->expects($this->once())
            ->method('resetFields')
            ->willReturnSelf();
        $this->typeListMock
            ->expects($this->once())
            ->method('cleanType')
            ->with('config');

        $result = $configureMethod->invoke(
            $this->deploymentMarker,
            $this->inputMock,
            $this->outputMock
        );

        $this->assertEquals(self::NAME, $this->deploymentMarker->getName());
        $this->assertEquals(0, $result);
    }

    /**
     * Test method execute when failure
     *
     * @return void
     * @throws ReflectionException
     */
    public function testExecuteFailure(): void
    {
        $configureMethod = new \ReflectionMethod(
            CreateNewRelicDeploymentMarker::class,
            'execute',
        );
        $configureMethod->setAccessible(true);
        $this->configProviderMock
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('some_key');
        $this->configProviderMock
            ->expects($this->once())
            ->method('getAppIdentifier')
            ->willReturn('some_app_identifier');
        $this->configProviderMock
            ->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('some_api_URL');
        $this->configProviderMock
            ->expects($this->once())
            ->method('canPerformDeploymentMarker')
            ->willReturn(true);
        $this->loggerMock
            ->expects($this->exactly(3))
            ->method('info');
        $this->laminasClientFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->httpClientMock);
        $this->httpClientMock
            ->expects($this->once())
            ->method('setUri')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setMethod')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setHeaders')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('setParameterPost')
            ->willReturnSelf();
        $this->httpClientMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException("Some error"));
        $this->loggerMock
            ->expects($this->once())
            ->method('error');
        $this->configProviderMock
            ->expects($this->once())
            ->method('resetFields')
            ->willReturnSelf();
        $this->typeListMock
            ->expects($this->once())
            ->method('cleanType')
            ->with('config');

        $result = $configureMethod->invoke(
            $this->deploymentMarker,
            $this->inputMock,
            $this->outputMock
        );

        $this->assertEquals(self::NAME, $this->deploymentMarker->getName());
        $this->assertEquals(0, $result);
    }
}

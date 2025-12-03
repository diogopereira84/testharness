<?php
/**
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Renjith Raveendran <iago.lima.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Model\SalesForce;
use Fedex\Customer\Model\SalesForce\Source\SubscriberDataSource;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SalesForceTest extends TestCase
{

    protected $streamInterfaceMock;
    /**
     * @var (\GuzzleHttp\Exception\GuzzleException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $guzzleExpectionMock;
    protected $salesForceCustomerSubscriberInterfaceMock;
    /**
     * @var SalesForce
     */
    protected SalesForce $salesForceMock;

    /**
     * @var ResponseFactory|MockObject
     */
    protected ResponseFactory $responseFactoryMock;

    /**
     * @var ClientFactory|MockObject
     */
    protected ClientFactory $clientFactoryMock;

    /**
     * @var Client|MockObject
     */
    protected Client $clientMock;

    /**
     * @var Response|MockObject
     */
    protected Response $responseMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected LoggerInterface $loggerMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected ConfigInterface $configMock;

    /**
     * @var JsonValidator|MockObject
     */
    protected JsonValidator $jsonValidatorMock;

    /**
     * @var Json|MockObject
     */
    protected Json $jsonMock;

    /**
     * @var SubscriberDataSource|MockObject
     */
    protected SubscriberDataSource $subscriberDataSourceMock;

    /**
     * @var SalesForceResponseInterface|MockObject
     */
    protected SalesForceResponseInterface $salesForceResponseMock;

    public function setUp(): void
    {
        $this->responseFactoryMock = $this->createMock(ResponseFactory::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->streamInterfaceMock = $this->createMock(StreamInterface::class);
        $this->guzzleExpectionMock = $this->createMock(GuzzleException::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->jsonValidatorMock = $this->createMock(JsonValidator::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->subscriberDataSourceMock = $this->createMock(SubscriberDataSource::class);
        $this->salesForceResponseMock = $this->createMock(SalesForceResponseInterface::class);
        $this->salesForceCustomerSubscriberInterfaceMock = $this->createMock(SalesForceCustomerSubscriberInterface::class);

        $this->salesForceMock = new SalesForce(
            $this->responseFactoryMock,
            $this->clientFactoryMock,
            $this->loggerMock,
            $this->configMock,
            $this->jsonValidatorMock,
            $this->jsonMock,
            $this->subscriberDataSourceMock,
            $this->salesForceResponseMock,
        );
    }

    /**
     * @return void
     */
    public function testSubscribe(): void
    {
        $APIEndPoint = 'https://page.message.fedex.com/api-subscribe-fxo';
        $requestBodyFunctionParam = '{"emailAddress": "avo@salesforce.com.br","countryCode": "US","languageCode": "EN","firstName": "Albert","lastName": "Vo","companyName": "Salesforce","streetAddress": "1234 Salesforce Rd.","cityName": "Plano","stateProvince": "TX","postalCode": "75074"}';
        $requestBody = [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'body' => '{"emailAddress": "avo@salesforce.com.br","countryCode": "US","languageCode": "EN","firstName": "Albert","lastName": "Vo","companyName": "Salesforce","streetAddress": "1234 Salesforce Rd.","cityName": "Plano","stateProvince": "TX","postalCode": "75074"}'
        ];
        $requestBodyJson = '{"headers":{"Accept": "application/json","Content-Type": "application/json"},"body": "{\"emailAddress\": \"avo@salesforce.com.br\",\"countryCode\": \"US\",\"languageCode\": \"EN\",\"firstName\": \"Albert\",\"lastName\": \"Vo\",\"companyName\": \"Salesforce\",\"streetAddress\": \"1234 Salesforce Rd.\",\"cityName\": \"Plano\",\"stateProvince\": \"TX\",\"postalCode\": \"75074\"}"}';
        $requestTrue = '{"request":true}';
        $marketingOptInArray = [
            "emailAddress" => "avo@salesforce.com.br",
            "countryCode" => "US",
            "languageCode" => "EN",
            "firstName" => "Albert",
            "lastName" => "Vo",
            "companyName" => "Salesforce",
            "streetAddress" => "1234 Salesforce Rd.",
            "cityName" => "Plano",
            "stateProvince" => "TX",
            "postalCode" => "75074"
        ];

        $this->configMock->expects($this->once())->method('isMarketingOptInEnabled')->willReturn(true);
        $this->configMock->expects($this->once())->method('getMarketingOptInApiUrl')
            ->willReturn($APIEndPoint);

        $this->jsonValidatorMock->expects($this->once())->method('isValid')
            ->with($requestTrue)->willReturn(true);
        $this->jsonMock->expects($this->once())->method('unserialize')
            ->with($requestTrue)->willReturn(['request' => true]);
        $this->jsonMock->expects($this->atMost(2))->method('serialize')
            ->withConsecutive([$marketingOptInArray], [$requestBody])
            ->willReturnOnConsecutiveCalls($requestBodyFunctionParam, $requestBodyJson);

        $this->streamInterfaceMock->expects($this->once())->method('getContents')
            ->willReturn($requestTrue);

        $this->responseMock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getBody')
            ->willReturn($this->streamInterfaceMock);

        $this->clientMock->expects($this->once())->method('request')
            ->with(Request::HTTP_METHOD_POST, $APIEndPoint, $requestBody)->willReturn($this->responseMock);

        $this->clientFactoryMock->expects($this->once())->method('create')->willReturn($this->clientMock);

        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getFirstName')->willReturn('Albert');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getLastName')->willReturn('Vo');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getEmailAddress')->willReturn('avo@salesforce.com.br');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getPostalCode')->willReturn('75074');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getCityName')->willReturn('Plano');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getStateProvince')->willReturn('TX');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getStreetAddress')->willReturn('1234 Salesforce Rd.');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getCountryCode')->willReturn('US');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getLanguageCode')->willReturn('EN');
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())
            ->method('getCompanyName')->willReturn('Salesforce');

        $return = $this->salesForceMock->subscribe($this->salesForceCustomerSubscriberInterfaceMock);
        $this->assertInstanceOf(SalesForceResponseInterface::class, $return);
    }

    /**
     * @return void
     */
    public function testSubscribeDisabledMarketingOptIn(): void
    {
        $this->configMock->expects($this->once())->method('isMarketingOptInEnabled')->willReturn(false);
        $this->assertInstanceOf(SalesForceResponseInterface::class, $this->salesForceMock->subscribe($this->salesForceCustomerSubscriberInterfaceMock));
    }
}

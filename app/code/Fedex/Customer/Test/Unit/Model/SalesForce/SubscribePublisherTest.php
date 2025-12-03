<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Model\SalesForce\SubscribePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * SubscribePublisherTest Model
 */
class SubscribePublisherTest extends TestCase
{
    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var SalesForceCustomerSubscriberInterface|MockObject
     */
    private $salesForceCustomerSubscriberInterfaceMock;

    /**
     * @var SalesForceResponseInterface|MockObject
     */
    private $salesForceResponseInterfaceMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerInterfaceMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var SubscribePublisher $subscribePublisher
     */
    protected $subscribePublisher;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->salesForceCustomerSubscriberInterfaceMock = $this->createMock(SalesForceCustomerSubscriberInterface::class);
        $this->salesForceResponseInterfaceMock = $this->createMock(SalesForceResponseInterface::class);
        $this->loggerInterfaceMock = $this->createMock(LoggerInterface::class);
        $this->jsonMock = $this->createMock(Json::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->subscribePublisher = $objectManagerHelper->getObject(
            SubscribePublisher::class,
            [
                'publisher' => $this->publisherMock,
                'salesForceCustomerSubscriberInterface' => $this->salesForceCustomerSubscriberInterfaceMock,
                'logger' => $this->loggerInterfaceMock,
                'json' => $this->jsonMock
            ]
        );
    }

    public function testExecute()
    {
        $marketingOptInString = '{"emailAddress":"avo@salesforce.com.br","countryCode":"US","languageCode":"EN","firstName":"Albert","lastName":"Vo","companyName":"Salesforce","streetAddress":"1234 Salesforce Rd.","cityName":"Plano","stateProvince":"TX","postalCode":"75074"}';
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

        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setFirstName')
            ->with($marketingOptInArray['firstName'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setLastName')
            ->with($marketingOptInArray['lastName'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setEmailAddress')
            ->with($marketingOptInArray['emailAddress'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setCompanyName')
            ->with($marketingOptInArray['companyName'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setLanguageCode')
            ->with($marketingOptInArray['languageCode'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setPostalCode')
            ->with($marketingOptInArray['postalCode'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setCityName')
            ->with($marketingOptInArray['cityName'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setStateProvince')
            ->with($marketingOptInArray['stateProvince'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setStreetAddress')
            ->with($marketingOptInArray['streetAddress'])->willReturnSelf();
        $this->salesForceCustomerSubscriberInterfaceMock->expects($this->once())->method('setCountryCode')
            ->with($marketingOptInArray['countryCode'])->willReturnSelf();

        $this->loggerInterfaceMock->expects($this->once())->method('debug')
            ->with('Fedex\Customer\Model\SalesForce\SubscribePublisher::execute:47 SalesForce Subscribe Publish => '.$marketingOptInString);

        $this->jsonMock->expects($this->once())->method('serialize')
            ->with($marketingOptInArray)->willReturn($marketingOptInString);

        $this->subscribePublisher->execute($marketingOptInArray);
    }
}

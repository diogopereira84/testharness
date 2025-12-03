<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CIDPSG\Api\MessageInterface;
use Fedex\CIDPSG\Model\GenericEmail;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Test class for GenericEmail
 */
class GenericEmailTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $genericEmail;
    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var MessageInterface|MockObject
     */
    protected $messageInterface;

    /**
     * @var PublisherInterface|MockObject
     */
    protected $publisherInterface;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterface;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageInterface = $this->getMockForAbstractClass(MessageInterface::class);
        $this->publisherInterface = $this->createMock(PublisherInterface::class);
        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->genericEmail = $this->objectManagerHelper->getObject(
            GenericEmail::class,
            [
                'logger' => $this->logger,
                'request' => $this->requestInterface,
                'publisher' => $this->publisherInterface,
                'message' => $this->messageInterface
            ]
        );
    }

    /**
     * Test publishGenericEmail
     */
    public function testPublishGenericEmail()
    {
        $contentData = '{
            "templateData": "Dummy dfdfgdg",
            "templateSubject":"Dummy",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "attachment": "",
            "customerCsv":""
        }';

        $this->requestInterface->expects($this->once())
            ->method('getContent')
            ->willReturn($contentData);
        $this->testValidateRequestJson();
        $this->messageInterface->expects($this->once())
            ->method('setMessage')
            ->willReturn("mailid");
        $this->assertNotNull($this->genericEmail->publishGenericEmail());
    }

    /**
     * Test publishGenericEmail without validation
     */
    public function testPublishGenericEmailWithoutValidation()
    {
        $contentData = '{
            "templateData": "Dummy",
            "templateSubject":"Dummy",
            "fromEmailId": "abc@gmail.com",
            "retryCount": 0,
            "errorSupportEmailId": ""
        }';

        $this->requestInterface->expects($this->once())
            ->method('getContent')
            ->willReturn($contentData);
        $this->testValidateRequestJson();
        $this->messageInterface->expects($this->any())
            ->method('setMessage')
            ->willReturn("mailid");
        $this->assertNotNull($this->genericEmail->publishGenericEmail());
    }

    /**
     * Test publishGenericEmail with exception
     */
    public function testPublishGenericEmailWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestInterface->expects($this->once())
            ->method('getContent')
            ->willThrowException($exception);
        $this->assertNotNull($this->genericEmail->publishGenericEmail());
    }

    /**
     * Test validateRequestJson
     */
    public function testValidateRequestJson()
    {
        $requestContent = '{
            "templateData": "Dummy",
            "templateSubject":"Dummy",
            "toEmailId": "xyz@gmail.com",
            "fromEmailId": "abc@gmail.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "attachment": "",
            "customerCsv":""
        }';
        $this->assertNotNull($this->genericEmail->validateRequestJson($requestContent));
    }
}

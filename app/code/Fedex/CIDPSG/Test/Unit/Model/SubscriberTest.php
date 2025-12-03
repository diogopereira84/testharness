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
use Fedex\CIDPSG\Model\Subscriber;
use Fedex\CIDPSG\Helper\GenerateCsvHelper;
use Fedex\CIDPSG\Helper\Email as EmailHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Test class for Subscriber
 */
class SubscriberTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $subscriber;
    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var MessageInterface|MockObject
     */
    protected $messageInterface;

    /**
     * @var GenerateCsvHelper|MockObject
     */
    protected $generateCsvHelper;

    /**
     * @var EmailHelper|MockObject
     */
    protected $emailHelper;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageInterface = $this->getMockForAbstractClass(MessageInterface::class);
        $this->emailHelper = $this->getMockBuilder(EmailHelper::class)
            ->setMethods(
                [
                    'sendEmail'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->generateCsvHelper = $this->getMockBuilder(GenerateCsvHelper::class)
            ->setMethods(
                [
                    'generateExcelForAuthrizedUser'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->subscriber = $this->objectManagerHelper->getObject(
            Subscriber::class,
            [
                'logger' => $this->logger,
                'emailHelper' => $this->emailHelper
            ]
        );
    }

    /**
     * Test ProcessGenericEmailWithCommercialReport
     */
    public function testProcessGenericEmailWithCommercialReport()
    {
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"'."Dummy Data".
                    '\",\"url\":\"'."".'\"},\"order\":{\"contact\":{\"email\":\"'."nidhi.singh@infogain.com".'\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Generic Email",
            "attachment": "{\"account_user_name\":\"Test Data H\"}",
            "customerCsv": "customerdata.csv",
            "commercial_report": true
        }';
        $this->messageInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn($jsonData);

        $this->emailHelper->expects($this->any())
            ->method('sendEmail')
            ->willReturnSelf();
        $this->assertNull($this->subscriber->processGenericEmail($this->messageInterface));
    }

    /**
     * Test processGenericEmail
     */
    public function testProcessGenericEmail()
    {
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"'."Dummy Data".
                    '\",\"url\":\"'."".'\"},\"order\":{\"contact\":{\"email\":\"'."nidhi.singh@infogain.com".'\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Generic Email",
            "attachment": "{\"account_user_name\":\"Test Data H\"}",
            "customerCsv": "customerdata.csv"
        }';
        $this->messageInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->generateCsvHelper->expects($this->any())
            ->method('generateExcelForAuthrizedUser')
            ->willReturn("test.csv");
        $this->logger->expects($this->once())
            ->method('info');
        $this->emailHelper->expects($this->any())
            ->method('sendEmail')
            ->willReturnSelf();
        $this->assertEquals(null, $this->subscriber->processGenericEmail($this->messageInterface));
    }

    /**
     * Test processGenericEmail with exception
     */
    public function testProcessGenericEmailWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"'."Dummy Data".
                    '\",\"url\":\"'."".'\"},\"order\":{\"contact\":{\"email\":\"'."nidhi.singh@infogain.com".'\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Generic Email"
        }';
        $this->messageInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->logger->expects($this->once())
            ->method('info');
        $this->emailHelper->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('critical');
        $this->assertEquals(null, $this->subscriber->processGenericEmail($this->messageInterface));
    }
}

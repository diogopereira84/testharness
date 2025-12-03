<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\Email;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\SelfReg\Helper\Email as SelfRegEmailHelper;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Filesystem\Io\File as FileIo;

/**
 * Test class for Email
 */
class EmailTest extends TestCase
{
    protected $file;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $email;
    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var SelfRegEmailHelper $selfRegEmailHelperMock
     */
    protected $selfRegEmailHelperMock;

    /**
     * @var Curl $curlMock
     */
    protected $curlMock;

    /**
     * @var LoggerInterface $loggerMock
     */
    protected $loggerMock;

    /**
     * @var TransportBuilder $transportBuilder
     */
    protected $transportBuilder;

    /**
     * @var FileIo $fileIo
     */
    protected $fileIo;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->selfRegEmailHelperMock = $this->getMockBuilder(SelfRegEmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMail'])
            ->getMock();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setTemplateIdentifier',
                'setFrom',
                'addTo',
                'setTemplateVars',
                'setTemplateOptions',
                'getTransport',
                'getMessage',
                'getBodyText'
            ])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->file = $this->getMockBuilder(File::class)
            ->setMethods(['fileGetContents', 'isExists'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fileIo = $this->getMockBuilder(FileIo::class)
            ->setMethods(['getPathInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->email = $this->objectManagerHelper->getObject(
            Email::class,
            [
                'selfRegEmailHelper' => $this->selfRegEmailHelperMock,
                'curl' => $this->curlMock,
                'logger' => $this->loggerMock,
                'scopeConfig' => $this->scopeConfigMock,
                'file' => $this->file,
                'transportBuilder' => $this->transportBuilder,
                'fileIo' => $this->fileIo
            ]
        );
    }

    /**
     * Test method for callGenericEmailApi
     *
     * @return void
     */
    public function testCallGenericEmailApi()
    {
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"' . "Dummy Data" .
            '\",\"url\":\"' . "" . '\"},\"order\":
                    {\"contact\":{\"email\":\"' . "nidhi.singh@infogain.com" . '\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Generic Email"
        }';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn("https://www.google.com");

        $this->curlMock->expects($this->once())
            ->method('addHeader')
            ->willReturnSelf();

        $this->curlMock->expects($this->once())
            ->method('post')
            ->willReturnSelf();

        $this->curlMock->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $this->assertEquals(true, $this->email->callGenericEmailApi($jsonData));
    }

    /**
     * Test method for callGenericEmailApi with exception
     *
     * @return void
     */
    public function testCallGenericEmailApiWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"' . "Dummy Data" .
            '\",\"url\":\"' . "" . '\"},\"order\":
                    {\"contact\":{\"email\":\"' . "nidhi.singh@infogain.com" . '\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Generic Email"
        }';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn("https://www.google.com");

        $this->curlMock->expects($this->once())
            ->method('addHeader')
            ->willReturnSelf();

        $this->curlMock->expects($this->once())
            ->method('post')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->email->callGenericEmailApi($jsonData));
    }

    /**
     * Test method for emailHeaderLogo
     *
     * @return void
     */
    public function testEmailHeaderLogo()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn("https://www.google.com");

        $expectedResult = "https://www.google.com";

        $this->assertEquals($expectedResult, $this->email->emailHeaderLogo());
    }

    /**
     * Test method for sendEmail
     *
     * @return void
     */
    public function testSendEmail()
    {
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"' . "Dummy Data" .
            '\",\"url\":\"' . "" . '\"},\"order\":{\"contact\":{\"email\":\"' . "nidhi.singh@infogain.com" . '\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Data",
            "attachment": "Path"
        }';
        $jsonData = json_decode($jsonData, true);

        $this->testEmailHeaderLogo();

        $this->file->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $this->file->expects($this->once())
            ->method('fileGetContents')
            ->willReturn("test");

        $this->fileIo->expects($this->once())
            ->method('getPathInfo')
            ->willReturn(["basename" => "test"]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturnSelf();

        $emailResponse = '{"transactionId":"","output":null}';
        $this->selfRegEmailHelperMock->expects($this->once())
            ->method('sendMail')
            ->willReturn($emailResponse);

        $this->assertEquals(true, $this->email->sendEmail($jsonData));
    }

    /**
     * Test method for sendEmail with exception
     *
     * @return void
     */
    public function testSendEmailWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $jsonData = '{
            "templateData": "{\"messages\":{\"statement\":\"' . "Dummy Data" .
            '\",\"url\":\"' . "" . '\"},\"order\":{\"contact\":{\"email\":\"' . "nidhi.singh@infogain.com" . '\"}}}",
            "toEmailId": "nidhi.singh@infogain.com",
            "fromEmailId": "nidhi.singh.osv@fedex.com",
            "retryCount": 0,
            "errorSupportEmailId": "",
            "templateSubject": "Data",
            "attachment": "Path"
        }';
        $jsonData = json_decode($jsonData, true);

        $this->testEmailHeaderLogo();

        $this->file->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
            
        $this->file->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->email->sendEmail($jsonData));
    }

    /**
     * Test method for loadEmailTemplate
     *
     * @return void
     */
    public function testLoadEmailTemplate()
    {
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateIdentifier')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateOptions')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setFrom')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('addTo')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateVars')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('getTransport')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->any())
            ->method('getMessage')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('getBodyText')
            ->willReturn('<div>Test</div>');

        $this->assertEquals(true, $this->email->loadEmailTemplate("4"));
    }

    /**
     * Test method for loadEmailTemplate with exception
     *
     * @return void
     */
    public function testLoadEmailTemplateWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateIdentifier')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateOptions')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setFrom')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('addTo')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('setTemplateVars')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('getTransport')
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())
            ->method('getMessage')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->email->loadEmailTemplate("4"));
    }
}

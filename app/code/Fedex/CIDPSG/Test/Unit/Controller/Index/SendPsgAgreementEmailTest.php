<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CIDPSG\Controller\Index\SendPsgAgreementEmail;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\Email;
use Fedex\CIDPSG\Helper\PsgHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Test class for SendPsgAgreementEmail Controller
 */
class SendPsgAgreementEmailTest extends TestCase
{
    protected $psgHelperMoke;
    protected $storeInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sendPsgAgreementEmail;
    /**
     * @var ResultFactory $resultFactoryMock
     */
    protected $resultFactoryMock;

    /**
     * @var RequestInterface $requestMock
     */
    protected $requestMock;

    /**
     * @var AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;

    /**
     * @var Email $emailMock
     */
    protected $emailMock;

    /**
     * @var PsgHelper $psgHelper
     */
    protected $psgHelper;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPaAgreementEmailTemplate',
                'getFromEmail',
                'getPaAgreementUserEmail',
            ])
            ->getMockForAbstractClass();

        $this->emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadEmailTemplate', 'callGenericEmailApi'])
            ->getMockForAbstractClass();

        $this->psgHelperMoke = $this->getMockBuilder(PsgHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPSGPaAgreementInfoByClientId'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->sendPsgAgreementEmail = $this->objectManager->getObject(
            SendPsgAgreementEmail::class,
            [
                'requestInterface' => $this->requestMock,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'email' => $this->emailMock,
                'psgHelper' => $this->psgHelperMoke,
                'storeManager' => $this->storeManager,
                'resultFactory' => $this->resultFactoryMock
            ]
        );

        $this->sendPsgAgreementEmail->formData = [
            'participation_code' => 'test',
            'company_name' => 'test',
            'first_name' => 'test',
            'account_type' => 1,
            'participation_code_id' => 'test',
            'clientId' => 'CID'
        ];

        $this->sendPsgAgreementEmail->successRespJson = '{
            "success": true,
            "message": "Email Sent Successfully",
            "account_type" => 1
        }';
    }

    /**
     * Test method for Execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->once())->method('getPostValue')
            ->willReturn($this->sendPsgAgreementEmail->formData);

        $this->resultFactoryMock->expects($this->once())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->sendPsgAgreementEmail->execute());
    }

    /**
     * Test method for execute function with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->resultFactoryMock->expects($this->once())->method('create')->willReturnSelf();
        $this->requestMock->expects($this->once())->method('getPostValue')
            ->willReturn($this->sendPsgAgreementEmail->formData);

        $this->resultFactoryMock->expects($this->once())->method('setData')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->sendPsgAgreementEmail->execute());
    }

    public function testExecuteWithExceptionElse()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->requestMock->expects($this->any())->method('getPostValue')
            ->willReturn(null);

        $this->resultFactoryMock->expects($this->any())->method('setData')
            ->willThrowException($exception);

        $this->assertFalse($this->sendPsgAgreementEmail->execute());
    }

    /**
     * Test method for sendPaAgreementEmail function
     *
     * @return void
     */
    public function testSendPaAgreementEmail()
    {
        $formData = [
            'first_name' => 'Test',
            'last_name' => 'Test',
        ];

        $this->testPrepareGenericEmailRequest();
        $this->emailMock->expects($this->once())
            ->method('callGenericEmailApi')
            ->willReturnSelf();

        $this->assertNotNull($this->sendPsgAgreementEmail->sendPaAgreementEmail($formData));
    }

    /**
     * Test method for prepareGenericEmailRequest function
     *
     * @return void
     */
    public function testPrepareGenericEmailRequest()
    {
        $formData = [
            'clientId' => 'fdxform',
            'account_type' => 0,
            'participation_code' => 'TESTFED34095',
            'company_name' => 'IND Purl Corps',
            'first_name' => 'Test',
            'last_name' => 'Test',
            'phone' => '9876543214',
            'customer_email' => 'abc@example.com',
            'participation_agreement' => '<h3>Participation Agreement</h3>',
            'participation_code_id' => 'test'
        ];

        $paAgreementContent = [
            "pa_agreement" =>"Dummy Content",
            "participation_code" => "IND1234455",
        ];

        $this->psgHelperMoke->expects($this->once())
            ->method('getPSGPaAgreementInfoByClientId')
            ->willReturn($paAgreementContent);

        $this->adminConfigHelperMock->expects($this->once())
            ->method('getPaAgreementEmailTemplate')
            ->willReturnSelf();

        $this->emailMock->expects($this->once())
            ->method('loadEmailTemplate')
            ->willReturn("Test");
        
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterface);
        
        $this->storeInterface->expects($this->once())
            ->method('getId')
            ->willReturn("1");

        $this->assertNotNull($this->sendPsgAgreementEmail->
                prepareGenericEmailRequest($formData));
    }

    /**
     * Test method for prepareGenericEmailRequest function with exception
     *
     * @return void
     */
    public function testPrepareGenericEmailRequestWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $formData = [
            'clientId' => 'default',
            'account_type' => 0,
            'participation_code' => 'TESTFED34095',
            'company_name' => 'IND Purl Corps',
            'first_name' => 'Test',
            'last_name' => 'Test',
            'phone' => '9876543214',
            'customer_email' => 'abc@example.com',
            'participation_agreement' => '<h3>Participation Agreement</h3>',
            'participation_code_id' => 'test'
        ];

        $paAgreementContent = [
            "pa_agreement" =>"Dummy Content",
            "participation_code" => "IND1234455",
        ];

        $this->adminConfigHelperMock->expects($this->once())
            ->method('getPaAgreementEmailTemplate')
            ->willReturnSelf();

        $this->psgHelperMoke->expects($this->any())
            ->method('getPSGPaAgreementInfoByClientId')
            ->willReturn($paAgreementContent);

        $this->emailMock->expects($this->once())
            ->method('loadEmailTemplate')
            ->willThrowException($exception);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterface);
        
        $this->storeInterface->expects($this->once())
            ->method('getId')
            ->willReturn("1");

        $this->assertEquals(
            false,
            $this->sendPsgAgreementEmail->prepareGenericEmailRequest($formData)
        );
    }
}

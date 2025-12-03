<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\Email;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Fedex\CIDPSG\Controller\Index\AuthorizedFormSubmit;

/**
 * Test class for AuthorizedFormSubmit Controller
 */
class AuthorizedFormSubmitTest extends TestCase
{
    protected $storeManager;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $authorizedFormSubmit;
    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactoryMock;

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
     * @var StoreManagerInterface $storeManagerMock
     */
    protected $storeManagerMock;

    /**
     * @var LoggerInterface $loggerMock
     */
    protected $loggerMock;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
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
                'getAuthorizedEmailTemplate',
                'getFromEmail',
                'getAuthorizedUserEmail'
            ])
            ->getMockForAbstractClass();

        $this->emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadEmailTemplate', 'callGenericEmailApi'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(
                [
                    'getId',
                    'getStore'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->authorizedFormSubmit = $this->objectManager->getObject(
            AuthorizedFormSubmit::class,
            [
                'requestInterface' => $this->requestMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'email' => $this->emailMock,
                'storeManager' => $this->storeManager,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test method for execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $postValue = [
            'auth_user_name' => 'Test'
        ];

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postValue);
        $this->jsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->testsendAuthorizedEmail();

        $this->assertNotNull($this->authorizedFormSubmit->execute());
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

        $postValue = [
            'auth_user_name' => 'Test'
        ];

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postValue);
        $this->jsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->authorizedFormSubmit->execute());
    }

    /**
     * Test method for execute function
     *
     * @return void
     */
    public function testsendAuthorizedEmail()
    {
        $formData = [
            'account_user_name' => 'Test',
            'office_account_no' => '12345678'
        ];
        $this->testPrepareGenericEmailRequest();
        $this->emailMock->expects($this->any())
            ->method('callGenericEmailApi')
            ->willReturnSelf();

        $this->authorizedFormSubmit->sendAuthorizedEmail($formData);
    }

    /**
     * Test method for execute function
     *
     * @return void
     */
    public function testPrepareGenericEmailRequest()
    {
        $formData = [
            'account_user_name' => 'Test',
            'office_account_no' => '12345678'
        ];

        $this->adminConfigHelperMock->expects($this->any())
            ->method('getAuthorizedEmailTemplate')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturnSelf("2");

        $this->emailMock->expects($this->any())
            ->method('loadEmailTemplate')
            ->willReturn("Test");

        $this->assertNotNull($this->authorizedFormSubmit->prepareGenericEmailRequest($formData));
    }

    /**
     * Test method for execute function with exception
     *
     * @return void
     */
    public function testPrepareGenericEmailRequestWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $formData = [
            'account_user_name' => 'Test',
            'office_account_no' => '12345678'
        ];

        $this->adminConfigHelperMock->expects($this->once())
            ->method('getAuthorizedEmailTemplate')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willThrowException($exception);

        $this->assertEquals(
            false,
            $this->authorizedFormSubmit->prepareGenericEmailRequest($formData)
        );
    }
}

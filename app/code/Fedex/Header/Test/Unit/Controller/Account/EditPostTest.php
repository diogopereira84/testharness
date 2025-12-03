<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Header\Test\Unit\Controller\Account;

use Fedex\Header\Controller\Account\EditPost;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Block\Account\Dashboard\Info;
use Fedex\Header\Helper\Data;


/**
 * Class EditPost
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditPostTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $customerSession;
    /**
     * @var (\Magento\Customer\Api\AccountManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerAccountManagement;
    protected $customerRepository;
    protected $customerInterface;
    protected $formKeyValidator;
    protected $customerExtractor;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaper;
    protected $addressRegistry;
    protected $address;
    protected $resultRedirectFactory;
    protected $requestMock;
    protected $customerMapper;
    protected $messageManager;
    protected $eventManager;
    /**
     * @var (\Magento\Framework\Exception\LocalizedException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $localizedMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $infoMock;
    protected $customerMock;
    protected $editPost;
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'setCustomerFormData', 'logout', 'start'])
            ->getMock();

        $this->customerAccountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setId','getCustomAttribute','setCustomAttribute'])
            ->getMockForAbstractClass();

        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate'])
            ->getMockForAbstractClass();

        $this->customerExtractor = $this->getMockBuilder(CustomerExtractor::class)
            ->disableOriginalConstructor()
            ->setMethods(['extract'])
            ->getMock();

        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressRegistry = $this->getMockBuilder(AddressRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['retrieve'])
            ->getMock();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['setShouldIgnoreValidation'])
            ->getMock();

        $this->resultRedirectFactory = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPost', 'getPostValue', 'getParam', 'getPost'])
            ->getMockForAbstractClass();

        $this->customerMapper = $this->getMockBuilder(Mapper::class)
            ->disableOriginalConstructor()
            ->setMethods(['toFlatArray'])
            ->getMock();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->localizedMock = $this->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->infoMock = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer','getId','getToggleConfigValue','getToggleD193926Fix'])
			->getMock();
        $this->customerMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','create','getCustomer','setEmail','save','setFirstname','setLastname','getToggleConfigValue','getToggleD193926Fix'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->editPost = $objectManagerHelper->getObject(EditPost::class,
            [
                'context' => $this->context,
                'customerSession' => $this->customerSession,
                'customerAccountManagement' => $this->customerAccountManagement,
                'customerRepository' => $this->customerRepository,
                'formKeyValidator' => $this->formKeyValidator,
                'customerExtractor' => $this->customerExtractor,
                'escaper' => $this->escaper,
                'addressRegistry' => $this->addressRegistry,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                '_request' => $this->requestMock,
                'customerMapper' => $this->customerMapper,
                'messageManager' => $this->messageManager,
                '_eventManager' => $this->eventManager,
                'logger' => $this->loggerMock,
                'data'=>$this->customerMock,
                'info'=> $this->infoMock
            ]
        );
    }
    public function testExecute()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

      $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password');

   
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())
        ->method('getCustomer')->willReturn($this->customerMock);
        $this->infoMock->method('getToggleD193926Fix')->willReturn(true);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
   
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('456');
        $this->customerInterface->expects($this->any())->method('getCustomAttribute')->willReturn('123');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);
        $this->addressRegistry->expects($this->once())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); 
       

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('customer/account')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
    public function testExecuteWithLocalizedException()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

        $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password');

        
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getEmail')->willReturn('neeraj2.gupta@iggblobal.com');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);

        $this->addressRegistry->expects($this->any())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); 

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with('customer_account_edited', ['email' => "neeraj2.gupta@iggblobal.com"])->willThrowException($exception);

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('*/*/edit')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
    public function testExecuteWithInputException()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

        $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password123');

        
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getEmail')->willReturn('neeraj2.gupta@iggblobal.com');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);

        $this->addressRegistry->expects($this->any())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); //->willReturn($this->address);

        $phrase = new Phrase(__('Exception message'));
        $exception = new InputException($phrase);

        $phraseMock = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()
            ->setMethods(['getErrors'])
            ->getMock();

      
        $phraseMock->expects($this->any())->method('getErrors')->willReturn([$phrase]);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with('customer_account_edited', ['email' => "neeraj2.gupta@iggblobal.com"])->willThrowException($exception);

        

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('*/*/edit')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
    public function testExecuteWithException()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

        $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password');
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getEmail')->willReturn('neeraj2.gupta@iggblobal.com');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);

        $this->addressRegistry->expects($this->any())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); //->willReturn($this->address);

        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception($phrase);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with('customer_account_edited', ['email' => "neeraj2.gupta@iggblobal.com"])->willThrowException($exception);

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('*/*/edit')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
    public function testExecuteWithUserLockedException()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

        $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password');
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');
        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('start')->willReturnSelf();

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getEmail')->willReturn('neeraj2.gupta@iggblobal.com');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);

        $this->addressRegistry->expects($this->any())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); 

        $phrase = new Phrase(__('Exception message'));
        $exception = new UserLockedException($phrase);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with('customer_account_edited', ['email' => "neeraj2.gupta@iggblobal.com"])->willThrowException($exception);

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('customer/account/login')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
    public function testExecuteWithInvalidEmailOrPasswordException()
    {
        $dataObject = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $params = ['key' => 'value'];
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($result);
        $this->formKeyValidator->expects($this->any())->method('validate')->with($this->requestMock)->willReturn(true);
        $this->requestMock->expects($this->any())->method('isPost')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($params);

        $this->requestMock->expects($this->any())->method('getParam')->withConsecutive(['change_email'], ['change_password'], ['email'], ['email'])->willReturnOnConsecutiveCalls(false, true, 'awda', 'awda');

        $this->requestMock->expects($this->any())->method('getPost')->withConsecutive(['current_password'], ['password'], ['password_confirmation'])->willReturnOnConsecutiveCalls('password', 'password', 'password');
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setCustomerFormData');
        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('start')->willReturnSelf();

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->infoMock->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMapper->expects($this->any())->method('toFlatArray')->with($this->customerInterface)->willReturn([]);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->customerInterface->expects($this->any())->method('setId')->willReturn($this->customerInterface);
        $this->customerExtractor->expects($this->any())->method('extract')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getEmail')->willReturn('neeraj2.gupta@iggblobal.com');
        $this->customerInterface->expects($this->any())->method('getAddresses')->willReturn([$dataObject]);

        $this->addressRegistry->expects($this->any())->method('retrieve')->willReturn($this->address);
        $this->address->expects($this->any())->method('setShouldIgnoreValidation'); //->willReturn($this->address);

        $phrase = new Phrase(__('Exception message'));
        $exception = new InvalidEmailOrPasswordException($phrase);

        $this->eventManager->expects($this->any())
            ->method('dispatch')
            ->with('customer_account_edited', ['email' => "neeraj2.gupta@iggblobal.com"])->willThrowException($exception);

        $this->messageManager->expects($this->any())
            ->method('addException')
            ->willReturnSelf();

        $result->expects($this->any())->method('setPath')->with('*/*/edit')->willReturnSelf();
        $this->assertEquals($result, $this->editPost->execute());

    }
}

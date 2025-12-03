<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Index;

use Fedex\CIDPSG\Controller\Adminhtml\Index\Save;
use Fedex\CIDPSG\Model\Customer;
use Fedex\CIDPSG\Model\CustomerFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\CIDPSG\Model\PsgCustomerFieldsFactory;
use Fedex\CIDPSG\Model\PsgCustomerFields;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Model\Auth\Session as AuthSession;

/**
 * Test class for Save
 */
class SaveTest extends TestCase
{
    protected $customerFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var ObjectManager objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var RequestInterface
     */
    protected $requestMock;

    /**
     * @var ManagerInterface $messageManager
     */
    protected $messageManager;

    /**
     * @var Customer $customer
     */
    protected $customer;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Save $save
     */
    protected $save;

    /**
     * @var PsgCustomerFieldsFactory $psgCustomerFieldsFactory
     */
    protected $psgCustomerFieldsFactory;

    /**
     * @var PsgCustomerFields $psgCustomerFields
     */
    protected $psgCustomerFields;

    /**
     * @var ResourceConnection $resourceConnection
     */
    protected $resourceConnection;

    /**
     * @var AuthSession $authSession
     */
    protected $authSession;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue', 'getParam'])
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['setData', 'save', 'load', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->psgCustomerFieldsFactory = $this->getMockBuilder(PsgCustomerFieldsFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->psgCustomerFields = $this->getMockBuilder(PsgCustomerFields::class)
            ->setMethods(['setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getTableName', 'getConnection', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authSession = $this->getMockBuilder(AuthSession::class)
            ->setMethods(['getUser', 'getEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->save = $this->objectManagerHelper->getObject(
            Save::class,
            [
                'customerFactory' => $this->customerFactory,
                'psgCustomerFieldsFactory' => $this->psgCustomerFieldsFactory,
                'resourceConnection' => $this->resourceConnection,
                'authSession' => $this->authSession,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'requestInterface' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test testExecute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $data = [
            'company_logo' => [['url' => 'http://example.com/logo.png']],
            'default_fields' => [
                [
                    'psg_customer_entity_id' => 3,
                    'field_group' => 0,
                    'field_type' => 'textbox',
                    'field_label' => 'First Name',
                    'field_description' => 'Please first name',
                    'validation_type' => 'text',
                    'max_character_length' => 70,
                    'is_required' => true,
                    'position' => 1,
                ]
            ],
            'custom_fields' => [
                [
                    'psg_customer_entity_id' => 4,
                    'field_group' => 1,
                    'field_type' => 'textbox',
                    'field_label' => 'Last Name',
                    'field_description' => 'Please last name',
                    'validation_type' => 'text',
                    'max_character_length' => 70,
                    'is_required' => true,
                    'position' => 1,
                ]
            ]
        ];
        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($data);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(false);
        $this->customerFactory->expects($this->once())->method('create')->willReturn($this->customer);
        $this->customer->expects($this->once())->method('setData')->willReturnSelf();
        $this->customer->expects($this->once())->method('save')->willReturnSelf();
        $this->resourceConnection->expects($this->once())->method('getTableName')->willReturn('psg_customer_fields');
        $this->resourceConnection->expects($this->once())->method('getConnection')->willReturnSelf();
        $this->resourceConnection->expects($this->once())->method('delete')->willReturn(true);
        $this->psgCustomerFieldsFactory->expects($this->any())->method('create')->willReturn($this->psgCustomerFields);
        $this->psgCustomerFields->expects($this->any())->method('setData')->willReturnSelf();
        $this->psgCustomerFields->expects($this->any())->method('save')->willReturnSelf();
        $this->customer->expects($this->any())->method('load')->willReturnSelf();
        $this->customer->expects($this->any())->method('getData')->willReturn(5);
        $this->authSession->expects($this->once())->method('getUser')->willReturnSelf();
        $this->authSession->expects($this->once())->method('getEmail')->willReturn('test@fedex.com');
        $this->messageManager->expects($this->any())->method('addSuccess')->willReturn("The customer has been saved.");
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf();

        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test Execute with back method.
     *
     * @return void
     */
    public function testExecuteWithBack()
    {
        $data = ['company_logo' => [['url' => 'http://example.com/logo.png']]];

        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($data);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(true);
        $this->authSession->expects($this->once())->method('getUser')->willReturnSelf();
        $this->authSession->expects($this->once())->method('getEmail')->willReturn('test@fedex.com');
        $this->customerFactory->expects($this->once())->method('create')->willReturn($this->customer);
        $this->customer->expects($this->once())->method('setData')->willReturnSelf();
        $this->customer->expects($this->once())->method('save')->willReturnSelf();
        $this->resourceConnection->expects($this->once())->method('getTableName')->willReturn('psg_customer_fields');
        $this->resourceConnection->expects($this->once())->method('getConnection')->willReturnSelf();
        $this->resourceConnection->expects($this->once())->method('delete')->willReturn(true);
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf();

        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }

    /**
     * Test testExecute method with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->customerFactory->expects($this->any())->method('create')
            ->willThrowException($exception);
        
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setPath')->willReturnSelf("*/*/edit");

        $this->assertEquals($this->resultRedirectFactory, $this->save->execute());
    }
}

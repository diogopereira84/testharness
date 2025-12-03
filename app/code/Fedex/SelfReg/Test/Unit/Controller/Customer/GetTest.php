<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Controller\Customer;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\RoleInterface;
use Fedex\SelfReg\Controller\Customer\Get;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Customer as customerModel;
use Magento\Eav\Model\Config as EavConfig;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\Collection as EnhanceUserRolesCollection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTest extends TestCase
{
    protected $customerModel;
    /**
     * @var (\Magento\Eav\Model\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eavConfig;
    /**
     * @var Get
     */
    private $get;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJson;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var AclInterface|MockObject
     */
    private $acl;

    protected $roleUser;
    protected $roleUserCollection;
    protected $toggleConfig;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->createMock(Structure::class);
        $this->structureManager->expects($this->any())->method('getAllowedIds')->willReturn(
            ['users' => [1, 2, 5, 7]]
        );
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->acl = $this->getMockForAbstractClass(AclInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->customerModel = $this->getMockBuilder(customerModel::class)
        ->disableOriginalConstructor()
        ->setMethods(['getSecondaryEmail','load','getData'])
        ->getMock();
        $this->eavConfig = $this->getMockBuilder(EavConfig::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->resultJson = $this->createPartialMock(Json::class, ['setData']);
        $resultFactory->expects($this->once())->method('create')->willReturn($this->resultJson);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->roleUser = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();
        $this->roleUserCollection = $this->getMockBuilder(EnhanceUserRolesCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getColumnValues'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->get = $objectManagerHelper->getObject(
            Get::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'customerRepository' => $this->customerRepository,
                'acl' => $this->acl,
                'customerModel' =>$this->customerModel,
                'logger' => $logger,
                '_request' => $this->request,
                'eavConfig' => $this->eavConfig,
                'roleUser' => $this->roleUser,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @param int $customerId
     * @param MockObject $customer
     * @param ReturnStub|\PHPUnit\Framework\MockObject\Stub\Exception $customerResult
     * @param int $getCustomerInvocation
     * @param int $invocationCount
     * @param string $expect
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $customerId,
        $customer,
        $customerResult,
        $getCustomerInvocation,
        $invocationCount,
        $expect
    ) {
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $companyAttributes = $this->createMock(Customer::class);
        $this->customerRepository->expects($this->exactly($getCustomerInvocation))
            ->method('getById')->with($customerId)->will($customerResult);

        $this->customerModel->expects($this->any())
            ->method('load')->with($customerId)->willReturnSelf();
        $this->customerModel->expects($this->any())
            ->method('getData')->with('customer_status')->willReturn('1');
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($invocationCount ? $this->atLeastOnce() : $this->never())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $customer->expects($invocationCount ? $this->atLeastOnce() : $this->never())
            ->method('getExtensionAttributes')->willReturn($customerExtension);
        $customer->expects($this->exactly($invocationCount))->method('__toArray')->willReturn([]);
        $this->customerModel->expects($this->any())->method('getSecondaryEmail')->willReturn('sanchit@gmail.com');
        $companyAttributes->expects($this->exactly($invocationCount))->method('getJobTitle')->willReturn('job title');
        $companyAttributes->expects($this->exactly($invocationCount))->method('getTelephone')->willReturn('111111');
        $companyAttributes->expects($this->exactly($invocationCount))->method('getStatus')->willReturn('status');
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->acl->expects($this->exactly($invocationCount))
            ->method('getRolesByUserId')->with($customerId)->willReturn([$role]);
        $role->expects($this->exactly($invocationCount))->method('getId')->willReturn(9);
        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->roleUser->expects($this->any())->method('getCollection')->willReturn($this->roleUserCollection);
        $this->roleUserCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->roleUserCollection->expects($this->any())->method('getColumnValues')->willReturnSelf([4,6]);

        $this->resultJson->expects($this->once())->method('setData')->willReturnCallback($setDataCallback);
        $this->get->execute();
        $this->assertEquals($expect, $result);
    }

    /**
     * Data provider for testExecute.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        $customer = $this->createMock(\Magento\Customer\Model\Data\Customer::class);
        return [
            [
                1,
                $customer,
                $this->returnValue($customer),
                1,
                1,
                'ok'
            ],
            [
                2,
                $customer,
                $this->throwException(new \Exception()),
                1,
                0,
                'error'
            ],
            [
                2,
                $customer,
                $this->throwException(new LocalizedException(__('phrase'))),
                1,
                0,
                'error'
            ],
        ];
    }
}

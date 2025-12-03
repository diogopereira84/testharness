<?php

declare (strict_types = 1);

namespace Fedex\Company\Test\Unit\Controller\User;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Company\Controller\User\Delete;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Api\GroupRepositoryInterface;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Customer\Model\ResourceModel\Customer\Collection;

class DeleteTest extends TestCase
{
    private $resultJsonFactoryMock;
    private $userGroupsRepositoryMock;
    private $loggerMock;
    private $requestMock;
    private $delete;
    private $customerFactoryMock;
    private $customerMock;
    private $groupRepositoryMock;
    private $folderPermissionMock;
    private $customerCollectionMock;
    private $customerEntityMock;

    protected function setUp(): void
    {
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->userGroupsRepositoryMock = $this->createMock(UserGroupsRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->customerFactoryMock = $this->createMock(CustomerFactory::class);
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->addMethods(['getCollection', 'setGroupId'])
            ->getMock();
        $this->customerEntityMock = $this->createMock(Customer::class);
        $this->groupRepositoryMock = $this->createMock(GroupRepositoryInterface::class);
        $this->folderPermissionMock = $this->createMock(FolderPermission::class);
        $this->customerCollectionMock = $this->createMock(Collection::class);

        $this->delete = new Delete(
            $this->resultJsonFactoryMock,
            $this->userGroupsRepositoryMock,
            $this->loggerMock,
            $this->requestMock,
            $this->customerFactoryMock,
            $this->customerEntityMock,
            $this->groupRepositoryMock,
            $this->folderPermissionMock
        );
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute($params, $expectedGroupId)
    {
        $resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);
        $this->requestMock->method('getParams')->willReturn($params);
        $this->userGroupsRepositoryMock->expects($this->any())->method('deleteById')->with($expectedGroupId);
        $this->folderPermissionMock->expects($this->any())->method('getParentGroupId')
            ->with($expectedGroupId)->willReturn(1);
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->method('addFieldToFilter')->with('group_id', $expectedGroupId)->willReturnSelf();
        $this->customerCollectionMock->method('getItems')->willReturn([]);

        $this->assertSame($resultJsonMock, $this->delete->execute());
    }

    public function executeDataProvider()
    {
        return [
            [['groupId' => 'user_groups-1'], 1],
            [['groupId' => 'customer_group-2'], 2]
        ];
    }
}

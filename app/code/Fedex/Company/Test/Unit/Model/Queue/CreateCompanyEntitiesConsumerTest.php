<?php

namespace Fedex\Company\Test\Unit\Model\Queue;

use Fedex\Company\Api\CreateCompanyEntitiesMessageInterface;
use Fedex\Company\Model\CompanyCreation;
use Fedex\Company\Model\Queue\CreateCompanyEntitiesConsumer;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateCompanyEntitiesConsumerTest extends TestCase
{
    private $requestMock;
    private $serializerMock;
    private $loggerMock;
    private $eventManagerMock;
    private $companyCreationMock;
    private $companyRepositoryMock;
    private $consumer;
    private $companyMock;
    private $messageMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->companyCreationMock = $this->createMock(CompanyCreation::class);
        $this->companyRepositoryMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['setSharedCatalogId'])
            ->setMethods(['setCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageMock = $this->createMock(CreateCompanyEntitiesMessageInterface::class);

        $this->consumer = new CreateCompanyEntitiesConsumer(
            $this->requestMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->eventManagerMock,
            $this->companyCreationMock,
            $this->companyRepositoryMock
        );
    }

    public function testInitializeCompanyExtraEntitiesCreationAll()
    {
        $messageData = [
            'company_id' => 1,
            'url_extension_name' => 'test',
            'creation_type' => 'all',
            'request_params' => []
        ];

        $this->messageMock->method('getMessage')->willReturn(json_encode($messageData));
        $this->serializerMock->method('unserialize')->willReturn($messageData);
        $this->companyRepositoryMock->method('get')->willReturn($this->companyMock);

        $this->companyCreationMock->expects($this->once())
            ->method('initializeCompanyExtraEntitiesCreation')
            ->with('test');

        $this->companyCreationMock->method('getCreatedCustomerGroup')->willReturn($this->createMock(\Magento\Customer\Api\Data\GroupInterface::class));
        $this->companyCreationMock->method('getCreatedRootCategory')->willReturn($this->createMock(\Magento\Catalog\Api\Data\CategoryInterface::class));

        $this->companyMock->expects($this->once())->method('setCustomerGroupId');
        $this->companyMock->expects($this->once())->method('setSharedCatalogId');
        $this->companyRepositoryMock->expects($this->once())->method('save');
        $this->eventManagerMock->expects($this->once())->method('dispatch');

        $this->consumer->initializeCompanyExtraEntitiesCreation($this->messageMock);
    }

    public function testInitializeCompanyExtraEntitiesCreationRootCategory()
    {
        $messageData = [
            'company_id' => 1,
            'url_extension_name' => 'test',
            'creation_type' => 'root_category',
            'customer_group_id' => 2,
            'request_params' => []
        ];

        $this->messageMock->method('getMessage')->willReturn(json_encode($messageData));
        $this->serializerMock->method('unserialize')->willReturn($messageData);
        $this->companyRepositoryMock->method('get')->willReturn($this->companyMock);

        $this->companyCreationMock->expects($this->once())
            ->method('initializeOnlyRootCategoryCreation')
            ->with('test', 2);

        $this->companyCreationMock->method('getCreatedRootCategory')->willReturn($this->createMock(\Magento\Catalog\Api\Data\CategoryInterface::class));

        $this->companyMock->expects($this->once())->method('setSharedCatalogId');
        $this->companyRepositoryMock->expects($this->once())->method('save');
        $this->eventManagerMock->expects($this->once())->method('dispatch');

        $this->consumer->initializeCompanyExtraEntitiesCreation($this->messageMock);
    }

    public function testInitializeCompanyExtraEntitiesCreationCustomerGroup()
    {
        $messageData = [
            'company_id' => 1,
            'url_extension_name' => 'test',
            'creation_type' => 'customer_group',
            'shared_catalog_id' => 3,
            'request_params' => []
        ];

        $this->messageMock->method('getMessage')->willReturn(json_encode($messageData));
        $this->serializerMock->method('unserialize')->willReturn($messageData);
        $this->companyRepositoryMock->method('get')->willReturn($this->companyMock);

        $this->companyCreationMock->expects($this->once())
            ->method('initializeOnlyCustomerGroupCreation')
            ->with('test', 3);

        $this->companyCreationMock->method('getCreatedCustomerGroup')->willReturn($this->createMock(\Magento\Customer\Api\Data\GroupInterface::class));

        $this->companyMock->expects($this->once())->method('setCustomerGroupId');
        $this->companyRepositoryMock->expects($this->once())->method('save');
        $this->eventManagerMock->expects($this->once())->method('dispatch');

        $this->consumer->initializeCompanyExtraEntitiesCreation($this->messageMock);
    }

    public function testInitializeCompanyExtraEntitiesCreationException()
    {
        $messageData = [
            'company_id' => 1,
            'url_extension_name' => 'test',
            'creation_type' => 'all',
            'request_params' => []
        ];

        $this->messageMock->method('getMessage')->willReturn(json_encode($messageData));
        $this->serializerMock->method('unserialize')->willReturn($messageData);
        $this->companyRepositoryMock->method('get')->willThrowException(new NoSuchEntityException(__('Company not found')));

        $this->loggerMock->expects($this->exactly(3))->method('critical');

        $this->consumer->initializeCompanyExtraEntitiesCreation($this->messageMock);
    }
}

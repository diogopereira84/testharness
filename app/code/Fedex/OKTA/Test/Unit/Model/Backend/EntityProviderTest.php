<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Fedex\OKTA\Model\Backend\AuthRepository;
use Fedex\OKTA\Model\Config\Backend as OktaConfig;
use Fedex\OKTA\Model\EntityDataValidator;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Backend\EntityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityProviderTest extends TestCase
{
    /**
     * @var EntityProvider
     */
    private EntityProvider $entityProvider;

    /**
     * @var AuthRepository|MockObject
     */
    private AuthRepository $authRepositoryMock;

    /**
     * @var OktaConfig|MockObject
     */
    private OktaConfig $oktaConfigMock;

    /**
     * @var User|MockObject
     */
    private User $userMock;

    /**
     * @var UserFactory|MockObject
     */
    private UserFactory $userFactoryMock;

    /**
     * @var UserResource|MockObject
     */
    private UserResource $userResourceMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private SerializerInterface $serializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->authRepositoryMock = $this->createMock(AuthRepository::class);
        $this->oktaConfigMock = $this->createMock(OktaConfig::class);
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()->setMethods(['getId', 'addData', 'setData'])->getMock();
        $this->userFactoryMock = $this->createMock(UserFactory::class);
        $this->userResourceMock = $this->createMock(UserResource::class);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->entityProvider = new EntityProvider(
            $this->authRepositoryMock,
            $this->oktaConfigMock,
            $this->userFactoryMock,
            $this->userResourceMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    public function testGetOrCreateEntity(): void
    {
        $this->userFactoryMock->expects($this->once())->method('create')->willReturn($this->userMock);
        $this->authRepositoryMock->expects($this->once())->method('getRelationshipId')->willReturn(true);
        $this->authRepositoryMock->expects($this->once())->method('addRelationship')->willReturn(true);
        $this->oktaConfigMock->expects($this->once())->method('getRoles')->willReturn([
            ['external_group' => 'test', 'internal_role' => 'test'],
        ]);
        $this->entityProvider->getOrCreateEntity([
            'sub' => 'some_data',
            'email' => 'email@mail.com',
            'given_name' => 'test',
            'family_name' => 'test',
            'groups' => ['test']
        ]);
    }

    public function testGetOrCreateEntityLoadByEmail(): void
    {
        $this->userFactoryMock->expects($this->once())->method('create')->willReturn($this->userMock);
        $this->authRepositoryMock->expects($this->once())->method('getRelationshipId')->willReturn(false);
        $this->authRepositoryMock->expects($this->once())->method('addRelationship')->willReturn(true);
        $this->oktaConfigMock->expects($this->once())->method('getRoles')->willReturn([
            ['external_group' => 'test', 'internal_role' => 'test'],
        ]);
        $this->entityProvider->getOrCreateEntity([
            'sub' => 'some_data',
            'email' => 'email@mail.com',
            'given_name' => 'test',
            'family_name' => 'test',
            'groups' => ['test']
        ]);
    }

    public function testGetInternalRoleIdInvalid(): void
    {
        $this->oktaConfigMock->expects($this->any())->method('getRoles')->willReturn([]);
        $this->oktaConfigMock->expects($this->once())
            ->method('isToggleForEnhancedLoggingEnabled')
            ->willReturn(true);
        $this->expectException(LocalizedException::class);
        $oktaGroupsData[EntityDataValidator::KEY_EMAIL] = '';
        $oktaGroupsData[EntityDataValidator::KEY_GROUPS] = [];
        $this->entityProvider->getInternalRoleId($oktaGroupsData);
    }
}

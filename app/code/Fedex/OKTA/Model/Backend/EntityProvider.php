<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Backend;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Fedex\OKTA\Api\Backend\AuthRepositoryInterface;
use Fedex\OKTA\Model\Config\Backend as OktaConfig;
use Fedex\OKTA\Model\EntityDataValidator;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class EntityProvider
{
    /**
     * Default properties values
     */
    public const DEFAULT_LOCALE = 'en_US';

    public const PASS_HASH = 'okta_admin';

    /**
     * EntityProvider constructor.
     * @param AuthRepositoryInterface $authRepository
     * @param OktaConfig $oktaConfig
     * @param UserFactory $userFactory
     * @param UserResource $userResource
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private OktaConfig $oktaConfig,
        private UserFactory $userFactory,
        private UserResource $userResource,
        private SerializerInterface $serializer,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param array $entityData
     * @return User
     * @throws LocalizedException
     * @throws AlreadyExistsException
     */
    public function getOrCreateEntity(array $entityData): User
    {
        /** @var User $user */
        $user = $this->loadMagentoEntity($entityData);

        if (!$user->getId()) {
            $user->addData($this->getNewMagentoEntityData($entityData));
        }

        /**
         * Handle admin user role and internal relationship control before login
         */
        $user->setData('role_id', $this->getInternalRoleId($entityData));
        $this->userResource->save($user);

        $this->createOrUpdateRelationship($entityData[EntityDataValidator::KEY_SUB], $user, $this->serializer->serialize($entityData));

        return $user;
    }

    /**
     * @param array $entityData
     * @return User
     * @throws LocalizedException
     */
    private function loadMagentoEntity(array $entityData): User
    {
        /** @var User $user */
        $user = $this->userFactory->create();

        if ($relationshipId = $this->getInternalIdByRelationship($entityData[EntityDataValidator::KEY_SUB])) {
            $this->userResource->load($user, $relationshipId);
        } else {
            $this->userResource->load(
                $user,
                $entityData[EntityDataValidator::KEY_EMAIL],
                EntityDataValidator::KEY_EMAIL
            );
        }

        return $user;
    }

    /**
     * @param array $entityData
     * @return array
     */
    private function getNewMagentoEntityData(array $entityData): array
    {
        $username = strtolower(
            $entityData[EntityDataValidator::KEY_FIRSTNAME] . '.' . $entityData[EntityDataValidator::KEY_LASTNAME]
        );

        return [
            'username'         => $username,
            'email'            => $entityData[EntityDataValidator::KEY_EMAIL],
            'firstname'        => $entityData[EntityDataValidator::KEY_FIRSTNAME],
            'lastname'         => $entityData[EntityDataValidator::KEY_LASTNAME],
            'password'         => uniqid(rand() . self::PASS_HASH),
            'interface_locale' => self::DEFAULT_LOCALE,
            'is_active'        => true
        ];
    }

    /**
     * @param array $oktaGroupsData
     * @return int
     * @throws LocalizedException
     */
    public function getInternalRoleId(array $oktaGroupsData): int
    {
        /** @var array $option */
        foreach ($this->oktaConfig->getRoles() as $option) {
            if (in_array($option['external_group'], $oktaGroupsData[EntityDataValidator::KEY_GROUPS])) {
                return (int) $option['internal_role'];
            }
        }

        if ($this->oktaConfig->isToggleForEnhancedLoggingEnabled()) {
            $arrayDebug = json_encode(['Groups' => $oktaGroupsData, 'Okta config Roles' => $this->oktaConfig->getRoles()]);
            $this->logger->error(__METHOD__.':'.__LINE__." - email: {$oktaGroupsData[EntityDataValidator::KEY_EMAIL]} User is not assigned to a valid admin user role.");
            $this->logger->info(__METHOD__.':'.__LINE__." - details: {$arrayDebug}");
        } else {
            $this->logger->error(__METHOD__.':'.__LINE__.' User is not assigned to a valid admin user role.');
        }

        throw new LocalizedException(__('You are not assigned to a valid admin user role.'));
    }

    /**
     * @param string $oktaUserIdentifier
     * @param User $user
     * @param string|null $oktaUserData
     * @return bool
     */
    private function createOrUpdateRelationship(string $oktaUserIdentifier, User $user, string $oktaUserData = null)
    {
        return $this->authRepository->addRelationship($oktaUserIdentifier, (int) $user->getId(), $oktaUserData);
    }

    /**
     * @param string $oktaUserIdentifier
     * @return false|int
     * @throws LocalizedException
     */
    public function getInternalIdByRelationship(string $oktaUserIdentifier)
    {
        return $this->authRepository->getRelationshipId($oktaUserIdentifier) ?? false;
    }

}

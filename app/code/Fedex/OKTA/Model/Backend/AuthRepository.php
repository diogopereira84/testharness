<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Backend;

use Magento\Framework\Exception\LocalizedException;
use Fedex\OKTA\Api\Backend\AuthRepositoryInterface;

/**
 * Class AuthRepository
 *
 * @package Fedex\OKTA\Model\Backend
 */
class AuthRepository implements AuthRepositoryInterface
{
    /**
     * @var \Fedex\OKTA\Model\ResourceModel\Frontend\Auth
     */
    private $serviceResource;

    /**
     * AuthRepository constructor
     *
     * @param \Fedex\OKTA\Model\ResourceModel\Backend\Auth $serviceResource
     */
    public function __construct(
        \Fedex\OKTA\Model\ResourceModel\Backend\Auth $serviceResource
    ) {
        $this->serviceResource = $serviceResource;
    }

    /**
     * @param string $oktaUserId
     * @return false|int
     * @throws LocalizedException
     */
    public function getRelationshipId(string $oktaUserId)
    {
        if (!($customerId = $this->serviceResource->getRelationshipId($oktaUserId))) {
            return false;
        }

        return (int) $customerId;
    }

    /**
     * @param string $oktaUserId
     * @param int $adminUserId
     * @param string|null $oktaUserData
     * @return bool
     * @throws LocalizedException
     */
    public function addRelationship(string $oktaUserId, int $adminUserId, string $oktaUserData = null)
    {
        return (bool) $this->serviceResource->addRelationship($oktaUserId, $adminUserId, $oktaUserData);
    }

    /**
     * @param int $adminUserId
     * @return mixed|string
     * @throws LocalizedException
     */
    public function getRelationshipData(int $adminUserId)
    {
        return $this->serviceResource->getRelationshipData($adminUserId);
    }
}

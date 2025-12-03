<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Api\Backend;

use Magento\Framework\Exception\LocalizedException;

interface AuthRepositoryInterface
{
    /**
     * Get Relationship id by okta user id
     *
     * @param string $oktaUserId
     * @return false|int
     * @throws LocalizedException
     */
    public function getRelationshipId(string $oktaUserId);

    /**
     * Add Relationship to Okta user and admin user
     *
     * @param string $oktaUserId
     * @param int $adminUserId
     * @param string|null $oktaUserData
     * @return mixed
     */
    public function addRelationship(string $oktaUserId, int $adminUserId, string $oktaUserData = null);

    /**
     * Get Relationship Data by admin user id
     *
     * @param int $adminUserId
     * @return mixed
     */
    public function getRelationshipData(int $adminUserId);
}

<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\ResourceModel\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Fedex\OKTA\Api\Data\Frontend\AuthInterface;

/**
 * Class Auth
 *
 * @package Fedex\OKTA\Model\ResourceModel\Backend
 */
class Auth extends AbstractDb
{
    private const ADMIN_IDENTIFIER = 'admin_user_id';
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('fedex_okta_backend_auth', 'id');
    }

    /**
     * @param string $oktaUserId
     * @return null|int
     * @throws LocalizedException
     */
    public function getRelationshipId(string $oktaUserId)
    {
        $adapter = $this->getConnection();
        $select  = $adapter->select()
            ->from($this->getMainTable(), [self::ADMIN_IDENTIFIER])
            ->where('okta_user_id = :okta_user_id');

        $bind['okta_user_id'] = $oktaUserId;
        $adapter->fetchOne($select, $bind);
        return $adapter->fetchOne($select, $bind);
    }

    /**
     * @param string $oktaUserId
     * @param int $adminUserId
     * @param string|null $oktaUserData
     * @return int
     * @throws LocalizedException
     */
    public function addRelationship(string $oktaUserId, int $adminUserId, string $oktaUserData = null)
    {
        $adapter = $this->getConnection();
        $data    = [
            'okta_user_id'  => $oktaUserId,
            self::ADMIN_IDENTIFIER => $adminUserId,
            'okta_user_data' => $oktaUserData
        ];

        return $adapter->insertOnDuplicate($this->getMainTable(), $data);
    }

    /**
     * @param int $adminUserId
     * @return string
     * @throws LocalizedException
     */
    public function getRelationshipData(int $adminUserId)
    {
        $adapter = $this->getConnection();
        $select  = $adapter->select()
            ->from($this->getMainTable(),['okta_user_data'])
            ->where('admin_user_id = :admin_user_id');

        $bind[self::ADMIN_IDENTIFIER] = $adminUserId;
        $adapter->fetchOne($select, $bind);
        return $adapter->fetchOne($select, $bind);
    }
}

<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Cron;

use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Integration\Model\ResourceModel\Oauth\Token as TokenResourceModel;

class CleanExpiredFuseToken
{
    /**
     * Initialize dependencies.
     *
     * @param TokenResourceModel $tokenResourceModel
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        private TokenResourceModel $tokenResourceModel,
        private DateTimeFactory $dateTimeFactory
    )
    {
    }

    /**
     * Delete expired fuse token
     *
     * @return void
     */
    public function execute(): void
    {
        $dateModel = $this->dateTimeFactory->create();
        $connection = $this->tokenResourceModel->getConnection();

        $fuseCondition = $connection->quoteInto('user_type = ?', UserContextInterface::USER_TYPE_INTEGRATION);
        $userTypeCondition = $connection->quoteInto('is_fuse = ?', 1);
        $createdAtCondition = $connection->quoteInto('expires_at <= ?', $dateModel->gmtDate());

        $connection->delete(
            $this->tokenResourceModel->getMainTable(),
            $userTypeCondition . ' AND ' . $createdAtCondition . ' AND ' . $fuseCondition
        );
    }
}

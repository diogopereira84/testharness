<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdInterface;
use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueIdResource;
use Magento\Framework\Model\AbstractModel;

/**
 * @codeCoverageIgnore
 * Class CustomerPunchoutUniqueId
 */
class CustomerPunchoutUniqueId extends AbstractModel implements CustomerPunchoutUniqueIdInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_punchout_unique_id';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CustomerPunchoutUniqueIdResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        return (int)$this->getData(self::CUSTOMER_ID) ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(?int $customerId): void
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmail(?string $customerEmail): void
    {
        $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * @inheritDoc
     */
    public function getUniqueId(): ?string
    {
        return $this->getData(self::UNIQUE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUniqueId(?string $uniqueId): void
    {
        $this->setData(self::UNIQUE_ID, $uniqueId);
    }
}

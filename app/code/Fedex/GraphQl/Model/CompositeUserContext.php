<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Magento\Authorization\Model\UserContextInterface;

class CompositeUserContext implements UserContextInterface
{
    /**
     * Zero represents guest cart
     * @return int
     */
    public function getUserId(): int
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getUserType(): int
    {
        return UserContextInterface::USER_TYPE_INTEGRATION;
    }
}

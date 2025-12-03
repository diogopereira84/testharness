<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response;

use Magento\Framework\DataObject;

class Introspect extends DataObject implements IntrospectInterface
{
    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return (bool)($this->getData(static::ACTIVE) ?? false);
    }

    /**
     * @inheritDoc
     */
    public function setActive(bool $active): IntrospectInterface
    {
        return $this->setData(static::ACTIVE, $active);
    }
}

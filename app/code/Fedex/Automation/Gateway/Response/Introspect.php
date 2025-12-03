<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Response;

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

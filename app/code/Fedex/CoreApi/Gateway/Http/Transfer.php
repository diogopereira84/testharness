<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Gateway\Http;

use Magento\Framework\DataObject;

class Transfer extends DataObject implements TransferInterface
{
    /**
     * @inheritDoc
     */
    public function getParams(): array
    {
        return $this->getData(static::PARAMS) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->getData(static::METHOD) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->getData(static::HEADERS) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getBody(): array
    {
        return $this->getData(static::BODY) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getUri(): string
    {
        return $this->getData(static::URI) ?? '';
    }
}

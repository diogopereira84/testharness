<?php

namespace Fedex\GraphQl\Exception;

use GraphQL\Error\ClientAware;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class GraphQlInStoreException extends LocalizedException implements ClientAware
{
    /** @var string  */
    const EXCEPTION_CATEGORY = 'graphql-instore';

    /**
     * @param bool $isSafe
     */
    public function __construct(
        string $phrase,
        \Exception $cause = null,
        $code = 0,
        private bool $isSafe = true
    )
    {
        parent::__construct(__($phrase), $cause, $code);
    }

    public function isClientSafe(): bool
    {
        return $this->isSafe;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return self::EXCEPTION_CATEGORY;
    }
}

<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Exception;

use Exception;
use Magento\Framework\Exception\AggregateExceptionInterface;
use Magento\Framework\Exception\LocalizedException;
use GraphQL\Error\ClientAware;
use Magento\Framework\Phrase;

class GraphQlFujitsuResponseException extends LocalizedException implements AggregateExceptionInterface, ClientAware
{
    public const EXCEPTION_CATEGORY = 'graphql-fujitsu-response';

    /**
     * The array of errors that have been added via the addError() method
     *
     * @var LocalizedException[]
     */
    private $errors = [];

    /**
     * Initialize object
     *
     * @param Phrase $phrase
     * @param Exception $cause
     * @param int $code
     * @param boolean $isSafe
     */
    public function __construct(
        Phrase $phrase,
        Exception $cause = null,
        $code = 0,
        private $isSafe = true
    )
    {
        parent::__construct($phrase, $cause, $code);
    }

    /**
     * @inheritdoc
     */
    public function isClientSafe() : bool
    {
        return $this->isSafe;
    }

    /**
     * @inheritdoc
     */
    public function getCategory() : string
    {
        return self::EXCEPTION_CATEGORY;
    }

    /**
     * Add child error if used as aggregate exception
     *
     * @param LocalizedException $exception
     * @return $this
     */
    public function addError(LocalizedException $exception): self
    {
        $this->errors[] = $exception;
        return $this;
    }

    /**
     * Get child errors if used as aggregate exception
     *
     * @return LocalizedException[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Athira Indrakumar
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Exception;

use Fedex\CartGraphQl\Exception\GraphQlParamNullException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class GraphQlParamNullExceptionTest extends TestCase
{
    /**
     * string MESSAGE
     */
    private const MESSAGE = 'Some Message';

    /**
     * @var GraphQlParamNullException
     */
    protected GraphQlParamNullException $instance;

    public function setUp() : void
    {
        $phrase = new Phrase(self::MESSAGE);
        $this->instance = new GraphQlParamNullException($phrase);
    }

    public function testIsClientSafe(): void
    {
        $result = $this->instance->isClientSafe();
        static::assertIsBool($result);
    }

    public function testGetCategory(): void
    {
        $result = $this->instance->getCategory();
        static::assertSame($result, GraphQlParamNullException::EXCEPTION_CATEGORY);
    }

    public function testAddError(): void
    {
        $exception = new LocalizedException(__(self::MESSAGE));
        $result = $this->instance->addError($exception);
        static::assertInstanceOf(GraphQlParamNullException::class, $result);
    }

    public function testGetErrors(): void
    {
        $result = $this->instance->getErrors();
        static::assertIsArray($result);
    }
}

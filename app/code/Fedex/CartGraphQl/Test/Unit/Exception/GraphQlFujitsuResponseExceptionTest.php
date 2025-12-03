<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Exception;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class GraphQlFujitsuResponseExceptionTest extends TestCase
{
    /**
     * @var GraphQlFujitsuResponseException
     */
    protected GraphQlFujitsuResponseException $instance;

    public function setUp() : void
    {
        $phrase = new Phrase("Some Message");
        $this->instance = new GraphQlFujitsuResponseException($phrase);
    }

    public function testIsClientSafe(): void
    {
        $result = $this->instance->isClientSafe();
        static::assertIsBool($result);
    }

    public function testGetCategory(): void
    {
        $result = $this->instance->getCategory();
        static::assertSame($result, GraphQlFujitsuResponseException::EXCEPTION_CATEGORY);
    }

    public function testAddError(): void
    {
        $exception = new LocalizedException(__("Some Message"));
        $result = $this->instance->addError($exception);
        static::assertInstanceOf(GraphQlFujitsuResponseException::class, $result);
    }

    public function testGetErrors(): void
    {
        $result = $this->instance->getErrors();
        static::assertIsArray($result);
    }
}

<?php

namespace Fedex\GraphQl\Test\Unit\Exception;

use Fedex\GraphQl\Exception\GraphQlInStoreException;
use GraphQL\Error\ClientAware;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class GraphQlInStoreExceptionTest extends TestCase
{

    /** @var GraphQlInStoreException  */
    private GraphQlInStoreException $exception;

    protected function setUp(): void
    {
        $this->exception = new GraphQlInStoreException('test exception');
    }

    /**
     * @return void
     */
    public function testExtendsLocalizedException(): void
    {
        $this->assertInstanceOf(LocalizedException::class, $this->exception);
    }

    /**
     * @return void
     */
    public function testImplementsClientAware(): void
    {
        $this->assertInstanceOf(ClientAware::class, $this->exception);
    }

    /**
     * @return void
     */
    public function testIsClientSafe(): void
    {
        $this->assertTrue($this->exception->isClientSafe());
    }

    public function testGetCategory(): void
    {
        $this->assertEquals('graphql-instore', $this->exception->getCategory());
    }

    public function testIsClientSafeFalse(): void
    {
        $exception = new GraphQlInStoreException('test exception 2', null, 0, false);
        $this->assertFalse($exception->isClientSafe());
    }
}

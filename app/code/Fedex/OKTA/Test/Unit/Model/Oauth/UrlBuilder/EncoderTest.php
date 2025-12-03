<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth\UrlBuilder;

use PHPUnit\Framework\TestCase;
use Fedex\OKTA\Model\Oauth\UrlBuilder\Encoder;

class EncoderTest extends TestCase
{
    /**
     * Raw value to be encoded
     */
    private const VALUE_DECODED = ' some random string +++++/// ';

    /**
     * Base64 encoded value
     */
    private const VALUE_ENCODED = 'IHNvbWUgcmFuZG9tIHN0cmluZyArKysrKy8vLyA';

    /**
     * Test encode() method
     *
     * @return void
     */
    public function testEncode(): void
    {
        $encoder = new Encoder();
        $this->assertEquals(
            self::VALUE_ENCODED,
            $encoder->encode(self::VALUE_DECODED)
        );
    }
}

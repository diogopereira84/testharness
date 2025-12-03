<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth\UrlBuilder;

use Fedex\OKTA\Model\Oauth\UrlBuilder\Code;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{
    /**
     * Challenge method key
     */
    private const CHALLENGE_METHOD = 'code_challenge_method';

    /**
     * Challenge key
     */
    private const CHALLENGE = 'code_challenge';

    /**
     * Verifier key
     */
    private const VERIFIER = 'code_verifier';

    /**
     * Challenge method value
     */
    private const CHALLENGE_METHOD_VALUE = 'S256';

    /**
     * Challenge method alternative value
     */
    private const CHALLENGE_METHOD_CHANGED_VALUE = 'md5';

    /**
     * Challenge value
     */
    private const CHALLENGE_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEz';

    /**
     * Challenge alternative value
     */
    private const CHALLENGE_CHANGED_VALUE = '3SsMTYH6Mh9TX9nMWirkcAbDbL3oFm7-_QMlPywUeb8';

    /**
     * Verifier value
     */
    private const VERIFIER_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEzN';

    /**
     * Verifier alternative value
     */
    private const VERIFIER_CHANGED_VALUE = 'cSZ4Io8fwyHweTvLcM-DRHWPD55zmk1TKinUjFaTcjM';

    /**
     * @var Code
     */
    private Code $code;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->code = new Code([
            self::CHALLENGE_METHOD => self::CHALLENGE_METHOD_VALUE,
            self::CHALLENGE => self::CHALLENGE_VALUE,
            self::VERIFIER => self::VERIFIER_VALUE,
        ]);
    }

    /**
     * Test getChallenge() method
     *
     * @return void
     */
    public function testGetChallenge(): void
    {
        $this->assertEquals(self::CHALLENGE_VALUE, $this->code->getChallenge());
    }

    /**
     * Test setChallenge() method
     *
     * @return void
     */
    public function testSetChallenge(): void
    {
        $this->code->setChallenge(self::CHALLENGE_CHANGED_VALUE);
        $this->assertEquals(self::CHALLENGE_CHANGED_VALUE, $this->code->getChallenge());
    }

    /**
     * Test getChallengeMethod() method
     *
     * @return void
     */
    public function testGetChallengeMethod(): void
    {
        $this->assertEquals(self::CHALLENGE_METHOD_VALUE, $this->code->getChallengeMethod());
    }

    /**
     * Test setChallengeMethod() method
     *
     * @return void
     */
    public function testSetChallengeMethod(): void
    {
        $this->code->setChallengeMethod(self::CHALLENGE_METHOD_CHANGED_VALUE);
        $this->assertEquals(self::CHALLENGE_METHOD_CHANGED_VALUE, $this->code->getChallengeMethod());
    }

    /**
     * Test getVerifier() method
     *
     * @return void
     */
    public function testGetVerifier(): void
    {
        $this->assertEquals(self::VERIFIER_VALUE, $this->code->getVerifier());
    }

    /**
     * Test setVerifier() method
     *
     * @return void
     */
    public function testSetVerifier(): void
    {
        $this->code->setVerifier(self::VERIFIER_CHANGED_VALUE);
        $this->assertEquals(self::VERIFIER_CHANGED_VALUE, $this->code->getVerifier());
    }
}

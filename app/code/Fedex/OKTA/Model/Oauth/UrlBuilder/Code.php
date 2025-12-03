<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth\UrlBuilder;

use Magento\Framework\DataObject;

class Code extends DataObject implements CodeInterface
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
     * @inheritDoc
     */
    public function getChallenge(): string
    {
        return $this->getData(self::CHALLENGE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setChallenge(string $challenge): CodeInterface
    {
        return $this->setData(self::CHALLENGE, $challenge);
    }

    /**
     * @inheritDoc
     */
    public function getChallengeMethod(): string
    {
        return $this->getData(self::CHALLENGE_METHOD) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setChallengeMethod(string $challengeMethod): CodeInterface
    {
        return $this->setData(self::CHALLENGE_METHOD, $challengeMethod);
    }

    /**
     * @inheritDoc
     */
    public function getVerifier(): string
    {
        return $this->getData(self::VERIFIER) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setVerifier(string $verifier): CodeInterface
    {
        return $this->setData(self::VERIFIER, $verifier);
    }
}

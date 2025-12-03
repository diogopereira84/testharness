<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth\UrlBuilder;

interface CodeInterface
{
    /**
     * Get Base64 URL-encoded SHA-256 hash of the code verifier
     *
     * @return string
     */
    public function getChallenge(): string;

    /**
     * Set Base64 URL-encoded SHA-256 hash of the code verifier
     *
     * @param string $challenge
     * @return CodeInterface
     */
    public function setChallenge(string $challenge): CodeInterface;

    /**
     * Get challenge method
     *
     * @return string
     */
    public function getChallengeMethod(): string;

    /**
     * Set challenge method
     *
     * @param string $challengeMethod
     * @return CodeInterface
     */
    public function setChallengeMethod(string $challengeMethod): CodeInterface;

    /**
     * Get Random URL-safe string with a minimum length of 43 characters
     *
     * @return string
     */
    public function getVerifier(): string;

    /**
     * Set Random URL-safe string with a minimum length of 43 characters
     *
     * @param string $verifier
     * @return CodeInterface
     */
    public function setVerifier(string $verifier): CodeInterface;
}

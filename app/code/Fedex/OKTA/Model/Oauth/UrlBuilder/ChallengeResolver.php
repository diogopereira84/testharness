<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth\UrlBuilder;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;

class ChallengeResolver
{
    /**
     * pack format value
     */
    private const PACK_FORMAT = 'H*';

    /**
     * Hash algorithm value
     */
    private const HASH_ALGORITHM = 'sha256';

    /**
     * Challenge method value
     */
    private const CHALLENGE_METHOD = 'S256';

    /**
     * Open SSL length value
     */
    private const OPENSSL_LENGTH = 32;

    /**
     * @param CodeFactory $codeFactory
     * @param Encoder $encoder
     * @param CodeStorage $codeStorage
     * @param RequestInterface $request
     */
    public function __construct(
        private CodeFactory $codeFactory,
        private Encoder $encoder,
        private CodeStorage $codeStorage,
        private RequestInterface $request
    )
    {
    }

    /**
     * Create code Challenger and verifier
     *
     * @return CodeInterface
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function resolve(): CodeInterface
    {
        $code = $this->codeFactory->create();
        if ($this->request->isGet()) {
            $random = bin2hex(openssl_random_pseudo_bytes(self::OPENSSL_LENGTH));
            $code->setChallengeMethod(self::CHALLENGE_METHOD);
            $code->setVerifier($this->encoder->encode(pack(self::PACK_FORMAT, $random)));
            $code->setChallenge(
                $this->encoder->encode(pack(self::PACK_FORMAT, hash(self::HASH_ALGORITHM, $code->getVerifier())))
            );
            $this->codeStorage->store($code->getVerifier());
        }

        return $code;
    }
}

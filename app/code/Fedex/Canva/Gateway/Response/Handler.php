<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Gateway\Response;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;

class Handler implements HandlerInterface
{
    public function __construct(
        private Json $json,
        private UserTokenFactory $factory,
        private JsonValidator $jsonValidator
    )
    {
    }
    /**
     * @inheritDoc
     */
    public function handle(Response $handlingSubject): UserToken
    {
        $userToken = $this->factory->create();

        if (201 == $handlingSubject->getStatusCode()) {
            $userToken->setStatus(true);
            $content = $handlingSubject->getBody()->getContents();
            if ($this->jsonValidator->isValid($content)) {
                $tokenData = $this->json->unserialize($content);

                if (isset($tokenData['output']['userTokenDetail']['accessToken'])) {
                    $userToken->setAccessToken($tokenData['output']['userTokenDetail']['accessToken']);
                }

                if (isset($tokenData['output']['userTokenDetail']['clientId'])) {
                    $userToken->setClientId($tokenData['output']['userTokenDetail']['clientId']);
                }

                if (isset($tokenData['output']['userTokenDetail']['expirationDateTime'])) {
                    $userToken->setExpirationDateTime($tokenData['output']['userTokenDetail']['expirationDateTime']);
                }
            }
        }

        return $userToken;
    }
}

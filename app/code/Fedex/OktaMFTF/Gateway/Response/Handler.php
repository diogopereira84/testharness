<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;

class Handler
{
    /**
     * @param Json $json
     * @param TokenFactory $factory
     * @param JsonValidator $jsonValidator
     */
    public function __construct(
        private Json $json,
        private TokenFactory $factory,
        private JsonValidator $jsonValidator
    )
    {
    }
    /**
     * @inheritDoc
     */
    public function handle(Response $handlingSubject): Token
    {
        $token = $this->factory->create();
        $content = $handlingSubject->getBody()->getContents();

        if (200 == $handlingSubject->getStatusCode() && $this->jsonValidator->isValid($content)) {
            $tokenData = $this->json->unserialize($content);
            if (is_array($tokenData)) {
                $token->setData($tokenData);
            }
        }

        return $token;
    }
}

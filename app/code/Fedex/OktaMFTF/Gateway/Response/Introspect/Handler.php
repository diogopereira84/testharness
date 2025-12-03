<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response\Introspect;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\OktaMFTF\Gateway\Response\IntrospectInterface;
use Fedex\OktaMFTF\Gateway\Response\IntrospectFactory;

class Handler
{
    /**
     * @param Json $json
     * @param IntrospectFactory $factory
     * @param JsonValidator $jsonValidator
     */
    public function __construct(
        private Json $json,
        private IntrospectFactory $factory,
        private JsonValidator $jsonValidator
    )
    {
    }

    /**
     * Handles response
     *
     * @param Response $handlingSubject
     * @return IntrospectInterface
     */
    public function handle(Response $handlingSubject): IntrospectInterface
    {
        $introspect = $this->factory->create();
        $content = $handlingSubject->getBody()->getContents();

        if (200 == $handlingSubject->getStatusCode() && $this->jsonValidator->isValid($content)) {
            $tokenData = $this->json->unserialize($content);
            if (is_array($tokenData)) {
                $introspect->setData($tokenData);
            }
        }

        return $introspect;
    }
}

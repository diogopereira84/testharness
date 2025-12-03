<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Response;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;

class Handler
{
    /**
     * @var TokenFactory
     */
    private TokenFactory $factory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var JsonValidator
     */
    private JsonValidator $jsonValidator;

    /**
     * @param Json $json
     * @param TokenFactory $factory
     * @param JsonValidator $jsonValidator
     */
    public function __construct(
        Json $json,
        TokenFactory $factory,
        JsonValidator $jsonValidator
    ) {
        $this->json = $json;
        $this->factory = $factory;
        $this->jsonValidator = $jsonValidator;
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

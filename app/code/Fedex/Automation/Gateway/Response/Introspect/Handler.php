<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Response\Introspect;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\Automation\Gateway\Response\IntrospectInterface;
use Fedex\Automation\Gateway\Response\IntrospectFactory;

class Handler
{
    /**
     * @var IntrospectFactory
     */
    private IntrospectFactory $factory;

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
     * @param IntrospectFactory $factory
     * @param JsonValidator $jsonValidator
     */
    public function __construct(
        Json $json,
        IntrospectFactory $factory,
        JsonValidator $jsonValidator
    ) {
        $this->json = $json;
        $this->factory = $factory;
        $this->jsonValidator = $jsonValidator;
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

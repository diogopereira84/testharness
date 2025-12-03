<?php

namespace Fedex\Punchout\Plugin\Webapi\Rest\Response;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Webapi\Rest\Response\RendererFactory;
use Magento\Framework\Webapi\Rest\Response\Renderer\Xml as XmlRenderer;

class RendererFactoryPlugin
{
    const XML_ENDPOINTS = [
        'fedex/eprocurement',
        'fedex/customer'
    ];

    public function __construct(
        protected Http         $request,
        protected XmlRenderer  $xmlRenderer
    )
    {
    }

    public function afterGet(RendererFactory $subject, $result)
    {
        $requestPath = $this->request->getRequestUri();
        foreach (self::XML_ENDPOINTS as $endpoint) {
            if (str_contains($requestPath, $endpoint)) {
                return $this->xmlRenderer;
            }
        }
        return $result;
    }
}

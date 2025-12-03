<?php
/**
 *  XML Renderer allows to format array or object as valid XML document.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Rest\Response;

/**
 * Renders response data in Xml format.
 */
class Xml extends \Magento\Framework\Webapi\Rest\Response\Renderer\Xml
{
    
    const MIME_TYPE = 'text/xml';
    
    /**
     * Get XML renderer MIME type.
     *
     * @return string
     */
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
    
    /**
     * Format object|array to valid XML.
     *
     * @param object|array|int|string|bool|float|null $data
     * @return string
     */
    public function render($data)
    {
        return $data;
    }

    
}


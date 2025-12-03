<?php
/**
 *  XML Renderer allows to format array or object as valid XML document.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Plugin\Magento\Framework\Webapi\Rest;


class Request
{
    
    /**
 * @param \Magento\Framework\Webapi\Rest\Request $subject
 * @param array $result
 * @return array
 */
    public function afterGetAcceptTypes(\Magento\Framework\Webapi\Rest\Request $subject, array $result)
    {
        if (($subject->getRequestUri() == '/rest/V1/fedex/eprocurement')
        || ($subject->getRequestUri() == '/index.php/rest/V1/fedex/eprocurement')) {
            $result = ['text/xml'];
        }
        return $result;
    }

    
}


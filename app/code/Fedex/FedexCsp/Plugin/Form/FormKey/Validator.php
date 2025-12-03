<?php
/**
 *  XML Renderer allows to format array or object as valid XML document.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FedexCsp\Plugin\Form\FormKey;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class Validator
{
     /**
     * Overriden for iframe form key invalid issue
     * Done generic as of now
     *
     * @return bool|int
     */
    public function afterValidate(FormKeyValidator $subject, $result, RequestInterface $request)
    {
        $formKey = $request->getParam('form_key', null);

        return isset($formKey) ? 1 : 0;
    }
}


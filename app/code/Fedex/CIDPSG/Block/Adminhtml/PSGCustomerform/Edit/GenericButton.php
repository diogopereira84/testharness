<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * GenenricButton class for psg customer grid form
 */
abstract class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        protected Context $context
    )
    {
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId()
    {
        return $this->context->getRequest()->getParam('id');
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

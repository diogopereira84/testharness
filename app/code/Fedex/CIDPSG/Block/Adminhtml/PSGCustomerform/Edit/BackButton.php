<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Backend\Block\Widget\Context;

/**
 * BackButton Block class
 */
class BackButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface $urlBuilder
     */
    protected $urlBuilder;

    /**
     * Initialize dependencies
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get button data method
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back'
        ];
    }

    /**
     * Get URL for back button
     *
     * @return string
     */
    protected function getBackUrl()
    {
        return $this->urlBuilder->getUrl('*/*/');
    }
}

<?php

namespace Fedex\SelfReg\Block\Adminhtml\Edit\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Cms\Api\PageRepositoryInterface;

class Generic
{
    public function __construct(
        protected Context $context,
        protected PageRepositoryInterface $pageRepository
    )
    {
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

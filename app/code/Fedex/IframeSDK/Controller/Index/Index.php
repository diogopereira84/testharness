<?php

namespace Fedex\IframeSDK\Controller\Index;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        protected RequestInterface $request,
        protected RedirectFactory $redirectFactory,
        protected UrlInterface $url,
        protected ToggleConfig $toggleConfig
    )
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->request->getParams();
        if ($data && isset($data['siteName']) && empty($data['siteName']) && isset($data['productType']) && $data['productType'] == 'COMMERCIAL_PRODUCT') {
            $redirect = $this->redirectFactory->create();
            $sku = $data['id'] ?? "";
            $params = ['sku' => $sku , 'configurationType' => 'customize'];
            $url = $this->url->getUrl('catalogmvp/configurator/index', $params);
            $redirect->setUrl($url);
            return $redirect;
        }
        return $this->_pageFactory->create();
    }

}

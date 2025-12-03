<?php
declare(strict_types=1);
namespace Fedex\Nuance\Controller\Nuance;

use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $redirect
     * @param NuanceInterface $nuanceInterface
     */
    public function __construct(
        private readonly Context            $context,
        private readonly PageFactory        $resultPageFactory,
        private readonly Http               $redirect,
        private readonly NuanceInterface    $nuanceInterface,
    ) {
        parent::__construct($context);
    }

    /**
     * @return Http|Page
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if ($this->nuanceInterface->isEnabledNuanceForCompany()) {
            /** @var Page $resultPage */
            $resultPage = $this->resultPageFactory->create(true);
            $resultPage->addHandle('nuance_nuance_index');
            $resultPage->setHeader('Content-Type', 'text/html; charset=utf-8');
            $resultPage->setHeader('Cache-Control', 'max-age=3600, private');

            return $resultPage;
        }

        $noRouteUrl = $this->context->getUrl()->getUrl();
        $this->redirect->setRedirect($noRouteUrl, 301);
        return $this->redirect;
    }
}

<?php

namespace Fedex\B2b\Plugin\Controller\Adminhtml\Quote;

use Magento\NegotiableQuote\Controller\Adminhtml\Quote\View as Subject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Provider;
use Magento\NegotiableQuote\Model\Cart;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ActionFlag;

/**
 * Plugin to view the quote in admin.
 */
class View
{
    /**
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param Provider $messageProvider
     * @param Cart $cart
     * @param Quote $negotiableQuoteHelper
     * @param SessionManagerInterface $sessionManagerInterface
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param ResultFactory $resultFactory
     * @param ToggleConfig $toggleConfig
     * @param ActionFlag $actionFlag
     */
    public function __construct(
        private LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        private Provider $messageProvider,
        private Cart $cart,
        private Quote $negotiableQuoteHelper,
        private SessionManagerInterface $sessionManagerInterface,
        private ManagerInterface $messageManager,
        private RedirectFactory $resultRedirectFactory,
        private CartRepositoryInterface $cartRepositoryInterface,
        private ResultFactory $resultFactory,
        private ToggleConfig $toggleConfig,
        private ActionFlag $actionFlag
    ) {
     }

    public function aroundExecute(Subject $subject, callable $proceed)
    {
        $this->sessionManagerInterface->start();
        $this->sessionManagerInterface->setAdminQuoteView(1);
        $quoteId = $subject->getRequest()->getParam('quote_id');
        try {
            $quote = $this->cartRepositoryInterface->get($quoteId, ['*']);
            $this->cart->removeAllFailed();
            $this->setNotifications($quote);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                                ' Requested quote was not found. Quote Id = ' . $quoteId);
            $this->messageManager->addError(__('Requested quote was not found'));
            $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' An error occurred on the server. Quote Id = ' . $quoteId . '. Error Message: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred on the server. %1', $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }
        $resultPage = $this->initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Quote #%1', $quoteId));
        return $resultPage;
    }

     /**
     * Set notifications for merchant.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    private function setNotifications(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $notifications = $this->messageProvider->getChangesMessages($quote);

        foreach ($notifications as $message) {
            if ($message) {
                $this->messageManager->addWarningMessage($message);
            }
        }

        if ($this->negotiableQuoteHelper->isLockMessageDisplayed()) {
            $this->messageManager->addWarningMessage(
                __('This quote is currently locked for editing. It will become available once released by the buyer.')
            );
        }
    }

    /**
     * Init layout, menu and breadcrumb
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initAction()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Sales::sales_order');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Quotes'), __('Quotes'));
        return $resultPage;
    }
}

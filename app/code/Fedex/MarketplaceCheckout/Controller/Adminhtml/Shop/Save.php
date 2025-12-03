<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCheckout
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Controller\Adminhtml\Shop;

use Magento\Backend\App\Action;
use Mirakl\Core\Model\Shop;
use Magento\Backend\Model\Session;
use \Magento\Framework\Serialize\Serializer\Json;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @param Action\Context $context
     * @param Shop $shopModel
     * @param Session $adminsession
     * @param Json $json
     */
    public function __construct(
        Action\Context $context,
        protected Shop           $shopModel,
        protected Session        $adminsession,
        protected Json           $json
    ) {
        parent::__construct($context);
    }

    /**
     * Save shop record action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $shippingMethod = null;
        if ($data) {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $this->shopModel->load($id);
            }
            if (!empty($data['shipping_method'])) {
                $shippingMethod = $this->json->serialize($data['shipping_method']);
            }
            $this->shopModel->setShippingMethods($shippingMethod);
            try {
                $this->shopModel->save();
                $this->messageManager->addSuccess(__('Shipping Methods have been saved.'));
                $this->adminsession->setFormData(false);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
            }
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
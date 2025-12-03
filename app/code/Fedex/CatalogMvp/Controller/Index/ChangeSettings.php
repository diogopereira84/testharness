<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

 declare(strict_types=1);

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
/**
 * Class ChangeRequest
 * Handle the ChangeRequest of the Catalog
 */
class ChangeSettings  implements ActionInterface
{

    /**
     * ChangeRequest Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $pageFactory
     * @param MvpHelper $mvpHelper
     */
    public function __construct(
        protected Context $context,
        readonly private  JsonFactory $resultJsonFactory,
        readonly private PageFactory $pageFactory,
        readonly private MvpHelper $mvpHelper
    )
    {
    }

    /**
     * Execute method
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute() : \Magento\Framework\Controller\Result\Json
    {
        $result = ['output' => '', 'status' => false, 'message' => ''];
        $resultJsonData = $this->resultJsonFactory->create();
        try {
            $productSku = $this->context->getRequest()->getParam('sku');
            $resultPage = $this->pageFactory->create();
            $block = $resultPage->getLayout()
                ->createBlock(
                    'Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup',
                    'catalog.mvp.product.setting.admin',
                        [
                            'data' => [
                                'mvphelper_mvp_setting_popup' => $this->mvpHelper
                            ]
                        ]
                    )
                ->setTemplate('Fedex_CatalogMvp::shared-catalog-product-settings-form-settings.phtml')
                ->setData('setting_sku', $productSku)
                ->toHtml();
            // @codeCoverageIgnoreStart
            $result = ['output' => $block, 'status' => true, 'message' => __('Sku updated in customer session')];
            // @codeCoverageIgnoreEnd
        } catch (\Exception $e) {
            $result = ['output' => '', 'status' => false, 'message' => __($e->getMessage())];
        }
        
        return $resultJsonData->setData($result);
    }
}

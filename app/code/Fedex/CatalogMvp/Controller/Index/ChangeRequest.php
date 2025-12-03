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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Customer\Model\SessionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class ChangeRequest
 * Handle the ChangeRequest of the Catalog
 */
class ChangeRequest implements ActionInterface
{
    private const PENDING_REVIEW_FLAG = 'is_pending_review';

    /**
     * ChangeRequest Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param EmailHelper $emailHelper
     * @param SessionFactory $sessionFactory
     * @param Category $categoryModel
     * @param CatalogMvp $catalogMvp
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param CategoryRepository $categoryRepository,
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        readonly private  JsonFactory $resultJsonFactory,
        readonly private ProductRepositoryInterface $productRepository,
        readonly private EmailHelper $emailHelper,
        readonly private SessionFactory $sessionFactory,
        readonly private Category $categoryModel,
        readonly private CatalogMvp $catalogMvp,
        protected CategoryRepository $categoryRepository,
        protected ToggleConfig $toggleConfig
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
        $result = ['status' => false, 'message' => ''];
        $productId = $this->request->getParam('id');
        $specialInstruction = $this->request->getParam('specialInstruction');
        $userWorkSpace = $this->request->getParam('userWorkSpace');
        $resultJsonData = $this->resultJsonFactory->create();
        try {
            $product = $this->productRepository->get($productId);
            $extProd = $product->getExternalProd();
            if (!empty($extProd)) {
                $prodata = json_decode($extProd, true);
                $prodata['priceable'] = false;
                if (!empty($userWorkSpace)) {
                    $prodata['userWorkspace']['files'] = $userWorkSpace;
                    $prodata['userWorkspace']['projects'] = [];
                }
                $properties = $prodata['properties'] ?? [];
                foreach ($properties as $k => $prop) {
                    if ($prop['name'] == 'USER_SPECIAL_INSTRUCTIONS') {
                        $prodata['properties'][$k]['value']
                            = trim($specialInstruction);
                    }
                }

                $this->updateProductData($product, $prodata);

                $this->catalogMvp->insertProductActivity($product->getId(), "UPDATE");

                $result = ['status' => true, 'message' => __('Special Instruction Updated')];
            }
        } catch (\Exception $e) {
            $result = ['status' => false, 'message' => __($e->getMessage())];
        }

        return $resultJsonData->setData($result);
    }

    /**
     * Update product data
     *
     * @param $product
     * @param array $prodata
     * @return void
     */
    public function updateProductData($product, $prodata) : void
    {
        $product->setSentToCustomer(0);
        $product->setPublished(false);
        $product->setData(self::PENDING_REVIEW_FLAG, 1);
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_217181')) {
            $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        }  
        $externalProd = json_encode($prodata);
        $product->setExternalProd($externalProd);
        $this->productRepository->save($product);
        $this->sendPendingReviewEmail($product, $prodata);
    }
    /**
     * Send pending review email method
     *
     * @param object $product
     * @param array $prodata
     * @return void
     */
    private function sendPendingReviewEmail($product, $prodata) : void
    {
        $productData = [];
        $categoryUrl = '';
        $currentCategoryIds = $product->getCategoryIds();
        if (is_array($currentCategoryIds) && !empty($currentCategoryIds)) {
            $categoryId = end($currentCategoryIds);
            $categoryObj = $this->categoryRepository->get($categoryId);
            $categoryUrl = $categoryObj->getUrl();
        }
        $customerSession = $this->sessionFactory->create();
        $customerEmail = !empty($customerSession->getCustomer()->getSecondaryEmail()) ? $customerSession->getCustomer()->getSecondaryEmail() : $customerSession->getCustomer()->getEmail();
        $productData['product_name'] = $product->getName();
        $productData['folder_path'] = !empty($product->getFolderPath()) ? $product->getFolderPath() : $categoryUrl;
        $productData['item_name'] = $product->getName();
        $productData['added_by'] = $customerSession->getCustomer()->getName();
        $productData['special_instruction'] = $prodata ? $this->emailHelper->getSpecialInstruction($prodata) : '';
        $productData['product_id'] = $product->getId();
        $productData['company_id'] = $customerSession->getCustomerCompany();
        $productData['customer_email'] = $customerEmail;
        $this->emailHelper->sendReadyForReviewEmail($productData);
    }
}

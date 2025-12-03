<?php
/**
 *
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Controller\Adminhtml\Group;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Controller\Adminhtml\Group\NewAction;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\Model\View\Result\Page;
use Magento\Customer\Block\Adminhtml\Group\Edit;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class NewGroup extends NewAction
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var GroupInterfaceFactory
     */
    protected $groupDataFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * Initialize Group Controller
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupDataFactory,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        protected ToggleConfig $toggleConfig,
        protected CookieManagerInterface  $cookieManager,
        protected SessionManagerInterface $sessionManager,
        protected CookieMetadataFactory $cookieMetadataFactory
    ) {
                parent::__construct(
                    $context,
                    $coreRegistry,
                    $groupRepository,
                    $groupDataFactory,
                    $resultForwardFactory,
                    $resultPageFactory
                );

    }
    /**
     * Initialize current group and set it in the registry.
     *
     * @return int
     */
    protected function _initGroup()
    {
        $groupId = $this->getRequest()->getParam('id');
        $this->_coreRegistry->register(RegistryConstants::CURRENT_GROUP_ID, $groupId);
        $this->cookieManager->setPublicCookie('group_id',$groupId);
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(86400)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());
        $this->cookieManager->setPublicCookie(
            'group_id',
            $groupId,
            $metadata
        );
        return $groupId;
    }

    /**
     * Edit or create customer group.
     *
     * @return Page
     */
    public function execute()
    {
        $groupId = $this->_initGroup();
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Customer::customer_group');
        $resultPage->getConfig()->getTitle()->prepend(__('Customer Groups'));
        $resultPage->addBreadcrumb(__('Customers'), __('Customers'));
        $resultPage->addBreadcrumb(__('Customer Groups'), __('Customer Groups'), $this->getUrl('customer/group'));

        if ($groupId === null) {
            $resultPage->addBreadcrumb(__('New Group'), __('New Customer Groups'));
            $resultPage->getConfig()->getTitle()->prepend(__('New Customer Group'));
        } else {
            $resultPage->addBreadcrumb(__('Edit Group'), __('Edit Customer Groups'));
            $resultPage->getConfig()->getTitle()->prepend(
                $this->groupRepository->getById($groupId)->getCode()
            );
        }

        $resultPage->getLayout()->addBlock(Edit::class);

        return $resultPage;
    }
}

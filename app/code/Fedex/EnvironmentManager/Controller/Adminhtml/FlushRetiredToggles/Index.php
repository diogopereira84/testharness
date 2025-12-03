<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Controller\Adminhtml\FlushRetiredToggles;

use Fedex\EnvironmentManager\Api\RetiredToggleManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Index extends Action
{
    /**
     * @param Context $context
     * @param ManagerInterface $managerInterface
     * @param RetiredToggleManager $retiredToggleManager
     */
    public function __construct(
        Context $context,
        private ManagerInterface $managerInterface,
        private RetiredToggleManager $retiredToggleManager
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
            try {
                $retiredTogglesToBeFlushed = $this->getRequest()->getParam('retired_toggles_to_be_flushed');
                if ($retiredTogglesToBeFlushed) {

                    $deletedRetiredToggles = $this->retiredToggleManager->flushSelectedRetiredToggles($retiredTogglesToBeFlushed);

                    if ($deletedRetiredToggles !== '') {

                        $this->managerInterface->addSuccessMessage('These toggles have been flushed from core_config_data table: ' . $deletedRetiredToggles);
                    } else {

                        $this->managerInterface->addWarningMessage('There was no toggles available to be flushed.');
                    }
                } else {

                    $this->managerInterface->addWarningMessage('No retired toggles were selected.');
                }
            } catch (\Exception $e) {
                $this->managerInterface->addWarningMessage($e->getMessage());
            }
        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}

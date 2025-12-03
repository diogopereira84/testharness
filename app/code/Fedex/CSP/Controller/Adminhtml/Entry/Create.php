<?php

declare(strict_types=1);

namespace Fedex\CSP\Controller\Adminhtml\Entry;

use Fedex\CSP\Model\CspManagement;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Create implements ActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJson;

    /**
     * Create class constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CspManagement $cspManagement
     */
    public function __construct(
        private Context $context,
        JsonFactory $resultJsonFactory,
        private CspManagement $cspManagement
    ) {
        $this->resultJson = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $serializedUpdatedValue = $this->cspManagement->updatedEntries();

            $this->cspManagement->saveEntries($serializedUpdatedValue);

            return $result->setData([
                'status' => true,
                'entries_value' => $serializedUpdatedValue
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'error'     => $e->getMessage()
            ]);
        }
    }
}

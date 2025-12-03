<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Controller\Adminhtml\Options;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;

class AssignGroup extends \Magento\Backend\App\Action
{
    /**
     * @var array
     */
    protected $results = [];
    /**
     * @var int
     */
    private $resultsCount;

    /**
     * Object initialization.
     *
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param GroupFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param Http $request
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        protected GroupFactory $collectionFactory,
        protected LoggerInterface $logger,
        private Http $request
    ) {
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    /**
     * Executes request and return json data
     *
     * @return json
     */
    public function execute()
    {
        $returnArray            = [];
        $returnArray["success"] = false;
        $returnArray["message"] = "";
        $resultJson             = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $wholeData              = $this->request->getParams();

        if ($wholeData) {
            try {
                $query       = "";
                $pageSize    = 10;
                $pageNumber  = $wholeData["page"] ?? 1;
                $query       = trim($wholeData["q"] ?? "");

                $customerList = [];
                $customerList = $this->getCustomerGroups($query, $pageSize, $pageNumber);

                $returnArray["success"]    = true;
                $returnArray["results"]    = $customerList;
                $returnArray["totalCount"] = $this->resultsCount;
                $noOfPages = ($this->resultsCount > 0)?$this->resultsCount/$pageSize : 1;
                $returnArray["noOfPages"]  = ceil($noOfPages);

                $resultJson->setData($returnArray);
                return $resultJson;
            } catch (\Exception $e) {
                $returnArray["message"] = $e->getMessage();
                $resultJson->setData($returnArray);
                return $resultJson;
            }
        } else {
            $returnArray["message"] = __("Invalid Request");
            $resultJson->setData($returnArray);
            return $resultJson;
        }
    }

    /**
     * Get customer groups
     *
     * @param string $query
     * @param int $pageSize
     * @param int $pageNumber
     * @return array
     */
    public function getCustomerGroups($query = "", $pageSize = 20, $pageNumber = 1)
    {
        try {
            $customerGroups = $this->collectionFactory->create();

            if (!empty($query)) {
                $customerGroups->addFieldToFilter('customer_group_code', ['like'=>'%'.$query.'%']);
            }
            $data = (!empty($customerGroups)) ? $customerGroups->getData() : [];
            if (!empty($data)) {
                foreach ($data as $index => $value) {
                    $result          = [];
                    $groupCode = ($value["customer_group_code"] ?? "");
                    $result["id"]    = $value["customer_group_id"] ?? "";
                    $result["text"]  = $groupCode;
                    $this->results[] = $result;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error Getting Customer Data: ' . $e->getMessage());
        }

        return $this->results;
    }
}

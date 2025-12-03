<?php
namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Psr\Log\LoggerInterface;

class OrderApproversNames extends Column
{
    protected $customerCollectionFactory;
    protected $logger;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $customerCollectionFactory,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->logger = $logger;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        // Batch loading approach: collect all user IDs first
        $allUserIds = [];
        $itemUserMapping = [];

        foreach ($dataSource['data']['items'] as $index => &$item) {
            if (isset($item['order_approvers_names'])) {
                $userIds = $this->parseIds($item['order_approvers_names']);
                if (!empty($userIds)) {
                    $itemUserMapping[$index] = $userIds;
                    $allUserIds = array_merge($allUserIds, $userIds);
                }
            }
        }

        // Remove duplicates and get unique user IDs
        $allUserIds = array_unique($allUserIds);

        if (empty($allUserIds)) {
            // No user IDs found, set empty arrays for all items
            foreach ($dataSource['data']['items'] as &$item) {
                $item['order_approvers_names'] = [];
            }
            return $dataSource;
        }

        // Single database query to fetch all user names at once
        $userNamesMap = $this->batchFetchUserNames($allUserIds);

        // Map the fetched names back to each row
        foreach ($dataSource['data']['items'] as $index => &$item) {
            if (isset($itemUserMapping[$index])) {
                $userIds = $itemUserMapping[$index];
                $names = [];
                
                foreach ($userIds as $userId) {
                    if (isset($userNamesMap[$userId])) {
                        $names[] = $userNamesMap[$userId];
                    }
                }
                
                $item['order_approvers_names'] = $names;
            } else {
                $item['order_approvers_names'] = [];
            }
        }

        return $dataSource;
    }

    private function parseIds($content)
    {
        if (is_string($content)) {
            // Handle JSON format
            if (strpos($content, '[') === 0 || strpos($content, '{') === 0) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return array_filter($decoded);
                }
            }
            // Handle comma-separated
            $ids = array_filter(explode(',', $content));
            return array_map('trim', $ids);
        } elseif (is_array($content)) {
            return array_filter($content);
        }
        
        return [];
    }

    /**
     * Batch fetch all user names in a single database query
     * 
     * @param array $userIds
     * @return array Map of user_id => full_name
     */
    private function batchFetchUserNames(array $userIds)
    {
        try {
            // Single query to fetch all user names
            $collection = $this->customerCollectionFactory->create();
            $collection->addAttributeToSelect(['firstname', 'lastname']);
            $collection->addFieldToFilter('entity_id', ['in' => $userIds]);
            
            $userNamesMap = [];
            foreach ($collection as $customer) {
                $firstName = $customer->getFirstname() ?: '';
                $lastName = $customer->getLastname() ?: '';
                
                $fullName = trim($firstName . ' ' . $lastName);
                if ($fullName) {
                    $userNamesMap[$customer->getId()] = $fullName;
                }
            }
            
            $this->logger->info(
                'Batch fetched approver names from database',
                [
                    'requested_user_ids' => count($userIds),
                    'found_users' => count($userNamesMap),
                    'user_ids' => $userIds
                ]
            );
            
            return $userNamesMap;
            
        } catch (\Exception $e) {
            // Log error using PSR-3 LoggerInterface
            $this->logger->error(
                'Error batch fetching approver names from DB: ' . $e->getMessage(),
                [
                    'user_ids' => $userIds,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
        
        return [];
    }
}
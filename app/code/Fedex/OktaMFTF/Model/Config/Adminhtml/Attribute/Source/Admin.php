<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model\Config\Adminhtml\Attribute\Source;

use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;

class Admin implements \Magento\Framework\Data\OptionSourceInterface
{
    private array $options = [];

    public function __construct(
        private UserCollectionFactory $userCollectionFactory
    )
    {
    }

    public function toOptionArray()
    {
        if (empty($this->options)) {
            $users = $this->userCollectionFactory->create();
            foreach ($users as $user) {
                $this->options[] = [
                    'value' => $user->getUserId(), 'label' => $user->getName()
                ];
            }
        }
        array_unshift($this->options, ['value' => '0', 'label' => __('None')]);
        return $this->options;
    }
}

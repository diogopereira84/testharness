<?php

namespace Fedex\CacheFlush\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class CacheFlush implements ObserverInterface
{
    /**
     * CacheFlush Constructor.
     *
     * @param Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     *
     * return void
     */
    public function __construct(
        protected TypeListInterface $cacheTypeList
    )
    {
    }

    /**
     * Execute method to flush cache
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $types = [];

        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    }
}

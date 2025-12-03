<?php
declare(strict_types=1);

namespace Fedex\ProductCustomAtrribute\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class SaveDltData implements ObserverInterface
{
    /**
     * SaveDltData constructor.
     * @param RequestInterface
     */
    public function __construct(
        protected RequestInterface $request
    )
    {
    }
 
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getDataObject();
        $requestPost = $this->request->getPost();
        $requestPostData = $requestPost['product'];
        if (isset($requestPostData['dlt_threshold']) && !empty($requestPostData['dlt_threshold'])) {
            $dltdata =  json_encode($requestPostData['dlt_threshold']);
            $product->setData('dlt_thresholds', $dltdata);
        } else {
            $product->setData('dlt_thresholds', null);
        }
    }
}

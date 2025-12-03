<?php

namespace Fedex\Purchaseorder\Api;

use \Magento\Framework\Exception\NoSuchEntityException;

interface OrderUpdateInterface{

	/**
     * @param int $orderId
     * @return void
     */
	function updateOrderStatus($orderId);

}

<?php

namespace Fedex\Purchaseorder\Api;

use \Magento\Framework\Exception\NoSuchEntityException;

interface PoInterface{

    /**
     *
     *get customer by unique id
     * @api
     * @param $request
     * @return boolean|array
     */
	function getPoxml($companyData,$inputXml);


}

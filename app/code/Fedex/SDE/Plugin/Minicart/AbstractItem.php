<?php

namespace Fedex\SDE\Plugin\Minicart;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Checkout\CustomerData\AbstractItem as MagentoAbstractItem;

/**
 * Class AbstractItem
 *
 * This class will be responsible for changing product data in minicart
 */
class AbstractItem
{
    /**
     * AbstractItem Construct
     *
     * @param SdeHelper $sdeHelper
     * @return void
     */
    public function __construct(
        private SdeHelper $sdeHelper
    )
    {
    }

    /**
     * After preparing minicart item data, check if its SDE and mask image is uploaded in backend
     *
     * @param MagentoAbstractItem $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemData($subject, $result): array
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            $sdeMaskImage = $this->sdeHelper->getSdeMaskSecureImagePath();
            if ($sdeMaskImage) {
                $result['product_image']['src'] = $sdeMaskImage;
            }
        }

        return $result;
    }
}

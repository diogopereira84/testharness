<?php
declare(strict_types=1);

namespace Fedex\FXOPricing\Plugin;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item\Option;
use Psr\Log\LoggerInterface;

class QuoteItemOptionPlugin
{
    /** @var int  */
    const ZERO_PRICE = 0;
    const CODE_TO_SKIP = [
        'bundle_identity',
        'bundle_instance_id_hash'
    ];

    /**
     * @param Json $jsonSerializer
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Json $jsonSerializer,
        private ToggleConfig $toggleConfig,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Modify the value before the beforeSave method is executed.
     *
     * @param Option $subject
     * @return void
     */
    public function beforeBeforeSave(Option $subject)
    {
        try {
            if (!$this->toggleConfig->getToggleConfigValue('hawks_d209598')) {
                return;
            }
            $value = $subject->getValue();
            if ($value) {
                if($this->toggleConfig->getToggleConfigValue('tiger_d238132')
                    && in_array($subject->getCode(), self::CODE_TO_SKIP)){
                    return;
                }
                $optionDataArray = $this->jsonSerializer->unserialize($value);
                if (isset($optionDataArray['external_prod'])) {
                    foreach ($optionDataArray['external_prod'] as &$externalProd) {
                        if (isset($externalProd['externalSkus'])) {
                            $externalSkusArray = [];
                            foreach ($externalProd['externalSkus'] as &$externalSku) {
                                $price = $externalSku['unitPrice'] ?? null;
                                if ($price !== self::ZERO_PRICE) {
                                    $externalSkusArray[] = $externalSku;
                                }
                            }
                            $externalProd['externalSkus'] = $externalSkusArray;
                        }
                    }
                }
                $value = $this->jsonSerializer->serialize($optionDataArray);
            }
            $subject->setValue($value);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

<?php
declare(strict_types=1);
namespace Fedex\FXOCMConfigurator\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CustomerLogin implements ObserverInterface
{

    /**
     * Constructor
     * @param ToggleConfig $toggleConfig
     * @param Batchupload $batchupload
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected Batchupload $batchupload
    )
    {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($customer->getId() &&
        $this->toggleConfig->getToggleConfigValue('batch_upload_toggle') &&
        $this->toggleConfig->getToggleConfigValue('fxo_cm_toggle')) {
           $this->batchupload->updateUserworkspaceDataAfterLogin($customer->getId());
        }
    }
}

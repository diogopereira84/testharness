<?php
declare(strict_types=1);

namespace Fedex\AccountValidation\Block\Adminhtml;

use Fedex\AccountValidation\Model\AccountValidation as AccountValidationModel;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class AccountValidationBlock extends Template
{
    protected $_template = 'Fedex_AccountValidation::account-validate.phtml';

    /**
     * @param Context $context
     * @param AccountValidationModel $accountValidationModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly AccountValidationModel $accountValidationModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the account validation URL
     *
     * @return string
     */
    public function getAccountValidationUrl(): string
    {
        return $this->accountValidationModel->getAccountValidationUrl();
    }

    /**
     * Check if the toggle E456656 is enabled
     *
     * @return bool
     */
    public function isToggleE456656Enabled(): bool
    {
        return (bool)$this->accountValidationModel->isToggleE456656Enabled();
    }
}

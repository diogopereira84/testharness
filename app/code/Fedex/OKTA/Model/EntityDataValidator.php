<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model;

use Magento\Framework\Exception\LocalizedException;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Psr\Log\LoggerInterface;

class EntityDataValidator
{
    /**
     * Token data keys
     */
    public const KEY_EMAIL          = 'email';
    public const KEY_FIRSTNAME      = 'given_name';
    public const KEY_LASTNAME       = 'family_name';
    public const KEY_GROUPS         = 'groups';
    public const KEY_SUB            = 'sub';

    /**
     * EntityDataValidator constructor.
     * @param array $requiredFields
     * @param LoggerInterface $logger
     */
    public function __construct(
        private array $requiredFields,
        private OktaHelper $oktaHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param $entityData
     * @return bool
     * @throws LocalizedException
     */
    public function validate(array $entityData): bool
    {
        foreach ($this->requiredFields as $requiredField) {
            if (!isset($entityData[$requiredField])) {
                if ($this->oktaHelper->isToggleForEnhancedLoggingEnabled()) {
                    $this->logger->error(__METHOD__.':'.__LINE__.' Error during authorization, Required field '
                        . $requiredField . ' is empty.');
                } else {
                    $this->logger->error(__METHOD__.':'.__LINE__.' Error during authorization.');
                }
                throw new LocalizedException(__('Error during authorization.'));
            }
        }

        return true;
    }
}

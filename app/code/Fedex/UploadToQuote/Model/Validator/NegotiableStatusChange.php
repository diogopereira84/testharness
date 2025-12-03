<?php
declare(strict_types=1);

namespace Fedex\UploadToQuote\Model\Validator;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote as NegotiableQuoteResource;
use Magento\NegotiableQuote\Model\Validator\NegotiableStatusChange as NegotiableStatusChangeCore;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

/**
 * Validator for changing negotiable quote status.
 */
class NegotiableStatusChange extends NegotiableStatusChangeCore implements ValidatorInterface
{
    const D208156_TOGGLE = 'tiger_d208156';

    /**
     * @var NegotiableQuoteInterfaceFactory
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuoteResource
     */
    private $negotiableQuoteResource;

    /**
     * @var ValidatorResultFactory
     */
    private $validatorResultFactory;

    /**
     * @var array
     */
    private $allowChangesFromCoreFile = [
        '' => [
            NegotiableQuoteInterface::STATUS_CREATED,
            NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN,
        ],
        NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
        ],
        NegotiableQuoteInterface::STATUS_CREATED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
        ],
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => [
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
        ],
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
        ],
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_ORDERED => [],
        NegotiableQuoteInterface::STATUS_EXPIRED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_DECLINED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_CLOSED => [],
        AdminConfigHelper::NBC_PRICED => [
            AdminConfigHelper::NBC_PRICED,
            AdminConfigHelper::NBC_SUPPORT,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        AdminConfigHelper::NBC_SUPPORT => [
            AdminConfigHelper::NBC_SUPPORT,
            AdminConfigHelper::NBC_PRICED,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ]
    ];

    /**
     * @var array
     */
    private $allowChanges = [
        '' => [
            NegotiableQuoteInterface::STATUS_CREATED,
            NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN,
        ],
        NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
        ],
        NegotiableQuoteInterface::STATUS_CREATED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
        ],
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => [
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
            NegotiableQuoteInterface::STATUS_DECLINED,
        ],
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_EXPIRED,
        ],
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            NegotiableQuoteInterface::STATUS_DECLINED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_ORDERED => [],
        NegotiableQuoteInterface::STATUS_EXPIRED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        NegotiableQuoteInterface::STATUS_DECLINED => [
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
        ],
        NegotiableQuoteInterface::STATUS_CLOSED => [],
        AdminConfigHelper::NBC_PRICED => [
            AdminConfigHelper::NBC_PRICED,
            AdminConfigHelper::NBC_SUPPORT,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ],
        AdminConfigHelper::NBC_SUPPORT => [
            AdminConfigHelper::NBC_SUPPORT,
            AdminConfigHelper::NBC_PRICED,
            NegotiableQuoteInterface::STATUS_ORDERED,
            NegotiableQuoteInterface::STATUS_CLOSED,
        ]
    ];

    /**
     * @param NegotiableQuoteInterfaceFactory $negotiableQuoteFactory
     * @param NegotiableQuoteResource $negotiableQuoteResource
     * @param ValidatorResultFactory $validatorResultFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        NegotiableQuoteInterfaceFactory $negotiableQuoteFactory,
        NegotiableQuoteResource $negotiableQuoteResource,
        ValidatorResultFactory $validatorResultFactory,
        protected ToggleConfig $toggleConfig
    ) {
        $this->negotiableQuoteFactory = $negotiableQuoteFactory;
        $this->negotiableQuoteResource = $negotiableQuoteResource;
        $this->validatorResultFactory = $validatorResultFactory;
        parent::__construct($negotiableQuoteFactory, $negotiableQuoteResource, $validatorResultFactory);
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data)
    {
        $result = $this->validatorResultFactory->create();
        $negotiableQuote = $this->retrieveNegotiableQuote($data);
        if (empty($negotiableQuote)) {
            return $result;
        }
        $oldQuote = $this->negotiableQuoteFactory->create();
        $this->negotiableQuoteResource->load($oldQuote, $negotiableQuote->getQuoteId());

        $oldQuoteStatus = $oldQuote->getData(NegotiableQuoteInterface::QUOTE_STATUS);
        if($this->toggleConfig->getToggleConfigValue(self::D208156_TOGGLE)) {
            $allowedStatuses = $this->allowChanges[$oldQuoteStatus];
        } else {
            $allowedStatuses = $this->allowChangesFromCoreFile[$oldQuoteStatus];
        }
        $negotiableQuoteStatus = $negotiableQuote->getData(NegotiableQuoteInterface::QUOTE_STATUS);
        if ($negotiableQuote->hasData(NegotiableQuoteInterface::QUOTE_STATUS)
            && $negotiableQuoteStatus != $oldQuoteStatus
            && !in_array($negotiableQuoteStatus, $allowedStatuses)
        ) {
            $result->addMessage(__('You cannot update the quote status.'));
        }

        return $result;
    }

    /**
     * Retrieve negotiable quote from $data.
     *
     * @param array $data
     * @return NegotiableQuoteInterface|null
     */
    private function retrieveNegotiableQuote(array $data)
    {
        $negotiableQuote = !empty($data['negotiableQuote']) ? $data['negotiableQuote'] : null;
        if (!$negotiableQuote && !empty($data['quote']) && $data['quote']->getExtensionAttributes()
            && $data['quote']->getExtensionAttributes()->getNegotiableQuote()
            && $data['quote']->getExtensionAttributes()->getNegotiableQuote()->getIsRegularQuote()
        ) {
            $negotiableQuote = $data['quote']->getExtensionAttributes()->getNegotiableQuote();
        }

        return $negotiableQuote;
    }
}

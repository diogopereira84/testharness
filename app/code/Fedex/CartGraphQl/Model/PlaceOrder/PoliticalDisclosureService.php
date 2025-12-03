<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder;

use Fedex\CartGraphQl\Api\PoliticalDisclosureServiceInterface;
use Magento\Framework\Exception\InputException;
use Magento\Sales\Model\OrderFactory;
use Fedex\CartGraphQl\Model\Region\RegionData;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;
use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CartGraphQl\Model\PlaceOrder\DTO\PoliticalDisclosureDTOFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\PlaceOrder\DTO\PoliticalDisclosureDTO;

class PoliticalDisclosureService implements PoliticalDisclosureServiceInterface
{
    const CANDIDATE_PAC_BALLOT_ISSUE = 'candidate_pac_ballot_issue';
    const ELECTION_DATE = 'election_date';
    const ELECTION_STATE = 'election_state';
    const SPONSORING_COMMITTEE = 'sponsoring_committee';
    const ADDRESS_STREET_LINES = 'address_street_lines';
    const CITY = 'city';
    const ZIPCODE = 'zipcode';
    const STATE = 'state';
    const EMAIL = 'email';
    const STATUS = 'status';

    private const COUNTRY_CODE = 'US';

    public function __construct(
        private readonly OrderFactory $orderFactory,
        private readonly RegionData $regionData,
        private readonly OrderDisclosureFactory $disclosureFactory,
        private readonly OrderDisclosureRepositoryInterface $disclosureRepository,
        private readonly PoliticalDisclosureConfig $politicalDisclosureConfig,
        private readonly LoggerInterface $logger,
        private readonly PoliticalDisclosureDTOFactory $politicalDisclosureDTOFactory,
        private readonly LoggerHelper $loggerHelper
    ) {}

    /**
     * @param array $disclosureInput
     * @param string $reserveId
     * @return bool|int
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function setDisclosureDetails(array $disclosureInput, string $reserveId): bool|int
    {
        try {
            $order = $this->loadOrderByReserveId($reserveId);
            if (!$order->getId()) {
                return false;
            }

            $disclosure = $this->buildDisclosureEntity((int)$order->getEntityId(), $disclosureInput);

            $this->saveDisclosure($disclosure);

            return (int)$order->getEntityId();
        } catch (InputException $ie) {
            // Input validation problems should propagate as readable errors (GraphQL will return a 400-style error)
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Input error: ' . $ie->getMessage());
            throw $ie;
        } catch (\Exception $e) {
            $this->loggerHelper->error(
                __METHOD__ . ':' . __LINE__ . sprintf(" Failed to save disclosure for order %s: %s", $reserveId, $e->getMessage())
            );

            throw new CouldNotSaveException(
                __("Failed to save disclosure: %1", $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Get the political disclosure details for a particular order
     *
     * @param string|int $orderId
     * @return array|null
     */
    public function getDisclosureDetailsByOrderId($orderId): ?array
    {
        try {
            $disclosure = $this->disclosureRepository->getByOrderId((int)$orderId);
            if (!$disclosure) {
                return null;
            }

            return [
                'candidate_pac_ballot_issue' => $disclosure->getDescription() ?? '',
                'election_date' => $disclosure->getElectionDate() ?? '',
                'election_state' => $this->regionData->getRegionById($disclosure->getElectionStateId()) ?? '',
                'sponsoring_committee' => $disclosure->getSponsor() ?? '',
                'address_street_lines' => $this->getAddressLinesAsArray($disclosure->getAddressStreetLines()),
                'city' => $disclosure->getCity() ?? '',
                'zipcode' => $disclosure->getZipCode() ?? '',
                'state' => $this->regionData->getRegionById($disclosure->getRegionId()) ?? '',
                'email' => $disclosure->getEmail() ?? '',
                'status' => $disclosure->getDisclosureStatus() ?? 0
            ];
        } catch (\Exception $e) {
            $this->loggerHelper->error(
                __METHOD__ . ':' . __LINE__ . sprintf(" Failed to fetch disclosure for order %s: %s", $orderId, $e->getMessage())
            );
            return null;
        }
    }

    /**
     * @param $addressStreetLines
     * @return array|string[]
     */
    private function getAddressLinesAsArray($addressStreetLines): array
    {
        return $addressStreetLines ? explode("\n", $addressStreetLines) : [];
    }

    /**
     * @param string $reserveId
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrderByReserveId(string $reserveId)
    {
        return $this->orderFactory->create()->loadByIncrementId($reserveId);
    }

    /**
     * @param string|null $regionCode
     * @return bool
     */
    private function isStateEnabled(?string $regionCode): bool
    {
        if (!$regionCode) {
            return false;
        }
        return in_array(
            strtoupper($regionCode),
            $this->politicalDisclosureConfig->getEnabledStates(),
            true
        );
    }

    /**
     * Build disclosure entity from input. Handles nulls, clears, and validation.
     *
     * @param int $orderId
     * @param array|null $disclosureInput
     * @return \Fedex\PoliticalDisclosure\Model\OrderDisclosure
     * @throws InputException
     */
    private function buildDisclosureEntity(int $orderId, array|null $disclosureInput)
    {
        if (!is_array($disclosureInput)) {
            $disclosureInput = [];
        }

        $disclosure = $this->disclosureRepository->getByOrderId($orderId);

        $requiredFields = [
            self::CANDIDATE_PAC_BALLOT_ISSUE => PoliticalDisclosureDTO::CANDIDATE_PAC_BALLOT_ISSUE,
            self::ELECTION_DATE              => PoliticalDisclosureDTO::ELECTION_DATE,
            self::ADDRESS_STREET_LINES       => PoliticalDisclosureDTO::ADDRESS_STREET_LINES,
            self::CITY                       => PoliticalDisclosureDTO::CITY,
            self::ZIPCODE                    => PoliticalDisclosureDTO::ZIPCODE,
            self::STATE                      => PoliticalDisclosureDTO::REGION_ID,
            self::EMAIL                      => PoliticalDisclosureDTO::EMAIL,
        ];

        // Ensure entity exists (new create) if not found
        if (!$disclosure) {
            $disclosure = $this->disclosureFactory->create();
            $disclosure->setData('order_id', $orderId);
        }

        // Validate required fields according to final rules
        $disclosureInput = $this->completeRequiredFieldsInTheDisclosure($requiredFields, $disclosureInput, $disclosure);

        // Validate election & address state codes and get region IDs (throws InputException on invalid)
        $stateIds = $this->validationsForElectionStateAndAddressState($disclosureInput, $disclosure);

        $electionRegionId = $stateIds['election_state_id'] ?? null;
        $regionId = $stateIds['region_id'] ?? null;

        // Determine current status (from input if provided, otherwise existing entity)
        $status = array_key_exists(self::STATUS, $disclosureInput)
            ? (int)$disclosureInput[self::STATUS]
            : (int)$disclosure->getDisclosureStatus();

        $isExistingDisclosure = (bool)$disclosure->getId();

        $getValue = function (string $key, $default = null) use ($disclosureInput, $disclosure, $status, $isExistingDisclosure) {
            if (array_key_exists($key, $disclosureInput)) {
                $supplied = $disclosureInput[$key];
                if ($supplied !== null) {
                    return $supplied;
                }

                if (($status === 0 && $isExistingDisclosure) || ($status >= 2)) {
                    return $disclosure->getData($key) ?? $default;
                }
                return null;
            }
            if ($status === 0) {
                if ($isExistingDisclosure) {
                    return $disclosure->getData($key);
                }
                return $default;
            }

            if ($status === 1) {
                return $default;
            }
            return $disclosure->getData($key) ?? $default;
        };

        $candidateValue = $getValue(self::CANDIDATE_PAC_BALLOT_ISSUE, '');
        $addressLines = $getValue(self::ADDRESS_STREET_LINES, []);
        $addressJoined = is_array($addressLines) ? implode("\n", $addressLines) : $addressLines;
        $sponsorValue = $getValue(self::SPONSORING_COMMITTEE, null);
        $emailValue = $getValue(self::EMAIL, '');
        $cityValue = $getValue(self::CITY, '');
        $zipValue = $getValue(self::ZIPCODE, '');
        $electionDate = $getValue(self::ELECTION_DATE, '');

        $dto = $this->politicalDisclosureDTOFactory->create([
            'description' => $candidateValue,
            'candidatePacBallotIssue' => $candidateValue,
            'electionDate' => $electionDate,
            'electionStateId' => $this->resolveFieldValue(
                self::ELECTION_STATE,
                $electionRegionId,
                $disclosure->getElectionStateId(),
                $getValue
            ),
            'sponsor' => $sponsorValue,
            'addressStreetLines' => $addressJoined,
            'city' => $cityValue,
            'zipcode' => $zipValue,
            'regionId' => $this->resolveFieldValue(
                self::STATE,
                $regionId,
                $disclosure->getRegionId(),
                $getValue
            ),
            'email' => $emailValue,
            'disclosureStatus' => $status,
        ]);

        $disclosure->addData($dto->toArray());

        return $disclosure;
    }

    /**
     * @param string $ruleKey
     * @param $overrideValue
     * @param $existingValue
     * @param callable $getValue
     * @return string|null
     */
    private function resolveFieldValue(
        string $ruleKey,
               $overrideValue,
               $existingValue,
        callable $getValue
    ): ?string {
        $selected = $getValue($ruleKey, $existingValue);
        if ($selected === null) {
            return $existingValue !== null ? (string)$existingValue : null;
        }
        return (string)($overrideValue ?? $selected);
    }


    /**
     * @param $disclosure
     * @return void
     * @throws CouldNotSaveException
     */
    private function saveDisclosure($disclosure): void
    {
        $this->disclosureRepository->save($disclosure);
    }

    /**
     * @param $requiredFields
     * @param $disclosureInput
     * @param $disclosure
     * @return array|mixed
     * @throws InputException
     */
    private function completeRequiredFieldsInTheDisclosure($requiredFields, $disclosureInput, $disclosure)
    {
        if (!is_array($disclosureInput)) {
            $disclosureInput = [];
        }

        $status = $this->resolveStatus($disclosureInput, $disclosure);
        $isExisting = $this->isExistingDisclosure($disclosure);

        foreach ($requiredFields as $key => $dtoField) {
            $value = $this->getIncomingValue($disclosureInput, $key);

            // First-time submission, status=0 => MUST have values
            if ($this->isFirstTimeSubmission($status, $isExisting) &&
                $this->isMissingOrEmpty($disclosureInput, $key, $value)) {

                throw new InputException(
                    __("The field '%1' is required for first-time submission when status = 0.", $key)
                );
            }

            // Subsequent submission with status 0 -> do nothing
            if ($this->isSubsequentDraft($status, $isExisting)) {
                continue;
            }

            // Status = 1 -> required ALWAYS
            if ($this->isFullySubmitted($status) &&
                $this->isMissingOrEmpty($disclosureInput, $key, $value)) {

                throw new InputException(
                    __("The field '%1' is required when status = 1.", $key)
                );
            }
        }

        return $disclosureInput;
    }

    /**
     * @param array $input
     * @param $disclosure
     * @return int
     */
    private function resolveStatus(array $input, $disclosure): int
    {
        return array_key_exists(self::STATUS, $input)
            ? (int)$input[self::STATUS]
            : (int)$disclosure->getDisclosureStatus();
    }

    /**
     * @param $disclosure
     * @return bool
     */
    private function isExistingDisclosure($disclosure): bool
    {
        return (bool)$disclosure->getId();
    }

    /**
     * @param array $input
     * @param string $key
     * @return mixed|null
     */
    private function getIncomingValue(array $input, string $key)
    {
        return $input[$key] ?? null;
    }

    /**
     * @param int $status
     * @param bool $isExisting
     * @return bool
     */
    private function isFirstTimeSubmission(int $status, bool $isExisting): bool
    {
        return $status === 0 && !$isExisting;
    }

    /**
     * @param int $status
     * @param bool $isExisting
     * @return bool
     */
    private function isSubsequentDraft(int $status, bool $isExisting): bool
    {
        return $status === 0 && $isExisting;
    }

    /**
     * @param int $status
     * @return bool
     */
    private function isFullySubmitted(int $status): bool
    {
        return $status === 1;
    }

    /**
     * @param array $input
     * @param string $key
     * @param $value
     * @return bool
     */
    private function isMissingOrEmpty(array $input, string $key, $value): bool
    {
        $notPresent = !array_key_exists($key, $input);

        $empty = $value === null ||
            (is_string($value) && trim((string)$value) === '') ||
            (is_array($value) && empty($value));

        return $notPresent || $empty;
    }

    /**
     * @param array $input
     * @param string $key
     * @return mixed
     */
    private function getValueFromInput(array $input, string $key): mixed
    {
        return array_key_exists($key, $input) ? $input[$key] : null;
    }

    /**
     * @param array $disclosureInput
     * @param \Fedex\PoliticalDisclosure\Model\OrderDisclosure $disclosure
     * @return array
     * @throws InputException
     */
    private function validationsForElectionStateAndAddressState($disclosureInput, $disclosure)
    {
        if (!is_array($disclosureInput)) {
            $disclosureInput = [];
        }

        $electionCode = $this->getValueFromInput($disclosureInput, self::ELECTION_STATE);
        $addressStateCode = $this->getValueFromInput($disclosureInput, self::STATE);
        $electionRegionId = null;
        $regionId = null;

        if ($electionCode) {
            if (!$this->isStateEnabled($electionCode)) {
                throw new InputException(__("Election state '%1' is not enabled for this disclosure.", $electionCode));
            }
            $electionRegion = $this->regionData->getRegionByCode($electionCode);
            if (!$electionRegion || !method_exists($electionRegion, 'getId')) {
                throw new InputException(__("Invalid election state code '%1'.", $electionCode));
            }
            $electionRegionId = $electionRegion->getId();
        }

        if ($addressStateCode) {
            $region = $this->regionData->getRegionByCode($addressStateCode);
            if (!$region || !method_exists($region, 'getId')) {
                throw new InputException(__("Invalid or missing state code '%1'.", $addressStateCode));
            }
            $regionId = $region->getId();
        }

        return [
            "election_state_id" => $electionRegionId,
            "region_id" => $regionId
        ];
    }
}

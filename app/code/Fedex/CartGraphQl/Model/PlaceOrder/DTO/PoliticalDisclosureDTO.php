<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder\DTO;

readonly class PoliticalDisclosureDTO
{
    const CANDIDATE_PAC_BALLOT_ISSUE = 'description';
    const ELECTION_DATE = 'election_date';
    const ELECTION_STATE_ID = 'election_state_id';
    const SPONSOR = 'sponsor';
    const ADDRESS_STREET_LINES = 'address_street_lines';
    const CITY = 'city';
    const ZIPCODE = 'zip_code';
    const REGION_ID = 'region_id';
    const EMAIL = 'email';
    const DISCLOSURE_STATUS = 'disclosure_status';

    /**
     * @param string|null $candidatePacBallotIssue
     * @param string|null $electionDate
     * @param string|null $electionStateId
     * @param string|null $sponsor
     * @param string|null $addressStreetLines
     * @param string|null $city
     * @param string|null $zipcode
     * @param string|null $regionId
     * @param string|null $email
     * @param int|null    $disclosureStatus
     */
    public function __construct(
        public ?string $candidatePacBallotIssue,
        public ?string $electionDate,
        public ?string $electionStateId,
        public ?string $sponsor,
        public ?string $addressStreetLines,
        public ?string $city,
        public ?string $zipcode,
        public ?string $regionId,
        public ?string $email,
        public ?int    $disclosureStatus
    ) {}

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::CANDIDATE_PAC_BALLOT_ISSUE => $this->candidatePacBallotIssue,
            self::ELECTION_DATE             => $this->electionDate,
            self::ELECTION_STATE_ID         => $this->electionStateId,
            self::SPONSOR                   => $this->sponsor,
            self::ADDRESS_STREET_LINES      => $this->addressStreetLines,
            self::CITY                      => $this->city,
            self::ZIPCODE                   => $this->zipcode,
            self::REGION_ID                 => $this->regionId,
            self::EMAIL                     => $this->email,
            self::DISCLOSURE_STATUS         => $this->disclosureStatus,
        ];
    }
}

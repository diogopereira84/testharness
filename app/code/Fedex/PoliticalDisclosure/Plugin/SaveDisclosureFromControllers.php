<?php
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Plugin;

use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\CartFactory;
use Psr\Log\LoggerInterface;

class SaveDisclosureFromControllers
{
    private const DISCLOSURE_KEYS = ['political_campaign_disclosure'];

    public function __construct(
        private readonly RequestInterface                   $request,
        private readonly CartFactory                        $cartFactory,
        private readonly OrderDisclosureRepositoryInterface $repository,
        private readonly OrderDisclosureFactory             $factory,
        private readonly PoliticalDisclosureConfig          $pdConfig,
        private readonly LoggerInterface                    $logger
    ) {}

    /**
     * @param object $subject
     * @return void
     */
    public function beforeExecute(object $subject): void
    {
        try {

            if (empty($this->pdConfig->getEnabledStates())) {
                return;
            }

            $payload = $this->request->getPost('data');
            if (empty($payload)) {
                return;
            }

            $data = json_decode((string)$payload, true);
            if (!is_array($data)) {
                return;
            }

            $disclosureData = $this->extractDisclosureData($data);
            if ($disclosureData === null) {
                return;
            }

            $quoteId = $this->resolveQuoteId($data);
            if ($quoteId === null) {
                $this->logger->warning('[PD] Quote ID could not be resolved — disclosure not saved.');
                return;
            }

            $model = $this->repository->getByQuoteId($quoteId) ?? $this->factory->create();

            $model->addData([
                'quote_id'             => $quoteId,
                'disclosure_status'    => (int)($disclosureData['disclosureStatus'] ?? 1),
                'description'          => $disclosureData['candidate_pac_ballot_issue']
                    ?? $disclosureData['description'] ?? null,
                'election_date'        => $disclosureData['election_date']
                    ?? $disclosureData['electionDate'] ?? null,
                'election_state_id'    => $disclosureData['election_state_id']
                    ?? $disclosureData['electionStateId'] ?? null,
                'sponsor'              => $disclosureData['sponsoring_committee']
                    ?? $disclosureData['sponsor'] ?? null,
                'address_street_lines' => $disclosureData['address_street_lines']
                    ?? $disclosureData['addressStreetLines'] ?? null,
                'city'                 => $disclosureData['city'] ?? null,
                'region_id'            => $disclosureData['region_id']
                    ?? $disclosureData['regionId'] ?? null,
                'zip_code'             => $disclosureData['zip_code']
                    ?? $disclosureData['zipCode'] ?? null,
                'email'                => $disclosureData['email'] ?? null,
            ]);

            $this->repository->save($model);
        } catch (\Throwable $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                '[PD] Error saving disclosure: ' . $e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array|null
     */
    private function extractDisclosureData(array $data): ?array
    {
        foreach (self::DISCLOSURE_KEYS as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @return int|null
     */
    private function resolveQuoteId(array $data): ?int
    {
        try {
            if (!empty($data['quote_id'])) {
                return (int)$data['quote_id'];
            }

            if (!empty($data['quoteCreation']['quoteId'])) {
                return (int)$data['quoteCreation']['quoteId'];
            }

            $quote = $this->cartFactory->create()->getQuote();
            if ($quote && $quote->getId()) {
                return (int)$quote->getId();
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

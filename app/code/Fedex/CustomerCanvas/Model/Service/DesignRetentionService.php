<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use DateTimeImmutable;
use DateInterval;
use DateTimeInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class DesignRetentionService
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly LoggerInterface $logger
    ) {}

    public function isExpiredDesign(QuoteItem $item): bool
    {
        $retentionPeriodMonths = $this->configProvider->getRetentionPeriod();

        try {
            foreach ($item->getOptions() as $option) {
                $optionsData = $option->getData('value');
                if (!$optionsData) {
                    continue;
                }

                $data = json_decode($optionsData, true);
                $designCreationDate = $data['productConfig']['vendorOptions']['designCreationTime'] ?? null;

                return $this->hasExpired($designCreationDate, $retentionPeriodMonths);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .'Error checking design expiration for quote item',
                ['exception' => $e, 'item_id' => $item->getId()]
            );
        }

        return false;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isExpiredCatalogProductDesign(Product $product): bool
    {
        $retentionPeriodMonths = $this->configProvider->getRetentionPeriod();

        try {
            $externalProd = $product->getData('external_prod');
            if (!$externalProd) {
                return false;
            }

            $externalProdData = json_decode($externalProd, true);
            $designCreationDate = $externalProdData['vendorOptions'][0]['designCreationTime'] ?? null;

            return $this->hasExpired($designCreationDate, $retentionPeriodMonths);
        } catch (\Throwable $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .'Error checking design expiration for catalog product',
                ['exception' => $e, 'product_id' => $product->getId()]
            );
        }

        return false;
    }

    /**
     * @param string|DateTimeInterface|null $creationDate
     * @param  $months
     * @return bool
     * @throws \Exception
     */
    private function hasExpired(string|DateTimeInterface|null $creationDate,$months): bool
    {
        if ($creationDate === null) {
            return false;
        }
        if($months == 0){
            return true;
        }

        $designDate = $this->toDateTimeImmutable($creationDate);
        $expirationDate = $designDate->add(new DateInterval("P{$months}M"));

        return (new DateTimeImmutable()) > $expirationDate;
    }

    /**
     * @param string|DateTimeInterface $date
     * @return DateTimeImmutable
     * @throws \Exception
     */
    private function toDateTimeImmutable(string|DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($date)
            : new DateTimeImmutable($date);
    }
}

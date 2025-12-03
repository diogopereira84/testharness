<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use Fedex\FXOPricing\Api\Data\AlertInterfaceFactory;
use Fedex\FXOPricing\Api\Data\AlertCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\RateAlertBuilderInterface;

class RateAlertBuilder implements RateAlertBuilderInterface
{
    /**
     * Constructor of RateAlertBuilder
     *
     * @param AlertInterfaceFactory $alertFactory
     * @param AlertCollectionInterfaceFactory $alertCollectionFactory
     */
    public function __construct(
        private readonly AlertInterfaceFactory $alertFactory,
        private readonly AlertCollectionInterfaceFactory $alertCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function build(array $alerts): AlertCollectionInterface
    {
        $collection = $this->alertCollectionFactory->create();

        foreach ($alerts as $alert) {
            $alertModel = $this->alertFactory->create();
            $alertModel->setCode($alert['code'] ?? '');
            $alertModel->setMessage($alert['message'] ?? '');
            $alertModel->setAlertType($alert['alertType'] ?? '');
            $collection->addItem($alertModel);
        }

        return $collection;
    }
}

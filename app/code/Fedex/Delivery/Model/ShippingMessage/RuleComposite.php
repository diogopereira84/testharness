<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage;

use Fedex\Delivery\Model\ShippingMessage\Rule\RuleInterface;

class RuleComposite implements RuleInterface, RuleCompositeInterface
{
    /**
     * @param array<RuleInterface> $rules
     */
    public function __construct(
        private array $rules = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isValid(TransportInterface $transport): bool
    {
        foreach ($this->rules as $rule) {
            if (!$rule->isValid($transport)) {
                return false;
            }
        }

        return true;
    }
}

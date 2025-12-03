<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\Delivery\Helper\Data;
use Fedex\Punchout\ViewModel\TazToken;

class TokenProvider
{
    public function __construct(
        private readonly TazToken $tazToken,
        private readonly Data $deliveryHelper
    ) {
    }

    /**
     * @param bool $publicFlag
     * @return string|null
     */
    public function getTazToken(bool $publicFlag = false): ?string
    {
        return $this->tazToken->getTazToken($publicFlag);
    }

    /**
     * @return string|null
     */
    public function getCompanySite(): ?string
    {
        return $this->deliveryHelper->getCompanySite();
    }
}

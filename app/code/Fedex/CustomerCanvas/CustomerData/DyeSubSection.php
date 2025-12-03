<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\CustomerData;

use Fedex\CustomerCanvas\ViewModel\CanvasParams;
use Magento\Customer\CustomerData\SectionSourceInterface;

class DyeSubSection implements SectionSourceInterface
{
    public function __construct(
        protected CanvasParams $canvasParamsViewModel,
    )
    {
    }

    public function getSectionData(): array
    {
        if ($this->canvasParamsViewModel->isDyeSubEnabled()) {
            $paramsDyeSub = $this->canvasParamsViewModel->getRequiredCanvasParams();
        }

        return $paramsDyeSub ?? [];
    }
}

<?php
/**
 *  Get Taz Token
 */
namespace Fedex\Punchout\ViewModel;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;

class TazToken implements ArgumentInterface
{

    /**
     * @param PunchoutHelper $punchoutHelper
     */
    public function __construct(
        private PunchoutHelper $punchoutHelper
    )
    {
    }

    /**
     * Get Taz Token
     *
     * @return string|null
     */
    public function getTazToken($publicFlag = false)
    {
        return $this->punchoutHelper->getTazToken($publicFlag);
    }

}


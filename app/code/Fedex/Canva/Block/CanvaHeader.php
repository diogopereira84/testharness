<?php
/**
 * @category  Fedex
 * @package   Fedex_Canva
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class CanvaHeader extends Template
{
    /** @var string  */
    private const TUTORIAL_URL_PATH = 'fedex/canva_design/tutorial_custom_url';

    /** @var string  */
    private const OPEN_NEW_TAB = 'fedex/canva_design/tutorial_new_tab';

    /**
     * @return string
     */
    public function getTutorialCustomUrl(): string
    {
        return $this->_scopeConfig->getValue(self::TUTORIAL_URL_PATH, ScopeInterface::SCOPE_STORE) ?? '';
    }

    /**
     * @return bool
     */
    public function isUrlOpenNewTabEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(self::OPEN_NEW_TAB, ScopeInterface::SCOPE_STORE) ?? false;
    }
}

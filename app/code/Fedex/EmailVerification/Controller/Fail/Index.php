<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Controller\Fail;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     *
     * @param Context  $context
     * @param PageFactory $pageFactory
     */

    public function __construct(
        Context $context,
        protected PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @codeCoverageIgnore
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        return $this->pageFactory->create();
    }
}

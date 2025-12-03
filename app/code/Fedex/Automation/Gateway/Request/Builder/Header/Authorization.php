<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Request\Builder\Header;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Magento\Framework\App\RequestInterface;

class Authorization implements BuilderInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        protected RequestInterface $request
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        if (empty($this->request->getParam('token'))) {
            return $buildSubject;
        }

        return array_replace_recursive($buildSubject, [
            'headers' => [
                'Authorization' => 'Basic ' . $this->request->getParam('token')
            ]
        ]);
    }
}

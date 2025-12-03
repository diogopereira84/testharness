<?php
/**
 * @category  Fedex
 * @package   Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Request;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Fedex\CoreApi\Gateway\Request\BuilderCompositeInterface;

class Builder implements BuilderInterface, BuilderCompositeInterface
{
    /**
     * @var array|BuilderInterface[]
     */
    private array $builders = [];

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        $result = [];
        foreach ($this->builders as $builder) {
            $result = array_replace_recursive($result, $builder->build($buildSubject));
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function add(BuilderInterface $builder): Builder
    {
        $this->builders[] = $builder;
        return $this;
    }
}

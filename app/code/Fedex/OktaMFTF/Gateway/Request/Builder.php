<?php
/**
 * @category    Request
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Request
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Request;

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

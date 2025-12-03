<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Source;

use \Psr\Log\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Import source factory
 */
class Factory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ObjectManagerInterface $objectManager,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Create function of factory
     *
     * @param string $className
     * @return \Magento\ImportExport\Model\Source\Import\AbstractBehavior
     * @throws \InvalidArgumentException
     */
    public function create($className)
    {
        if (!$className) {
            $this->logger->error(__METHOD__.':'.__LINE__.' INCORRECT CLASS NAME');
            throw new \InvalidArgumentException('Incorrect class name');
        }

        return $this->objectManager->create($className);
    }
}

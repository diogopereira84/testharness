<?php

namespace Fedex\Company\Plugin\Model\Company;

use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Data\Tree\Node;

class StructurePlugin
{
    /**
     * @param ConfigInterface $configInterface
     */
    public function __construct(
        protected ConfigInterface $configInterface
    )
    {
    }

    public function afterGetTreeByCustomerId(Structure $subject, Node|null $result)//NOSONAR
    {
        if ($result && !$this->configInterface->isToggleEnableForD190859IssueFix()) {
            /** @var Node $childNode */
            foreach ($result->getChildren() as &$childNode) {
                $entityId = (int)$childNode->getId();
                $childNode->setStructureId($entityId);
            }
        }
        return $result;
    }

    /**
     * @param Structure $subject
     * @param $result
     * @param $id
     * @return mixed
     */
    public function afterGetTreeById(Structure $subject, $result, $id)
    {
        if ($this->configInterface->isToggleEnableForD190859IssueFix() && $result) {
            foreach ($result->getChildren() as &$childNode) {
                $entityId = (int)$childNode->getId();
                $childNode->setStructureId($entityId);
            }
        }
        return $result;
    }

}


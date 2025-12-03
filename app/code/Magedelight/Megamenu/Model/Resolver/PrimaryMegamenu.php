<?php

namespace Magedelight\Megamenu\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

class PrimaryMegamenu implements ResolverInterface
{
    /**
     * @var \Magedelight\Megamenu\Api\MegamenuManagementInterface
     */
    private $megamenuManagement;

    /**
     * @param DataProvider\TestMenuData $testMenuDataRepository
     */
    public function __construct(
        \Magedelight\Megamenu\Api\MegamenuManagementInterface $megamenuManagement
    ) {
        $this->megamenuManagement = $megamenuManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            /** @var ContextInterface $context */
            $menuData = $this->megamenuManagement->getMenuData($context->getUserId());
            return [
                'menu' => $menuData->getMenu()
            ];
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }

    }
}
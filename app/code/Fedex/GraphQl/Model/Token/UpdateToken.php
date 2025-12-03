<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Token;

use Fedex\GraphQl\Helper\Data;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\ResourceModel\Oauth\Token as ResourceToken;

/**
 * Update token
 **/
class UpdateToken
{
    /**
     * UpdateToken constructor.
     * @param ResourceToken $resourceToken
     * @param Data $data
     */
    public function __construct(
        protected ResourceToken $resourceToken,
        protected Data $data
    )
    {
    }

    /**
     * Update Fuse integration token
     */
    public function execute(Token $token): void
    {
        $token->setIsFuse(true);
        $token->setExpiresAt($this->data->generateAccessTokenExpirationDate());
        $this->resourceToken->save($token);
    }
}

<?php

namespace Fedex\InStoreConfigurations\Model;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\App\Request\Http;

class Organization
{
    /**
     * This value was hardcoded in the original code.
     * It was moved here because FUSE will need to send different values to it.
     * For requests that are not fuse or graphQL, this hardcoded value, that was always here, will continue to be sent.
     */
    private const DEFAULT_ORGANIZATION = 'FXO';

    /**
     * @param Http $request
     * @param RequestQueryValidator $validator
     */
    public function __construct(
        private readonly Http                             $request,
        private readonly RequestQueryValidator            $validator
    ) {
    }

    /**
     * Check if organization is present in the request and if it's a GraphQL request.
     * If true, return the organization, otherwise return "FXO".
     *
     * @return string
     */
    public function getOrganization(string $organization)
    {
        if ($this->validator->isGraphQl()
        ) {
            $contactInformation = $this->request->getParam('contactInformation') ?? [];
            return $contactInformation['organization'] ?? $organization;
        }

        return self::DEFAULT_ORGANIZATION;
    }
}

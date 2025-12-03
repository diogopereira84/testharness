<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class PostbackValidator implements PostbackValidatorInterface
{

    /**
     * PostbackValidator constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param RequestInterface $request
     * @return bool
     * @throws LocalizedException
     */
    public function validate(RequestInterface $request): bool
    {
        if ($request->getParam('state') !== PostbackValidatorInterface::REQUEST_KEY_STATE) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Error found during user authentication.');
            throw new LocalizedException(
                __('There has been an error during your authentication.')
            );
        }

        if (!$request->getParam(PostbackValidatorInterface::REQUEST_KEY_CODE)) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Error found during user authentication.');
            throw new LocalizedException(
                __('There has been an error during your authentication.')
            );
        }

        return true;
    }
}

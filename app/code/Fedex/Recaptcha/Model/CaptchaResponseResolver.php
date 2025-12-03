<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Magento\Framework\App\PlainTextRequestInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;

/**
 * @inheritdoc
 */
class CaptchaResponseResolver implements CaptchaResponseResolverInterface
{
    /**
     * {@inheritdoc}
     *
     * @param RequestInterface|PlainTextRequestInterface $request
     * @return string
     * @throws InputException
     */
    public function resolve(RequestInterface $request): string
    {
        $content = $request->getContent();
        if (empty($content)) {
            throw new InputException(__('Can not resolve reCAPTCHA response.'));
        }

        try {
            $jsonParams = $request->getParams();
            if(empty($jsonParams[self::PARAM_RECAPTCHA]) && isset($jsonParams['data'])) {
                $jsonParams = json_decode($jsonParams['data'], true);
            }
        } catch (\InvalidArgumentException $e) {
            throw new InputException(__('Can not resolve reCAPTCHA response.'), $e);
        }

        if (empty($jsonParams[self::PARAM_RECAPTCHA])) {
            throw new InputException(__('Can not resolve reCAPTCHA response.'));
        }
        return $jsonParams[self::PARAM_RECAPTCHA];
    }
}

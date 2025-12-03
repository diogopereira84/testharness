<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Exception;

use Magento\Framework\Exception\RuntimeException;

/**
 * Exception thrown when an infinite recursion is found
 */
class RecursionException extends RuntimeException
{
}

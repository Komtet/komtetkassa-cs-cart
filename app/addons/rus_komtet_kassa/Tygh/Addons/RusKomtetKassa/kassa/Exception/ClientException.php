<?php

/**
 * This file is part of the komtet/kassa-sdk library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Komtet\KassaSdk\Exception;

include_once __DIR__.'/SdkException.php';

class ClientException extends \RuntimeException implements SdkException
{
}

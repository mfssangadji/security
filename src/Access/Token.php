<?php
/**
 * This file is part of the O2System PHP Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */
// ------------------------------------------------------------------------

namespace O2System\Security\Access;

// ------------------------------------------------------------------------

use O2System\Psr\Cache\CacheItemPoolInterface;

/**
 * Class Token
 *
 * @package O2System\Security\Access
 */
class Token
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheHandler;
}
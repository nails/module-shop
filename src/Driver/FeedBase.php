<?php

/**
 * This interface is implemented by all Shop shipping drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Driver
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Driver;

use \Nails\Common\Driver\Base;
use Nails\Shop\Exception\FeedDriverException;

class FeedBase extends Base
{
    /**
     * Generate the feed data
     * @param  object $oHeader File handle to write headers to
     * @param  string $oData   File handle to write data to
     * @return boolean
     */
    public function generate($oHeader, $oData)
    {
        throw new FeedDriverException('Driver must define generate()', 0);
    }
}

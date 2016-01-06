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

use Nails\Shop\Exception\FeedDriverException;

class FeedBase
{
    protected $sDriverSlug;

    // --------------------------------------------------------------------------

    /**
     * Accepts an array of config values from the main driver model
     * @param array $aConfig The configs to set
     * @return array
     */
    public function setConfig($aConfig)
    {
        $this->sDriverSlug = $aConfig['driver_slug'];
    }

    // --------------------------------------------------------------------------

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

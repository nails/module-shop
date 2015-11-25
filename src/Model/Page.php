<?php

/**
 * This model manages Shop Pages
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

class Page
{
    protected $aPages;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        //  Define the pages which are available here
        $this->aPages = array(
            'contact' => 'Contact Us',
            'terms' => 'Terms &amp; Conditions',
            'delivery' => 'Delivery &amp; Returns'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all available pages
     * @return array
     */
    public function getAll()
    {
        return $this->aPages;
    }
}
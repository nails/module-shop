<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_skin_checkout_model.php
 *
 * Description:		This model finds and loads shop "Front of house" skins
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_skin_checkout_model extends NAILS_Model
{
	protected $_available;
	protected $_skins;
	protected $_skin_locations;


	// --------------------------------------------------------------------------


	/**
	 * Construct the model.
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->_available = NULL;

		/**
		 * Skin locations
		 * The model will search these directories for skins; to add more directories extend this
		 * This must be an array with 2 indexes:
		 * `path`	=> The absolute path to the directory containing the skins (required)
		 * `url`	=> The URL to access the skin (required)
		 * `regex`	=> If the directory doesn't only contain skin then specify a regex to filter by
		 */

		if ( empty( $this->_skin_locations ) ) :

			$this->_skin_locations = array();

		endif;

		//	'Official' skins
		$this->_skin_locations[]	= array(
										'path'	=> NAILS_PATH,
										'url'	=> NAILS_URL,
										'regex'	=> '/^shop-skin-checkout-(.*)$/'
									);

		//	App Skins
		$this->_skin_locations[]	= array(
										'path' => FCPATH . APPPATH . 'modules/shop/skins/checkout',
										'url' => site_url( APPPATH . 'modules/shop/skins/checkout', page_is_secure() )
									);
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all available shipping drivers
	 * @param  boolean $refresh Fetch from refresh - skip the cache
	 * @return array
	 */
	public function get_available( $refresh = FALSE )
	{
		if ( ! is_null( $this->_available ) && ! $refresh ) :

			return $this->_available;

		endif;

		//	Reset
		$this->_available = array();

		// --------------------------------------------------------------------------

		/**
		 * Look for skins, where a skin has the same name, the last one found is the
		 * one which is used
		 */

		$this->load->helper( 'directory' );

		//	Take a fresh copy
		$_skin_locations = $this->_skin_locations;

		//	Sanitise
		for ( $i = 0; $i < count( $_skin_locations ); $i++ ) :

			//	Ensure path is present and has a trailing slash
			if ( isset( $_skin_locations[$i]['path'] ) ) :

				$_skin_locations[$i]['path'] = substr( $_skin_locations[$i]['path'], -1, 1 ) == '/' ? $_skin_locations[$i]['path'] : $_skin_locations[$i]['path'] . '/';

			else :

				unset( $_skin_locations[$i] );

			endif;

			//	Ensure URL is present and has a trailing slash
			if ( isset( $_skin_locations[$i]['url'] ) ) :

				$_skin_locations[$i]['url'] = substr( $_skin_locations[$i]['url'], -1, 1 ) == '/' ? $_skin_locations[$i]['url'] : $_skin_locations[$i]['url'] . '/';

			else :

				unset( $_skin_locations[$i] );

			endif;

		endfor;

		//	Reset array keys, possible that some may have been removed
		$_skin_locations = array_values( $_skin_locations );

		foreach ( $_skin_locations AS $skin_location ) :

			$_path	= $skin_location['path'];
			$_skins	= is_dir($_path) ? directory_map($_path, 1) : array();

			if ( is_array( $_skins ) ) :

				foreach ( $_skins AS $skin ) :

					//	do we need to filter out non skins?
					if ( ! empty( $skin_location['regex'] ) ) :

						if ( ! preg_match( $skin_location['regex'], $skin ) ) :

							log_message( 'debug', '"' . $skin . '" is not a shop skin.' );
							continue;

						endif;

					endif;

					// --------------------------------------------------------------------------

					//	Exists?
					if ( file_exists( $_path . $skin . '/config.json' ) ) :

						$_config = @json_decode( file_get_contents( $_path . $skin . '/config.json' ) );

					else :

						log_message( 'error', 'Could not find configuration file for skin "' . $_path . $skin. '".' );
						continue;

					endif;

					//	Valid?
					if ( empty( $_config ) ) :

						log_message( 'error', 'Configuration file for skin "' . $_path . $skin. '" contains invalid JSON.' );
						continue;

					elseif ( ! is_object( $_config ) ) :

						log_message( 'error', 'Configuration file for skin "' . $_path . $skin. '" contains invalid data.' );
						continue;

					endif;

					// --------------------------------------------------------------------------

					//	All good!

					//	Set the slug
					$_config->slug = $skin;

					//	Set the path
					$_config->path = $_path . $skin . '/';

					//	Set the URL
					$_config->url = $skin_location['url'] . $skin . '/';

					$this->_available[$skin] = $_config;

				endforeach;

			endif;

		endforeach;

		$this->_available = array_values( $this->_available );

		return $this->_available;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets a single driver
	 * @param  string  $slug    The driver's slug
	 * @param  boolean $refresh Skip the cache
	 * @return stdClass
	 */
	public function get( $slug, $refresh = FALSE )
	{
		$_skins = $this->get_available( $refresh );

		foreach ( $_skins AS $skin ) :

			if ( $skin->slug == $slug ) :

				return $skin;

			endif;

		endforeach;

		$this->_set_error( '"' . $slug . '" was not found.' );
		return FALSE;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_Shop_skin_checkout_model' ) ) :

	class Shop_skin_checkout_model extends NAILS_Shop_skin_checkout_model
	{
	}

endif;

/* End of file shop_skin_checkout_model.php */
/* Location: ./modules/shop/models/shop_skin_checkout_model.php */
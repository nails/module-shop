<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_shipping_driver_model.php
 *
 * Description:		This model finds and loads shop shipping drivers
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_shipping_driver_model extends NAILS_Model
{
	protected $_available;
	protected $_shipping_driver_locations;
	protected $_driver;
	protected $_driver_configure;


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
		 * Shipping driver locations
		 * The model will search these directories for shipping drivers; to add more directories extend this
		 * This must be an array with 2 indexes:
		 * `path`	=> The absolute path to the directory containing the shipping drivers (required)
		 * `url`	=> The URL to access the shipping drivers (required)
		 * `regex`	=> If the directory doesn't only contain shipping drivers then specify a regex to filter by
		 */

		if ( empty( $this->_shipping_driver_locations ) ) :

			$this->_shipping_driver_locations = array();

		endif;

		//	'Official' Shipping Drivers
		$this->_shipping_driver_locations[]	= array(
												'path'	=> NAILS_PATH,
												'url'	=> NAILS_URL,
												'regex'	=> '/^shop-shipping-driver-(.*)$/'
											);

		//	App Shipping Drivers
		$this->_shipping_driver_locations[]	= array(
												'path' => FCPATH . APPPATH . 'modules/shop/shipping_drivers',
												'url' => site_url( APPPATH . 'modules/shop/shipping_drivers', page_is_secure() )
											);
	}


	// --------------------------------------------------------------------------

	/**
	 * Fetches all available shipping drivers
	 * @param  boolean $refresh Fetchf rom refresh - skip the cache
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

		//	Look for shipping_drivers, where a shipping_driver has the same name, the last one found is the
		//	one which is used

		$this->load->helper( 'directory' );

		//	Take a fresh copy
		$_shipping_driver_locations = $this->_shipping_driver_locations;

		//	Sanitise
		for ( $i = 0; $i < count( $_shipping_driver_locations ); $i++ ) :

			//	Ensure path is present and has a trailing slash
			if ( isset( $_shipping_driver_locations[$i]['path'] ) ) :

				$_shipping_driver_locations[$i]['path'] = substr( $_shipping_driver_locations[$i]['path'], -1, 1 ) == '/' ? $_shipping_driver_locations[$i]['path'] : $_shipping_driver_locations[$i]['path'] . '/';

			else :

				unset( $_shipping_driver_locations[$i] );

			endif;

			//	Ensure URL is present and has a trailing slash
			if ( isset( $_shipping_driver_locations[$i]['url'] ) ) :

				$_shipping_driver_locations[$i]['url'] = substr( $_shipping_driver_locations[$i]['url'], -1, 1 ) == '/' ? $_shipping_driver_locations[$i]['url'] : $_shipping_driver_locations[$i]['url'] . '/';

			else :

				unset( $_shipping_driver_locations[$i] );

			endif;

		endfor;

		//	Reset array keys, possible that some may have been removed
		$_shipping_driver_locations = array_values( $_shipping_driver_locations );

		foreach( $_shipping_driver_locations AS $shipping_driver_location ) :

			$_path	= $shipping_driver_location['path'];
			$_shipping_drivers	= directory_map( $_path, 1 );

			if ( is_array( $_shipping_drivers ) ) :

				foreach( $_shipping_drivers AS $shipping_driver ) :

					//	do we need to filter out non shipping_drivers?
					if ( ! empty( $shipping_driver_location['regex'] ) ) :

						if ( ! preg_match( $shipping_driver_location['regex'], $shipping_driver ) ) :

							log_message( 'debug', '"' . $shipping_driver . '" is not a shop shipping_driver.' );
							continue;

						endif;

					endif;

					// --------------------------------------------------------------------------

					//	Exists?
					if ( file_exists( $_path . $shipping_driver . '/config.json' ) ) :

						$_config = @json_decode( file_get_contents( $_path . $shipping_driver . '/config.json' ) );

					else :

						log_message( 'error', 'Could not find configuration file for shipping_driver "' . $_path . $shipping_driver. '".' );
						continue;

					endif;

					//	Valid?
					if ( empty( $_config ) ) :

						log_message( 'error', 'Configuration file for shipping_driver "' . $_path . $shipping_driver. '" contains invalid JSON.' );
						continue;

					elseif ( ! is_object( $_config ) ) :

						log_message( 'error', 'Configuration file for shipping_driver "' . $_path . $shipping_driver. '" contains invalid data.' );
						continue;

					endif;

					// --------------------------------------------------------------------------

					//	All good!

					//	Set the slug
					$_config->slug = $shipping_driver;

					//	Set the path
					$_config->path = $_path . $shipping_driver . '/';

					//	Set the URL
					$_config->url = $shipping_driver_location['url'] . $shipping_driver . '/';

					$this->_available[$shipping_driver] = $_config;

				endforeach;

			endif;

		endforeach;

		$this->_available = array_values( $this->_available );

		return $this->_available;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets a single driver
	 * @param  string  $slug    the driver's slug
	 * @param  boolean $refresh Skip the cache
	 * @return stdClass
	 */
	public function get( $slug, $refresh = FALSE )
	{
		$_shipping_drivers = $this->get_available( $refresh );

		foreach( $_shipping_drivers AS $shipping_driver ) :

			if ( $shipping_driver->slug == $slug ) :

				return $shipping_driver;

			endif;

		endforeach;

		$this->_set_error( '"' . $slug . '" was not found.' );
		return FALSE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the enabled driver.
	 * @return mixed stdClass on success, FALSE on failure
	 */
	public function get_enabled()
	{
		$_slug = app_setting( 'enabled_shipping_driver', 'shop' );

		if ( ! $_slug ) :

			return FALSE;

		endif;

		return $this->get( $_slug );
	}

	// --------------------------------------------------------------------------


	/**
	 * Loads a driver
	 * @param  string $slug The driver to load
	 * @return boolean
	 */
	public function load( $slug = NULL )
	{
		if (  is_null( $slug ) ) :

			$slug = app_setting( 'enabled_shipping_driver', 'shop' );

			if ( ! $slug ) :

				return FALSE;

			endif;

		endif;

		$_driver = $this->get( $slug );

		if ( ! $_driver ) :

			return FALSE;

		endif;

		$this->unload();

		require_once $_driver->path . 'driver.php';
		$_class = ucfirst( str_replace( '-', '_', $_driver->slug ) );
		$this->_driver = new $_class();

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Unloads a driver
	 * @return void
	 */
	public function unload()
	{
		unset( $this->_driver );
		$this->_driver = NULL;
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether a driver is loaded or not
	 * @return boolean
	 */
	protected function _driver_is_loaded()
	{
		return ! is_null( $this->_driver );
	}


	// --------------------------------------------------------------------------


	/**
	 * Takes a basket object and calculates the cost of shipping
	 * @param  stdClass $basket A basket object
	 * @return stdClass
	 */
	public function calculate( $basket )
	{
		$_free			= new stdClass();
		$_free->base	= (float) 0;
		$_free->user	= (float) 0;

		// --------------------------------------------------------------------------

		if ( ! $this->_driver_is_loaded() ) :

			//	No driver loaded, detect enabled driver and attempt to load
			$_enabled_driver = app_setting( 'enabled_shipping_driver', 'shop' );

			if ( empty( $_enabled_driver ) || ! $this->load( $_enabled_driver ) ) :

				//	Free shipping, I guess?
				return $_free;

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( ! is_callable( array( $this->_driver, 'calculate' ) ) ) :

			//	Driver isn't configured properly, free shipping.
			return $_free;

		endif;

		// --------------------------------------------------------------------------

		/**
		 * Have the driver calculate the cost of shipping, this should return a float
		 * which is in the base currency. It is passed an array of all shippable items
		 * (i.e., items who's type markes them as `is_physical` and is not set to
		 * `collect only`), as well as a reference to the basket, shold the driver need
		 * to know anything else about the order.
		 */

		$_shippable_items = array();

		foreach( $basket->items AS $item ) :

			if ( ! empty( $item->product->type->is_physical ) && empty( $item->variant->shipping->collection_only ) ) :

				$_shippable_items[] = $item;

			endif;

		endforeach;

		$_cost = $this->_driver->calculate( $_shippable_items, $basket );
		$_cost = is_float( $_cost ) ? $_cost : (float) 0;

		$_out		= new stdClass();
		$_out->base	= $_cost;

		//	Convert the base price to the user's currency
		$this->load->model( 'shop/shop_currency_model' );
		$_out->user	= $this->shop_currency_model->convert_base_to_user( $_cost );

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns an array of possible shipping methods which the user can select from.
	 * These might include priority shipping or recorded delivery for example.
	 * @return array
	 */
	public function options_basket()
	{
		if ( ! $this->_driver_is_loaded() ) :

			if ( ! $this->load() ) :

				return array();

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( ! is_callable( array( $this->_driver, 'options_basket' ) ) ) :

			//	Driver isn't configured properly
			return array();

		endif;

		// --------------------------------------------------------------------------

		return $this->_driver->options_basket();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns an array of additional options for variants which can be set by admin
	 * @return array
	 */
	public function options_variant()
	{
		if ( ! $this->_driver_is_loaded() ) :

			if ( ! $this->load() ) :

				return array();

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( ! is_callable( array( $this->_driver, 'options_variant' ) ) ) :

			//	Driver isn't configured properly
			return array();

		endif;

		// --------------------------------------------------------------------------

		return $this->_driver->options_variant();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns an array of additional options for products  which can be set by admin
	 * @return array
	 */
	public function options_product()
	{
		if ( ! $this->_driver_is_loaded() ) :

			if ( ! $this->load() ) :

				return array();

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( ! is_callable( array( $this->_driver, 'options_product' ) ) ) :

			//	Driver isn't configured properly
			return array();

		endif;

		// --------------------------------------------------------------------------

		return $this->_driver->options_product();
	}


	// --------------------------------------------------------------------------


	/**
	 * Handles the configuration of the driver in admin
	 * @return array
	 */
	public function configure( $slug )
	{
		//	Fetch the driver in question
		$_driver = $this->get( $slug );

		if ( ! $_driver ) :
			return 'boobs';
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Unload any previously loaded driver for configuration
		unset( $this->_driver_configure );
		$this->_driver_configure = NULL;

		// --------------------------------------------------------------------------

		//	Load the driver
		require_once $_driver->path . 'driver.php';
		$_class = ucfirst( strtolower( str_replace( '-', '_', $_driver->slug ) ) );
		$this->_driver_configure = new $_class();

		// --------------------------------------------------------------------------

		//	Call the config method
		if ( ! is_callable( array( $this->_driver_configure, 'configure' ) ) ) :

			//	Driver isn't configured properly
			return 'vagina';
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Spit back whatever the driver desires
		return $this->_driver_configure->configure();
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_SHIPPING_DRIVER_MODEL' ) ) :

	class Shop_shipping_driver_model extends NAILS_Shop_shipping_driver_model
	{
	}

endif;

/* End of file shop_shipping_driver_model.php */
/* Location: ./modules/shop/models/shop_shipping_driver_model.php */
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_currency_model.php
 *
 * Description:		This model handles everything to do with currencies
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_currency_model extends NAILS_Model
{
	protected $_oer_url;
	protected $_rates;

	// --------------------------------------------------------------------------


	/**
	 * Construct the model, define defaults and load dependencies.
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		//	Load required config file
		$this->config->load( 'shop/currency' );

		// --------------------------------------------------------------------------

		//	Defaults
		$this->_oer_url	= 'http://openexchangerates.org/api/latest.json';
		$this->_rates	= NULL;
	}


	// --------------------------------------------------------------------------


	/**
	 * Get all defined currencies.
	 * @return array
	 */
	public function get_all()
	{
		return $this->config->item( 'currency' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Get all defined currencies as a flat array; the index is the currency's code,
	 * the value is the currency's label.
	 * @return array
	 */
	public function get_all_flat()
	{
		$_out		= array();
		$_currency	= $this->get_all();

		foreach( $_currency AS $c ) :

			$_out[$c->code] = $c->label;

		endforeach;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets all currencies supported by the shop.
	 * @return array
	 */
	public function get_all_supported()
	{
		$_currencies	= $this->get_all();
		$_additional	= app_setting( 'additional_currencies', 'shop' );
		$_base			= app_setting( 'base_currency', 'shop' );
		$_supported		= array();

		if ( isset( $_currencies[$_base] ) ) :

			$_supported[] = $_currencies[$_base];

		endif;

		if ( is_array( $_additional ) ) :

			foreach( $_additional AS $additional ) :

				if ( isset( $_currencies[$additional] ) ) :

					$_supported[] = $_currencies[$additional];

				endif;

			endforeach;

		endif;

		return $_supported;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets all supported currencies as a flat array; the index is the currency's
	 * code, the value is the currency's label.
	 * @return array
	 */
	public function get_all_supported_flat()
	{
		$_out		= array();
		$_currency	= $this->get_all_supported();

		foreach( $_currency AS $c ) :

			$_out[$c->code] = $c->label;

		endforeach;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets an individual currency by it's 3 letter code.
	 * @param  string $code The code to return
	 * @return mixed        stdClass on success, FALSE on failure
	 */
	public function get_by_code( $code )
	{
		$_currency = $this->get_all();

		return ! empty( $_currency[$code] ) ? $_currency[$code] : FALSE;
	}

	// --------------------------------------------------------------------------


	/**
	 * Syncs exchange rates to the Open Exchange Rates service.
	 * @param  boolean $mute_log Whether or not to write errors to the log
	 * @return boolean
	 */
	public function sync( $mute_log = TRUE )
	{
		$_openexchangerates_app_id			= app_setting( 'openexchangerates_app_id', 'shop' );
		$_openexchangerates_etag			= app_setting( 'openexchangerates_etag', 'shop' );
		$_openexchangerates_last_modified	= app_setting( 'openexchangerates_last_modified', 'shop' );
		$_additional_currencies				= app_setting( 'additional_currencies', 'shop' );

		if ( empty( $_additional_currencies ) ) :

			$_message = 'No additional currencies are supported, aborting sync.';
			$this->_set_error( $_message );

			if ( empty( $mute_log ) ) :

				_LOG( '... ' . $_message );

			endif;

			return FALSE;

		endif;

		if ( $_openexchangerates_app_id ) :

			//	Make sure we know what the base currency is
			if ( defined( 'SHOP_BASE_CURRENCY_CODE' ) ) :

				$this->load->model( 'shop/shop_model' );

			endif;

			if ( empty( $mute_log ) ) :

				_LOG( '... Base Currency is ' . SHOP_BASE_CURRENCY_CODE );

			endif;

			/**
			 * Set up the CURL request
			 * First attempt to get the rates using the Shop's base currency
			 * (only available to paid subscribers, but probably more accurate)
			 */

			$this->load->library( 'curl/curl' );

			$_params			= array();
			$_params['app_id']	= $_openexchangerates_app_id;
			$_params['base']	= SHOP_BASE_CURRENCY_CODE;

			$this->curl->create( $this->_oer_url . '?' . http_build_query( $_params ) );
			$this->curl->option( CURLOPT_FAILONERROR, FALSE );
			$this->curl->option( CURLOPT_HEADER, TRUE );

			if ( ! empty( $_openexchangerates_etag ) && ! empty( $_openexchangerates_last_modified ) ) :

				$this->curl->http_header( 'If-None-Match', '"' . $_openexchangerates_etag . '"' );
				$this->curl->http_header( 'If-Modified-Since', $_openexchangerates_last_modified );

			endif;

			$_response = $this->curl->execute();

			/**
			 * If this failed, it's probably due to requesting a non-USD base
			 * Try again with but using USD base this time.
			 */

			if ( empty( $this->curl->info['http_code'] ) || $this->curl->info['http_code'] != 200 ) :

				//	Attempt to extract the body and see if the reason is an invalid App ID
				$_response = explode( "\r\n\r\n", $_response, 2 );
				$_response = ! empty( $_response[1] ) ? @json_decode( $_response[1] ) : NULL;

				if ( ! empty( $_response->message ) && $_response->message == 'invalid_app_id' ) :

					$_message = $_openexchangerates_app_id . ' is not a valid OER app ID.';
					$this->_set_error( $_message );

					if ( empty( $mute_log ) ) :

						_LOG( $_message );

					endif;

					return FALSE;

				endif;

				if ( empty( $mute_log ) ) :

					_LOG( '... Query using base as ' . SHOP_BASE_CURRENCY_CODE  . ' failed, trying agian using USD' );

				endif;

				$_params['base'] = 'USD';

				$this->curl->create( $this->_oer_url . '?' . http_build_query( $_params ) );
				$this->curl->option( CURLOPT_FAILONERROR, FALSE );
				$this->curl->option( CURLOPT_HEADER, TRUE );

				if ( ! empty( $_openexchangerates_etag ) && ! empty( $_openexchangerates_last_modified ) ) :

					$this->curl->http_header( 'If-None-Match', '"' . $_openexchangerates_etag . '"' );
					$this->curl->http_header( 'If-Modified-Since', $_openexchangerates_last_modified );

				endif;

				$_response = $this->curl->execute();

			elseif ( ! empty( $this->curl->info['http_code'] ) && $this->curl->info['http_code'] == 304 ) :

				//	304 Not Modified, abort sync.
				if ( empty( $mute_log ) ) :

					_LOG( '... OER reported 304 Not Modified, aborting sync' );

				endif;

				return TRUE;

			endif;

			if ( ! empty( $this->curl->info['http_code'] ) && $this->curl->info['http_code'] == 200 ) :

				/**
				 * Ok, now we know the rates we need to work out what the base_exchange rate is.
				 * If the store's base rate is the same as the API's base rate then we're golden,
				 * if it's not then we'll need to do some calculations.
				 *
				 * Attempt to extract the headers (so we can use the E-Tag) and then parse
				 * the body.
				 */

				$_response = explode( "\r\n\r\n", $_response, 2 );

				if ( empty( $_response[1] ) ) :

					$_message = 'Could not extract the body of the request.';
					$this->_set_error( $_message );

					if ( empty( $mute_log ) ) :

						_LOG( $_message );
						_LOG( print_r( $_response, TRUE ) );

					endif;

					return FALSE;

				endif;

				//	Body
				$_response[1] = ! empty( $_response[1] ) ? @json_decode( $_response[1] ) : NULL;

				if ( empty( $_response[1] ) ) :

					$_message = 'Could not parse the body of the request.';
					$this->_set_error( $_message );

					if ( empty( $mute_log ) ) :

						_LOG( $_message );
						_LOG( print_r( $_response, TRUE ) );

					endif;

					return FALSE;

				endif;

				//	Headers, look for the E-Tag and last modified
				preg_match( '/ETag: "(.*?)"/', $_response[0], $_matches );
				if ( ! empty( $_matches[1] ) ) :

					//	Save ETag to shop settings
					set_app_setting( 'openexchangerates_etag', 'shop', $_matches[1] );

				endif;

				preg_match( '/Last-Modified: (.*)/', $_response[0], $_matches );
				if ( ! empty( $_matches[1] ) ) :

					//	Save Last-Modified to shop settings
					set_app_setting( 'openexchangerates_last_modified', 'shop', $_matches[1] );

				endif;

				$_response = $_response[1];

				$_to_save = array();

				if ( SHOP_BASE_CURRENCY_CODE == $_response->base ) :

					foreach ( $_response->rates AS $to_currency => $rate ) :

						if ( array_search( $to_currency, $_additional_currencies ) !== FALSE ) :

							if ( empty( $mute_log ) ) :

								_LOG( '... ' . $to_currency . ' > ' . $rate );

							endif;

							$_to_save[] = array(
								'from'		=> $_response->base,
								'to'		=> $to_currency,
								'rate'		=> $rate,
								'modified'	=> date( 'Y-m-d H:i:s' )
							);

						endif;

					endforeach;

				else :

					if ( empty( $mute_log ) ) :

						_LOG( '... API base is ' . $_response->base . '; calculating differences...' );

					endif;

					$_base = 1;
					foreach ( $_response->rates AS $code => $rate ) :

						if ( $code == SHOP_BASE_CURRENCY_CODE ) :

							$_base = $rate;
							break;

						endif;

					endforeach;

					foreach ( $_response->rates AS $to_currency => $rate ) :

						if ( array_search( $to_currency, $_additional_currencies ) !== FALSE ) :

							//	We calculate the new exchange rate as so: $rate / $_base
							$_new_rate = $rate / $_base;
							$_to_save[] = array(
								'from'		=> SHOP_BASE_CURRENCY_CODE,
								'to'		=> $to_currency,
								'rate'		=> $_new_rate,
								'modified'	=> date( 'Y-m-d H:i:s' )
							);

							if ( empty( $mute_log ) ) :

								_LOG( '... Calculating and saving new exchange rate for ' . SHOP_BASE_CURRENCY_CODE . ' > ' . $to_currency . ' (' . $_new_rate . ')' );

							endif;

						endif;

					endforeach;


				endif;

				// --------------------------------------------------------------------------

				//	Ok, we've done all the BASE -> CURRENCY conversions, now how about we work
				//	out the reverse?
				$_to_save_reverse = array();

				//	Easy one first, base to base, base, bass, drop da bass. BASS.
				$_to_save_reverse[] = array(
					'from'		=> SHOP_BASE_CURRENCY_CODE,
					'to'		=> SHOP_BASE_CURRENCY_CODE,
					'rate'		=> 1,
					'modified'	=> date( 'Y-m-d H:i:s' )
				);

				foreach ( $_to_save AS $old ) :

					$_to_save_reverse[] = array(
					'from'		=> $old['to'],
					'to'		=> SHOP_BASE_CURRENCY_CODE,
					'rate'		=> 1 / $old['rate'],
					'modified'	=> date( 'Y-m-d H:i:s' )
				);

				endforeach;

				$_to_save = array_merge( $_to_save, $_to_save_reverse );

				// --------------------------------------------------------------------------

				if ( $this->db->truncate( NAILS_DB_PREFIX . 'shop_currency_exchange' ) ) :

					if ( ! empty( $_to_save ) ) :

						if ( $this->db->insert_batch( NAILS_DB_PREFIX . 'shop_currency_exchange', $_to_save ) ) :

							return TRUE;

						else :

							$_message = 'Failed to insert new currency data.';
							$this->_set_error( $_message );

							if ( empty( $mute_log ) ) :

								_LOG( '... ' . $_message );

							endif;

							return FALSE;

						endif;

					else :

						return TRUE;

					endif;

				else :

					$_message = 'Failed to truncate currency table.';
					$this->_set_error( $_message );

					if ( empty( $mute_log ) ) :

						_LOG( '... ' . $_message );

					endif;

					return FALSE;

				endif;

			elseif ( ! empty( $this->curl->info['http_code'] ) && $this->curl->info['http_code'] == 304 ) :

				//	304 Not Modified, abort sync.
				if ( empty( $mute_log ) ) :

					_LOG( '... OER reported 304 Not Modified, aborting sync' );

				endif;

				return TRUE;

			else :

				//	Attempt to extract the body so we can get our failure reason
				$_response = explode( "\r\n\r\n", $_response, 2 );
				$_response = ! empty( $_response[1] ) ? @json_decode( $_response[1] ) : NULL;

				$_message = 'An error occurred when querying the API.';
				$this->_set_error( $_message );

				if ( empty( $mute_log ) ) :

					_LOG( '... ' . $_message );
					_LOG( print_r( $_response, TRUE ) );

				endif;

				return FALSE;

			endif;

		else :

			$_message = '`openexchangerates_app_id` setting is not set. Sync aborted.';
			$this->_set_error( $_message );

			if ( empty( $mute_log ) ) :

				_LOG( '... ' . $_message );

			endif;

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Converts a value between currencies.
	 * @param  mixed  $value The value to convert
	 * @param  string $from  The currency to convert from
	 * @param  string $to    The currency to convert too
	 * @return mixed         Float on success, fALSE on failure
	 */
	public function convert( $value, $from, $to )
	{
		if ( is_null( $this->_rates ) ) :

			$this->_rates	= array();
			$_rates			= $this->db->get( NAILS_DB_PREFIX . 'shop_currency_exchange' )->result();

			foreach( $_rates AS $rate ) :

				$this->_rates[$rate->from . $rate->to] = $rate->rate;

			endforeach;

		endif;

		if ( isset( $this->_rates[$from . $to] ) ) :

			return $value * $this->_rates[$from . $to];

		else :

			$this->_set_error( 'No exchange rate available for those currencies; does the system need to sync?' );
			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Converts a value from the base currency to the user's currency.
	 * @param  mixed $value The value to convert
	 * @return mixed        Float on success, FALSE on failure
	 */
	public function convert_base_to_user( $value )
	{
		return $this->convert( $value, SHOP_BASE_CURRENCY_CODE, SHOP_USER_CURRENCY_CODE );
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a value using the settings for a given currency.
	 * @param  mixed   $value      The value to format, string, int or float
	 * @param  string  $code       The currency to format as
	 * @param  boolean $inc_symbol Whether or not to include the currency's symbol
	 * @return mixed               String on success, FALSE on failure
	 */
	public function format( $value, $code, $inc_symbol = TRUE )
	{
		$_currency = $this->get_by_code( $code );

		if ( ! $_currency ) :

			$this->_set_error( 'Invalid currency code.' );
			return FALSE;

		endif;

		$value = number_format( $value, $_currency->decimal_precision, $_currency->decimal_symbol, $_currency->thousands_seperator );

		if ( $inc_symbol ) :

			if ( $_currency->symbol_position == 'BEFORE' ) :

				$value = $_currency->symbol . $value;

			else :

				$value = $value . $_currency->symbol;

			endif;

		endif;

		return $value;
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a value using the settings for the base currency.
	 * @param  mixed   $value      The value to format, string, int or float
	 * @param  boolean $inc_symbol Whether or not to include the currency's symbol
	 * @return mixed               String on success, FALSE on failure
	 */
	public function format_base( $value, $inc_symbol = TRUE )
	{
		return $this->format( $value, SHOP_BASE_CURRENCY_CODE, $inc_symbol );
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a value using the settings for the user's currency.
	 * @param  mixed   $value      The value to format, string, int or float
	 * @param  boolean $inc_symbol Whether or not to include the currency's symbol
	 * @return mixed               String on success, FALSE on failure
	 */
	public function format_user( $value, $inc_symbol = TRUE )
	{
		return $this->format( $value, SHOP_USER_CURRENCY_CODE, $inc_symbol );
	}


	// --------------------------------------------------------------------------


	public function get_exchange_rate( $from, $to )
	{
		$this->db->select( 'rate' );
		$this->db->where( 'from', $from );
		$this->db->where( 'to', $to );

		$_rate = $this->db->get( NAILS_DB_PREFIX . 'shop_currency_exchange' )->row();

		if ( ! $_rate ) :

			return NULL;

		endif;

		return $_rate->rate;
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_CURRENCY_MODEL' ) ) :

	class Shop_currency_model extends NAILS_Shop_currency_model
	{
	}

endif;

/* End of file shop_currency_model.php */
/* Location: ./modules/shop/models/shop_currency_model.php */
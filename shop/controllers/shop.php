<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Shop
 *
 * Description:	This controller handles the frontpage of the shop
 *
 **/

/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

//	Include _shop.php; executes common functionality
require_once '_shop.php';

class NAILS_Shop extends NAILS_Shop_Controller
{
	protected $_product_sort;
	protected $_product_pagination;

	// --------------------------------------------------------------------------


	/**
	 * Defines the default items, then sets them if they're to be set.
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		//	Product Sorting
		//	===============

		//	Defaults
		$this->_product_sort			= new stdClass();
		$this->_product_sort->sort		= app_setting( 'default_product_sort','shop' ) ? app_setting( 'default_product_sort','shop' ) : 'recent';

		//	Actual Values
		$this->_product_sort->sort		= $this->input->get_post( 'sort' ) ? $this->input->get_post( 'sort' ) : $this->_product_sort->sort;

		//	Sanitise/translate
		switch ( $this->_product_sort->sort ) :

			case 'price_low_high' :	$this->_product_sort->sort_on = $this->shop_product_model->get_property_table_prefix() . '.XXX';		break;
			case 'price_low_high' :	$this->_product_sort->sort_on = $this->shop_product_model->get_property_table_prefix() . '.XXX';		break;
			case 'a-z' :			$this->_product_sort->sort_on = $this->shop_product_model->get_property_table_prefix() . '.label';		break;
			case 'recent' :
			default :				$this->_product_sort->sort_on =  $this->shop_product_model->get_property_table_prefix() . '.created';	break;

		endswitch;

		//	Pass to views
		$this->data['product_sort'] = $this->_product_sort;

		// --------------------------------------------------------------------------

		//	Product Pagination
		//	==================

		//	Defaults
		$this->_product_pagination				= new stdClasS();
		$this->_product_pagination->page		= 0;
		$this->_product_pagination->rsegment	= 2;
		$this->_product_pagination->total		= 0;
		$this->_product_pagination->per_page	= app_setting( 'default_product_per_page','shop' ) ? app_setting( 'default_product_per_page','shop' ) : 25;

		//	Actual Values
		$this->_product_pagination->per_page	= $this->input->get_post( 'per_page' ) ? $this->input->get_post( 'per_page' ) : $this->_product_pagination->per_page;

		//	Sanitise
		switch ( $this->_product_pagination->per_page ) :

			case '10' :		$this->_product_pagination->per_page = 10;	break;
			case '50' :		$this->_product_pagination->per_page = 50;	break;
			case '100' :	$this->_product_pagination->per_page = 100;	break;
			case '25' :
			default :		$this->_product_pagination->per_page = 25;	break;

		endswitch;

		//	Pass to views
		$this->data['product_pagination'] = $this->_product_pagination;
	}


	// --------------------------------------------------------------------------


	/**
	 * Render's the shop's front page
	 * @return void
	 */
	public function index()
	{
		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name;

		// --------------------------------------------------------------------------

		//	Sidebar Items
		//	=============

		$this->data['brands']		= $this->shop_brand_model->get_all();
		$this->data['categories']	= $this->shop_category_model->get_top_level();
		$this->data['collections']	= $this->shop_collection_model->get_all();
		$this->data['ranges']		= $this->shop_range_model->get_all();

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja.
		 */

		$this->_product_pagination->rsegment	= 2;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );

		$this->_configure_pagination( $this->shop_product_model->count_all( $_data ) );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_all( $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',							$this->data );
		$this->load->view( $this->_skin->path . 'views/front/index',	$this->data );
		$this->load->view( 'structure/footer',							$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the brand slug and loads the appropriate method
	 * @return void
	 */
	public function brand()
	{
		//	Strip out the store's URL, leave just the brand's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'brand/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_brand_single( $_slug );

		else :

			$this->_brand_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of brands
	 * @return void
	 */
	protected function _brand_index()
	{
		if ( ! app_setting( 'page_brand_listing', 'shop' ) ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Brands';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Brands
		//	======

		$this->data['brands'] = $this->shop_brand_model->get_all();

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/brand/index',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single brand
	 * @return void
	 */
	protected function _brand_single( $slug )
	{
		$this->data['brand'] = $this->shop_brand_model->get_by_slug( $slug );

		if ( ! $this->data['brand' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Brand: "' . $this->data['brand']->label . '"';

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the category's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/category).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['brand']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_brand( $this->data['brand']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'brand/' . $this->data['brand']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_brand( $this->data['brand']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/brand/single',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the category slug and loads the appropriate method
	 * @return void
	 */
	public function category()
	{
		//	Strip out the store's URL, leave just the category's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'category/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_category_single( $_slug );

		else :

			$this->_category_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of categories
	 * @return void
	 */
	protected function _category_index()
	{
		if ( ! app_setting( 'page_category_listing', 'shop' ) ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Categories';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',									$this->data );
		$this->load->view( $this->_skin->path . 'views/front/category/index',	$this->data );
		$this->load->view( 'structure/footer',									$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single category
	 * @return void
	 */
	protected function _category_single( $slug )
	{
		$this->data['category'] = $this->shop_category_model->get_by_slug( $slug );

		if ( ! $this->data['category' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Category: "' . $this->data['category']->label . '"';

		// --------------------------------------------------------------------------

		//	Category's (immediate) decendants
		//	=================================

		$this->data['category']->children = $this->shop_category_model->get_children( $this->data['category']->id, TRUE );

		// --------------------------------------------------------------------------

		//	Category's siblings
		//	=================================

		$this->data['category_siblings'] = $this->shop_category_model->get_siblings( $this->data['category']->id );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the category's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/category).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['category']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_category( $this->data['category']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'category/' . $this->data['category']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_category( $this->data['category']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',									$this->data );
		$this->load->view( $this->_skin->path . 'views/front/category/single',	$this->data );
		$this->load->view( 'structure/footer',									$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the collection slug and loads the appropriate method
	 * @return void
	 */
	public function collection()
	{
		//	Strip out the store's URL, leave just the colelction's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'colelction/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_collection_single( $_slug );

		else :

			$this->_collection_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of collections
	 * @return void
	 */
	protected function _collection_index()
	{
		if ( ! app_setting( 'page_collection_listing', 'shop' ) ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Collections';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Collections
		//	===========

		$this->data['collections'] = $this->shop_collection_model->get_all();

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',									$this->data );
		$this->load->view( $this->_skin->path . 'views/front/collection/index',	$this->data );
		$this->load->view( 'structure/footer',									$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single collection
	 * @return void
	 */
	protected function _collection_single( $slug )
	{
		$this->data['collection'] = $this->shop_collection_model->get_by_slug( $slug );

		if ( ! $this->data['collection' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Collection: "' . $this->data['collection']->label . '"';

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the collection's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/collection).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['collection']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_collection( $this->data['collection']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'collection/' . $this->data['collection']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_collection( $this->data['collection']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',										$this->data );
		$this->load->view( $this->_skin->path . 'views/front/collection/single',	$this->data );
		$this->load->view( 'structure/footer',										$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the product slug and loads the appropriate method
	 * @return void
	 */
	protected function product()
	{
		//	Strip out the store's URL, leave just the product's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'product/?#', '', uri_string() );

		if ( $_slug ) :

			$this->_product_single( $_slug );

		else :

			show_404();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single product
	 * @return void
	 */
	protected function _product_single( $slug )
	{
		$this->data['product'] = $this->shop_product_model->get_by_slug( $slug );

		if ( ! $this->data['product' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------


		//	Generate missing SEO content
		//	============================

		$this->shop_product_model->generate_seo_content( $this->data['product'] );

		// --------------------------------------------------------------------------

		//	SEO
		//	===

		$this->data['page']->title				= $this->_shop_name . ': ';
		$this->data['page']->title				.= $this->data['product']->seo_title ? $this->data['product']->seo_title : $this->data['product']->label;
		$this->data['page']->seo->description	= $this->data['product']->seo_description;
		$this->data['page']->seo->keywords		= $this->data['product']->seo_keywords;

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',									$this->data );
		$this->load->view( $this->_skin->path . 'views/front/product/single',	$this->data );
		$this->load->view( 'structure/footer',									$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the range slug and loads the appropriate method
	 * @return void
	 */
	public function range()
	{
		//	Strip out the store's URL, leave just the range's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'range/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_range_single( $_slug );

		else :

			$this->_range_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of ranges
	 * @return void
	 */
	protected function _range_index()
	{
		if ( ! app_setting( 'page_range_listing', 'shop' ) ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Ranges';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Ranges
		//	======

		$this->data['ranges'] = $this->shop_range_model->get_all();

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/range/index',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single range
	 * @return void
	 */
	protected function _range_single( $slug )
	{
		$this->data['range'] = $this->shop_range_model->get_by_slug( $slug );

		if ( ! $this->data['range' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Range: "' . $this->data['range']->label . '"';

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the range's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/range).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['range']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_range( $this->data['range']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'range/' . $this->data['range']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_range( $this->data['range']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/range/single',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the sale slug and loads the appropriate method
	 * @return void
	 */
	public function sale()
	{
		//	Strip out the store's URL, leave just the sale's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'sale/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_sale_single( $_slug );

		else :

			$this->_sale_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of sales
	 * @return void
	 */
	protected function _sale_index()
	{
		if ( ! app_setting( 'page_sale_listing', 'shop' ) ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Sales';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Sales
		//	=====

		$this->data['sales'] = $this->shop_sale_model->get_all();

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/sale/index',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single sale
	 * @return void
	 */
	protected function _sale_single( $slug )
	{
		$this->data['sale'] = $this->shop_sale_model->get_by_slug( $slug );

		if ( ! $this->data['sale' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Sale: "' . $this->data['sale']->label . '"';

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the sale's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/sale).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['sale']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_sale( $this->data['sale']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'sale/' . $this->data['sale']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_sale( $this->data['sale']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/sale/single',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Detects the tag slug and loads the appropriate method
	 * @return void
	 */
	public function tag()
	{
		//	Strip out the store's URL, leave just the tag's slug
		$_slug = preg_replace( '#' . app_setting( 'url', 'shop' ) . 'tag/?#', '', uri_string() );

		//	Strip out the pagination segment, if present
		$_slug = preg_replace( '#\/\d+$#', '', $_slug );

		if ( $_slug ) :

			$this->_tag_single( $_slug );

		else :

			$this->_tag_index();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the list of tags
	 * @return void
	 */
	protected function _tag_index()
	{
		if ( ! app_setting( 'page_tag_listing', 'shop' ) ) :

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Tags';

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		$this->load->library( 'pagination' );

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Tags
		//	====

		$this->data['tags'] = $this->shop_tag_model->get_all();

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/tag/index',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders a single tag
	 * @return void
	 */
	protected function _tag_single( $slug )
	{
		$this->data['tag'] = $this->shop_tag_model->get_by_slug( $slug );

		if ( ! $this->data['tag' ] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Page title
		//	==========

		$this->data['page']->title = $this->_shop_name . ': Tag: "' . $this->data['tag']->label . '"';

		// --------------------------------------------------------------------------

		//	Categories
		//	==========

		$_data = array( 'include_count' => TRUE );
		$this->data['categories']			= $this->shop_category_model->get_all( NULL, NULL, $_data );
		$this->data['categories_nested']	= $this->shop_category_model->get_all_nested( NULL, $_data );

		// --------------------------------------------------------------------------

		//	Configure Conditionals and Sorting
		//	==================================

		$_data			= array();
		$_data['where']	= array();
		$_data['sort']	= $this->_product_sort->sort_on;

		// --------------------------------------------------------------------------

		//	Pagination
		//	==========

		/**
		 * Set the page number, done per method as the rsegment to use changes place,
		 * like a ninja. Additionally, We need the segment after the tag's slug,
		 * the additional 3 takes into consideration segments 1 & 2 (i.e shop/tag).
		 */

		$this->_product_pagination->rsegment	= count( explode( '/', $this->data['tag']->slug ) ) + 3;
		$this->_product_pagination->page		= (int) $this->uri->rsegment( $this->_product_pagination->rsegment );
		$this->_product_pagination->total		= $this->shop_product_model->count_for_tag( $this->data['tag']->id, $_data );

		$this->_configure_pagination( $this->_product_pagination->total, 'tag/' . $this->data['tag']->slug );

		// --------------------------------------------------------------------------

		//	Products
		//	========

		$this->data['products'] = $this->shop_product_model->get_for_tag( $this->data['tag']->id, $this->_product_pagination->page, $this->_product_pagination->per_page, $_data );

		// --------------------------------------------------------------------------

		//	Load views
		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin->path . 'views/front/tag/single',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Common pagination configurations
	 * @param  integer $total_rows The total number of rows to paginate for
	 * @param  string  $base_url   Any additional part of the URL to add
	 * @return void
	 */
	protected function _configure_pagination( $total_rows = 0, $base_url = '' )
	{
		$this->load->library( 'pagination' );

		$_config = array();

		if ( app_setting( 'url', 'shop' ) ) :

			$_config['base_url'] = app_setting( 'url', 'shop' ) . $base_url;

		else :

			$_config['base_url'] = 'shop/' . $base_url;

		endif;

		$_config['base_url']			= site_url( $_config['base_url'] );
		$_config['total_rows']			= $total_rows;
		$_config['per_page']			= $this->_product_pagination->per_page;
		$_config['use_page_numbers']	= TRUE;
		$_config['use_rsegment']		= TRUE;
		$_config['uri_segment']			= $this->_product_pagination->rsegment;

		// --------------------------------------------------------------------------

		//	Bootstrap-ify
		$_config['full_tag_open'] = '<div class="text-center"><ul class="pagination">';
		$_config['full_tag_close'] = '</ul></div><!--pagination-->';

		$_config['first_link'] = '&laquo; First';
		$_config['first_tag_open'] = '<li class="prev page">';
		$_config['first_tag_close'] = '</li>';

		$_config['last_link'] = 'Last &raquo;';
		$_config['last_tag_open'] = '<li class="next page">';
		$_config['last_tag_close'] = '</li>';

		$_config['next_link'] = 'Next &rarr;';
		$_config['next_tag_open'] = '<li class="next page">';
		$_config['next_tag_close'] = '</li>';

		$_config['prev_link'] = '&larr; Previous';
		$_config['prev_tag_open'] = '<li class="prev page">';
		$_config['prev_tag_close'] = '</li>';

		$_config['cur_tag_open'] = '<li class="active"><a href="">';
		$_config['cur_tag_close'] = '</a></li>';

		$_config['num_tag_open'] = '<li class="page">';
		$_config['num_tag_close'] = '</li>';

		// --------------------------------------------------------------------------

		$this->pagination->initialize( $_config );
	}


	// --------------------------------------------------------------------------


	/**
	 * Manually remap the URL as CI's router has some issues resolving the index()
	 * route, especially when using a non-standard shop base URL
	 * @return void
	 */
	public function _remap()
	{
		if ( is_numeric( $this->uri->rsegment( 2 ) ) ) :

			//	Paginating the front page
			$_method = 'index';

		else :

			$_method = $this->uri->rsegment( 2 ) ? $this->uri->rsegment( 2 ) : 'index';

		endif;

		// --------------------------------------------------------------------------

		if ( method_exists( $this, $_method ) ) :

			$this->{$_method}();

		else :

			show_404();

		endif;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * The following block of code makes it simple to extend one of the core shop
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP' ) ) :

	class Shop extends NAILS_Shop
	{
	}

endif;

/* End of file shop.php */
/* Location: ./application/modules/shop/controllers/shop.php */
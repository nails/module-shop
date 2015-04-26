<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides front end shop functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop extends NAILS_Shop_Controller
{
    protected $product_sort;
    protected $product_pagination;

    // --------------------------------------------------------------------------

    /**
     * Defines the default items, then sets them if they're to be set.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Load the skin to use
        $this->loadSkin('front');

        // --------------------------------------------------------------------------

        //  Product Sorting
        //  ===============

        //  Defaults
        $this->_product_sort       = new \stdClass();
        $this->_product_sort->sort = app_setting('default_product_sort', 'shop');
        $this->_product_sort->sort = $this->_product_sort->sort ? $this->_product_sort->sort : 'recent';

        //  Actual Values
        $this->_product_sort->sort = $this->input->get_post('sort');
        $this->_product_sort->sort = $this->_product_sort->sort ? $this->_product_sort->sort : $this->_product_sort->sort;

        //  Sanitise/translate
        switch ($this->_product_sort->sort) {

            case 'price-high-low':

                $this->_product_sort->sort_on = 'PRICE.DESC';
                break;

            case 'price-low-high':

                $this->_product_sort->sort_on = 'PRICE.ASC';
                break;

            case 'a-z':

                $this->_product_sort->sort_on = $this->shop_product_model->getTablePrefix() . '.label';
                break;

            case 'recent':
            default:

                $this->_product_sort->sort_on =  'PUBLISHED.DESC';
                break;
        }

        //  Pass to views
        $this->data['product_sort'] = $this->_product_sort;

        // --------------------------------------------------------------------------

        //  Product Pagination
        //  ==================

        //  Defaults
        $this->_product_pagination           = new \stdClass();
        $this->_product_pagination->page     = 0;
        $this->_product_pagination->rsegment = 2;
        $this->_product_pagination->total    = 0;
        $this->_product_pagination->per_page = app_setting('default_product_per_page', 'shop');
        $this->_product_pagination->per_page = $this->_product_pagination->per_page ? $this->_product_pagination->per_page : 25;

        //  Actual Values
        $this->_product_pagination->per_page = $this->input->get_post('per_page') ? $this->input->get_post('per_page') : $this->_product_pagination->per_page;

        //  Sanitise
        switch ($this->_product_pagination->per_page) {

            case '20':

                $this->_product_pagination->per_page = 20;
                break;

            case '40':

                $this->_product_pagination->per_page = 40;
                break;

            case '80':

                $this->_product_pagination->per_page = 80;
                break;

            case '100':

                $this->_product_pagination->per_page = 100;
                break;

            case 'all':

                //  C'mon, who's gonna have more than this?
                $this->_product_pagination->per_page = 10000;
                break;

            default:

                $this->_product_pagination->per_page = 20;
                break;
        }

        //  Pass to views
        $this->data['product_pagination'] = $this->_product_pagination;

    }

    // --------------------------------------------------------------------------

    /**
     * Render's the shop's front page
     * @return void
     */
    public function index()
    {
        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName;

        // --------------------------------------------------------------------------

        //  Sidebar Items
        //  =============

        $this->data['categories'] = $this->shop_category_model->getTopLevel();

        $data = array('include_count' => true);
        $this->data['brands']      = $this->shop_brand_model->get_all(null, null, $data);
        $this->data['collections'] = $this->shop_collection_model->get_all(null, null, $data);
        $this->data['ranges']      = $this->shop_range_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja.
         */

        $this->_product_pagination->rsegment = 2;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);

        $this->configurePagination($this->shop_product_model->count_all($data));

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->get_all(
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the brand slug and loads the appropriate method
     * @return void
     */
    public function brand()
    {
        //  Strip out the store's URL, leave just the brand's slug
        $slug = preg_replace('#' . $this->shopUrl . 'brand/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->brandSingle($slug);

        } else {

            $this->brandIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of brands
     * @return void
     */
    protected function brandIndex()
    {
        if (!app_setting('page_brand_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Brands';

        // --------------------------------------------------------------------------

        //  Brands
        //  ======

        $this->data['brands'] = $this->shop_brand_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/brand/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single brand
     * @return void
     */
    protected function brandSingle($slug)
    {
        $this->data['brand'] = $this->shop_brand_model->get_by_slug($slug);

        if (!$this->data['brand' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Brand: "' . $this->data['brand']->label . '"';

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the category's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/category).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['brand']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForBrand($this->data['brand']->id, $data);

        $this->configurePagination($this->_product_pagination->total, 'brand/' . $this->data['brand']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForBrand(
            $this->data['brand']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInBrand(
            $this->data['brand']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/brand/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the category slug and loads the appropriate method
     * @return void
     */
    public function category()
    {
        //  Strip out the store's URL, leave just the category's slug
        $slug = preg_replace('#' . $this->shopUrl . 'category/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->categorySingle($slug);

        } else {

            $this->categoryIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of categories
     * @return void
     */
    protected function categoryIndex()
    {
        if (!app_setting('page_category_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Categories';

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        $this->load->library('pagination');

        // --------------------------------------------------------------------------

        //  Categories
        //  ==========

        $data = array('include_count' => true);
        $this->data['categories']        = $this->shop_category_model->get_all(null, null, $data);
        $this->data['categories_nested'] = $this->shop_category_model->getAllNested($data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/category/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single category
     * @return void
     */
    protected function categorySingle($slug)
    {
        $data = array('include_count' => true);
        $this->data['category'] = $this->shop_category_model->get_by_slug($slug, $data);

        if (!$this->data['category' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Generate missing SEO content
        //  ============================

        $this->shop_category_model->generateSeoContent($this->data['category']);

        //  SEO
        //  ===
        $this->data['page']->title = $this->shopName . ': Category: "' . $this->data['category']->label . '"';
        $this->data['page']->seo->description = $this->data['category']->seo_description;
        $this->data['page']->seo->keywords    = $this->data['category']->seo_keywords;

        // --------------------------------------------------------------------------

        //  Category's (immediate) decendants
        //  =================================

        $this->data['category']->children = $this->shop_category_model->get_children(
            $this->data['category']->id,
            true,
            $data
       );

        // --------------------------------------------------------------------------

        //  Category's siblings
        //  =================================

        $this->data['category_siblings'] = $this->shop_category_model->getSiblings(
            $this->data['category']->id,
            $data
       );

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the category's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/category).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['category']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForCategory(
            $this->data['category']->id,
            $data
       );

        $this->configurePagination($this->_product_pagination->total, 'category/' . $this->data['category']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForCategory(
            $this->data['category']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInCategory(
            $this->data['category']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/category/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the collection slug and loads the appropriate method
     * @return void
     */
    public function collection()
    {
        //  Strip out the store's URL, leave just the colelction's slug
        $slug = preg_replace('#' . $this->shopUrl . 'collection/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->collectionSingle($slug);

        } else {

            $this->collectionIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of collections
     * @return void
     */
    protected function collectionIndex()
    {
        if (!app_setting('page_collection_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Collections';

        // --------------------------------------------------------------------------

        //  Collections
        //  ===========

        $this->data['collections'] = $this->shop_collection_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/collection/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single collection
     * @return void
     */
    protected function collectionSingle($slug)
    {
        $this->data['collection'] = $this->shop_collection_model->get_by_slug($slug);

        if (!$this->data['collection' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Collection: "' . $this->data['collection']->label . '"';

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the collection's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/collection).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['collection']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForCollection(
            $this->data['collection']->id,
            $data
       );

        $this->configurePagination($this->_product_pagination->total, 'collection/' . $this->data['collection']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForCollection(
            $this->data['collection']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInCollection(
            $this->data['collection']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/collection/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the product slug and loads the appropriate method
     * @return void
     */
    protected function product()
    {
        //  Strip out the store's URL, leave just the product's slug
        $slug = preg_replace('#' . $this->shopUrl . 'product/?#', '', uri_string());

        if ($slug) {

            $this->productSingle($slug);

        } else {

            show_404('', true);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single product
     * @return void
     */
    protected function productSingle($slug)
    {
        $this->data['product'] = $this->shop_product_model->get_by_slug($slug);

        if (!$this->data['product']) {

            show_404('', true);

        } elseif (!$this->data['product']->is_active && !userHasPermission('admin:shop:inventory:manage')) {

            show_404();

        } elseif (!$this->data['product']->is_active) {

            $this->data['message']  = '<strong>This product is not public</strong><br />';
            $this->data['message'] .= 'This product is not active; you can see this page because your ';
            $this->data['message'] .= 'account has permission to manage inventory items.';
        }

        // --------------------------------------------------------------------------

        //  Add as a recently viewed product for this user
        $this->shop_product_model->addAsRecentlyViewed($this->data['product']->id);

        // --------------------------------------------------------------------------

        //  Generate missing SEO content
        //  ============================

        $this->shop_product_model->generateSeoContent($this->data['product']);

        // --------------------------------------------------------------------------

        //  SEO
        //  ===

        $this->data['page']->title             = $this->shopName . ': ';
        $this->data['page']->title            .= $this->data['product']->seo_title ? $this->data['product']->seo_title : $this->data['product']->label;
        $this->data['page']->seo->description  = $this->data['product']->seo_description;
        $this->data['page']->seo->keywords     = $this->data['product']->seo_keywords;

        // --------------------------------------------------------------------------

        /**
         * This URL is set by the shop homepage and the *Single() controllers; it
         * defines the last page that the user was on so that the "go back" button on
         * the product page takes them somewhere meaningful.
         */

        $this->data['goBackUrl'] = $this->session->userdata('shopLastBrowsePage');
        $this->data['goBackUrl'] = $this->data['goBackUrl'] ? site_url($this->data['goBackUrl']) : site_url($this->shopUrl);

        // --------------------------------------------------------------------------

        //  Product Reviews
        //  @todo
        $this->data['productReviews'] = array();

        // --------------------------------------------------------------------------

        //  Related Products
        $this->data['relatedProducts'] = $this->shop_product_model->getRelatedProducts($this->data['product']->id);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/product/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the range slug and loads the appropriate method
     * @return void
     */
    public function range()
    {
        //  Strip out the store's URL, leave just the range's slug
        $slug = preg_replace('#' . $this->shopUrl . 'range/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->rangeSingle($slug);

        } else {

            $this->rangeIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of ranges
     * @return void
     */
    protected function rangeIndex()
    {
        if (!app_setting('page_range_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Ranges';

        // --------------------------------------------------------------------------

        //  Ranges
        //  ======

        $this->data['ranges'] = $this->shop_range_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/range/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single range
     * @return void
     */
    protected function rangeSingle($slug)
    {
        $this->data['range'] = $this->shop_range_model->get_by_slug($slug);

        if (!$this->data['range' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Range: "' . $this->data['range']->label . '"';

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the range's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/range).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['range']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForRange($this->data['range']->id, $data);

        $this->configurePagination($this->_product_pagination->total, 'range/' . $this->data['range']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForRange(
            $this->data['range']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInRange(
            $this->data['range']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/range/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the sale slug and loads the appropriate method
     * @return void
     */
    public function sale()
    {
        //  Strip out the store's URL, leave just the sale's slug
        $slug = preg_replace('#' . $this->shopUrl . 'sale/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->saleSingle($slug);

        } else {

            $this->saleIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of sales
     * @return void
     */
    protected function saleIndex()
    {
        if (!app_setting('page_sale_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Sales';

        // --------------------------------------------------------------------------

        //  Sales
        //  =====

        $this->data['sales'] = $this->shop_sale_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/sale/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single sale
     * @return void
     */
    protected function saleSingle($slug)
    {
        $this->data['sale'] = $this->shop_sale_model->get_by_slug($slug);

        if (!$this->data['sale' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Sale: "' . $this->data['sale']->label . '"';

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the sale's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/sale).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['sale']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForSale($this->data['sale']->id, $data);

        $this->configurePagination($this->_product_pagination->total, 'sale/' . $this->data['sale']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForSale(
            $this->data['sale']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInSale(
            $this->data['sale']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/sale/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the tag slug and loads the appropriate method
     * @return void
     */
    public function tag()
    {
        //  Strip out the store's URL, leave just the tag's slug
        $slug = preg_replace('#' . $this->shopUrl . 'tag/?#', '', uri_string());

        //  Strip out the pagination segment, if present
        $slug = preg_replace('#\/\d+$#', '', $slug);

        if ($slug) {

            $this->tagSingle($slug);

        } else {

            $this->tagIndex();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the list of tags
     * @return void
     */
    protected function tagIndex()
    {
        if (!app_setting('page_tag_listing', 'shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Tags';

        // --------------------------------------------------------------------------

        //  Tags
        //  ====

        $this->data['tags'] = $this->shop_tag_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/tag/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a single tag
     * @return void
     */
    protected function tagSingle($slug)
    {
        $this->data['tag'] = $this->shop_tag_model->get_by_slug($slug);

        if (!$this->data['tag' ]) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName . ': Tag: "' . $this->data['tag']->label . '"';

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data            = array();
        $data['where']   = array();
        $data['where'][] = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']    = $this->_product_sort->sort_on;
        $data['filter']  = $this->input->get('f');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja. Additionally, We need the segment after the tag's slug,
         * the additional 3 takes into consideration segments 1 & 2 (i.e shop/tag).
         */

        $this->_product_pagination->rsegment = count(explode('/', $this->data['tag']->slug)) + 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);
        $this->_product_pagination->total    = $this->shop_product_model->countForTag($this->data['tag']->id, $data);

        $this->configurePagination($this->_product_pagination->total, 'tag/' . $this->data['tag']->slug);

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->getForTag(
            $this->data['tag']->id,
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInTag(
            $this->data['tag']->id,
            $data
       );

        // --------------------------------------------------------------------------

        /**
         * Take a note of this page, this is used in the single product page for the
         * "go back" button. This avoids the use of javascript.
         */

        $this->session->set_userdata('shopLastBrowsePage', uri_string());

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/tag/single', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    public function search()
    {
        //  Page title
        //  ==========

        $this->data['page']->title = $this->shopName;

        if (!$this->input->get('s')) {

            $this->data['message'] = 'Please enter a search term.';
        }

        // --------------------------------------------------------------------------

        //  Configure Conditionals and Sorting
        //  ==================================

        $data             = array();
        $data['where']    = array();
        $data['where'][]  = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['sort']     = $this->_product_sort->sort_on;
        $data['filter']   = $this->input->get('f');
        $data['keywords'] = $this->input->get('s');

        // --------------------------------------------------------------------------

        //  Pagination
        //  ==========

        /**
         * Set the page number, done per method as the rsegment to use changes place,
         * like a ninja.
         */

        $this->_product_pagination->rsegment = 3;
        $this->_product_pagination->page     = (int) $this->uri->rsegment($this->_product_pagination->rsegment);

        $this->configurePagination($this->shop_product_model->count_all($data), 'search', '?s=' . $this->input->get('s'));

        // --------------------------------------------------------------------------

        //  Products
        //  ========

        $this->data['products'] = $this->shop_product_model->get_all(
            $this->_product_pagination->page,
            $this->_product_pagination->per_page,
            $data
       );

        // --------------------------------------------------------------------------

        //  Sidebar Filters
        //  ===============

        $this->data['sidebar_filters'] = $this->shop_product_model->getFiltersForProductsInSearch(
            $data['keywords'],
            $data
       );

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/front/search/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Common pagination configurations
     * @param  integer $total_rows The total number of rows to paginate for
     * @param  string  $baseUrl    Any additional part of the URL to add
     * @param  string  $firstUrl   An alternative URl for the first link
     * @return void
     */
    protected function configurePagination($total_rows = 0, $baseUrl = '', $firstUrl = '')
    {
        $this->load->library('pagination');

        $config = array();

        if ($this->shopUrl) {

            $config['base_url'] = $this->shopUrl . $baseUrl;

        } else {

            $config['base_url'] = 'shop/' . $baseUrl;
        }

        $config['base_url']         = site_url($config['base_url']);
        $config['first_url']        = site_url($config['base_url'] . $firstUrl);
        $config['total_rows']       = $total_rows;
        $config['per_page']         = $this->_product_pagination->per_page;
        $config['use_page_numbers'] = true;
        $config['use_rsegment']     = true;
        $config['uri_segment']      = $this->_product_pagination->rsegment;

        // --------------------------------------------------------------------------

        //  If there's any get data then bind that tot eh end
        $get = (array) $this->input->get();
        $get = array_filter($get);
        $get = http_build_query($get);
        $config['suffix'] = $get ? '?' . $get : '';

        // --------------------------------------------------------------------------

        //  Bootstrap-ify
        $config['full_tag_open'] = '<div class="text-center"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></div><!--pagination-->';

        $config['first_link'] = '&laquo; First';
        $config['first_tag_open'] = '<li class="prev page">';
        $config['first_tag_close'] = '</li>';

        $config['last_link'] = 'Last &raquo;';
        $config['last_tag_open'] = '<li class="next page">';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = 'Next &rarr;';
        $config['next_tag_open'] = '<li class="next page">';
        $config['next_tag_close'] = '</li>';

        $config['prev_link'] = '&larr; Previous';
        $config['prev_tag_open'] = '<li class="prev page">';
        $config['prev_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active"><a href="">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page">';
        $config['num_tag_close'] = '</li>';

        // --------------------------------------------------------------------------

        $this->pagination->initialize($config);
    }

    // --------------------------------------------------------------------------

    /**
     * Manually remap the URL as CI's router has some issues resolving the index()
     * route, especially when using a non-standard shop base URL
     * @return void
     */
    public function _remap()
    {
        if (is_numeric($this->uri->rsegment(2))) {

            //  Paginating the front page
            $method = 'index';

        } else {

            $method = $this->uri->rsegment(2) ? $this->uri->rsegment(2) : 'index';
        }

        // --------------------------------------------------------------------------

        if (method_exists($this, $method) && substr($method, 0, 1) != '_') {

            $this->{$method}();

        } else {

            show_404();
        }
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP')) {

    class Shop extends NAILS_Shop
    {
    }
}

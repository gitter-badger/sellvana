<?php

class FCom_IndexTank_Model_Product extends BClass
{
    protected $_index_name = 'products';
    protected $_model;

    //main DOCID - unique for each records
    const DOC_ID = 'doc_id';

    //text fields IndexDen
    const FT_PRODUCT_NAME = 'product_name';
    const FT_DESCRIPTION = 'text';
    const FT_NOTES = 'notes';
    const FT_MANUF_SKU = 'manuf_sku';
    const FT_TIMESTAMP = 'timestamp';
    /**
     * Special text field which will contain word 'all'
     * It will be used when we will need to fetch all documents from the index
     * without performing a search query
     */
    const FT_MATCH = 'match';


    //categories for IndexDen
    /**
     * Level for categories
     * Example:
     * array ( self::CT_CATEGORY_PREFIX . $CAT_ID => 'Electronics' )
     */
    const CT_CATEGORY_PREFIX = 'ct_category_';

    /**
     * Prefix for custom field
     * Example:
     * array ( self::CT_CUSTOM_FIELD_PREFIX . 'display_inch' => '29' )
     */
    const CT_CUSTOM_FIELD_PREFIX = 'ct_custom_field_';

    /**
     * Textual price range representation like '$100 to $299'
     */
    const CT_PRICE_RANGE = 'ct_price_range';

    /**
     * Brand name
     */
    const CT_BRAND = 'ct_brand';

    /**
     * Prefix for seller name
     */
    const CT_SELLER_PREFIX = 'ct_seller_';


    //variables for IndexDen
    const VAR_PRICE         = 0;
    const VAR_RATING        = 1;


    /**
     *
     * @return Indextank_Index
     * @throws Exception if index not found
     */
    public function model()
    {
        if (empty($this->_model)){
            try {
                $this->_model = FCom_IndexTank::i()->service()->create_index($this->_index_name);
            } catch (Exception $e){
                $this->_model = FCom_IndexTank::i()->service()->get_index($this->_index_name);
            }
        }
        return $this->_model;
    }

    /**
     *
     * @param string $query
     * @return array $products of FCom_Catalog_Model_Product objects
     * @throws Exception if query failed
     */
    public function search($query)
    {
        if (!empty($query)){
            $queryString = self::FT_PRODUCT_NAME . ":($query)^10 ".self::FT_DESCRIPTION.":($query) ";
        } else {
            $queryString = "match:all";
        }
        try {
            $result = $this->model()->search($queryString);
        } catch(Exception $e) {
            throw $e;
        }

        if ($result->matches <= 0){
            return false;
        }

        $products = array();
        $product_model = FCom_Catalog_Model_Product::i();
        foreach ($result->results as $res){
            $products[] = $product_model->load($res->{self::DOC_ID});
        }
        return $products;
    }

    /**
     *
     * @param array $products of FCom_Catalog_Model_Product objects
     */
    public function add($products)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }
        /**
         * Structure of documents array
         $documents = array();
         $documents[]= array(
         *      "docid" => "doc1",
         *      "fields" => array( "text" => "text1 is short" ) );
         $documents[]= array(
         *      "docid" => "doc2",
         *      "fields" => array( "text" => "text2 is a bit longer" ),
         *      "categories" => array("priceRange" => "$100 to $200", "Brand" => "Sony") );
         $documents[]= array(
         *      "docid" => "doc3",
         *      "fields" => array( "text" => "text3 is longer than text2" ),
         *      "variables" => array( 0 => 1.5 )
        */
        $documents = array();

        foreach($products as $product){
            $categories = array(
                self::CT_PRICE_RANGE    => $product->getPriceRangeText(),
                self::CT_BRAND          => $product->getBrandName()
             );

            $product_categories = array(); //get all categories for product
            foreach ($product_categories as $cat) {
                $categories[self::CT_CATEGORY_PREFIX . $cat->full_name] = $cat->node_name;
            }

            $product_custom_fields = array(); //get all custom fields for product
            foreach ($product_custom_fields as $cf) {
                $categories[self::CT_CUSTOM_FIELD_PREFIX . $cf->field_type. $cf->field_code .$cf->field_name] = $cf->label;
            }

            $product_sellers = array(); //get all custom fields for product
            foreach ($product_sellers as $seller) {
                $categories[self::CT_SELLER_PREFIX . $seller->name] = 'Yes';
            }


            //get all variables
            $variables = array(
                self::VAR_PRICE         => $product->base_price,
                self::VAR_RATING        => $product->rating()
            );

            //get all text fields
            $fields = array(
                self::FT_DESCRIPTION    => $product->description,
                self::FT_PRODUCT_NAME   => $product->product_name,
                self::FT_MANUF_SKU      => $product->manuf_sku,
                self::FT_NOTES          => $product->notes,
                self::FT_TIMESTAMP      => strtotime($product->updated_dt),
                self::FT_MATCH          => "all"
            );

            $documents[][self::DOC_ID] = $product->id();

            $documents[]['fields'] = $fields;
            if (!empty($categories)){
                $documents[]['categories'] = $categories;
            }
            if (!empty($variables)){
                $documents[]['variables'] = $variables;
            }
        }

        $this->model()->add_documents($documents);
    }

    public function update_categories($product_id)
    {
        $products = array ( FCom_Catalog_Model_Product::i()->load($product_id) );

        foreach($products as $product){
            $categories = array(
                self::CT_PRICE_RANGE    => $product->getPriceRangeText(),
                self::CT_BRAND          => $product->getBrandName()
             );

            $product_categories = array(); //get all categories for product
            foreach ($product_categories as $cat) {
                $categories[self::CT_CATEGORY_PREFIX . $cat->full_name] = $cat->node_name;
            }

            $product_custom_fields = array(); //get all custom fields for product
            foreach ($product_custom_fields as $cf) {
                $categories[self::CT_CUSTOM_FIELD_PREFIX . $cf->field_type. $cf->field_code .$cf->field_name] = $cf->label;
            }

            $product_sellers = array(); //get all custom fields for product
            foreach ($product_sellers as $seller) {
                $categories[self::CT_SELLER_PREFIX . $seller->name] = 'Yes';
            }
            $this->model()->update_categories($product->id(), $categories);
        }

    }

    public function update_variables($product_id)
    {
        $products = array ( FCom_Catalog_Model_Product::i()->load($product_id) );

        foreach($products as $product){
            $variables = array(
                self::VAR_PRICE         => $product->base_price,
                self::VAR_RATING        => $product->rating()
            );
            $this->model()->update_variables($product_id, $variables);
        }
    }

    public function delete($products)
    {
        if (!is_array($products)){
            $products = array($products);
        }
        $docids = array();
        foreach($products as $product){
            $docids[] = $product->id();
        }
        $this->model()->delete_documents($docids);
    }
}
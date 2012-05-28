<?php

class FCom_IndexTank_Index_Product extends FCom_IndexTank_Index_Abstract
{
    /**
     * Name of the index
     * @var string
     */
    protected $_indexName = 'products';

    /**
     * IndexTank API object
     * @var FCom_IndexTank_Api
     */
    protected $_model;


    /**
     * Defined scoring functions for products index
     * @var array
     */
    protected $_functions  =  array ();


    /**
     * Selected scoring function for current search session
     * @var integer
     */
    protected $_scoringFunction = 0;

    /**
     * Selected filters for current search session
     * @var array
     */
    protected $_filterCategory = null;

    /**
     * Set category which required rollup totals
     * @var array
     */
    protected $_rollupCategory = null;

    /**
     * Selected document variables filter for current search session
     * @var array
     */
    protected $_filterDocvar = null;

    /**
     * Search result object
     * @var object
     */
    protected $_result = null;


    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Index_Product
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    /**
     * Load defined scoring functions
     */
    protected function _init_functions()
    {
        //scoring functions definition for IndexDen
        //todo: move them into configuration
        $functionList  =  FCom_IndexTank_Model_ProductFunction::i()->get_list();
        foreach ($functionList as $func) {
            $this->_functions[$func->name] = $func;
        }
    }

    /**
     *
     * @return Indextank_Index
     * @throws Exception if index not found
     */
    public function model()
    {
        if (empty($this->_model)){
            //init index name
            if(false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
                $this->_indexName = $indexName;
            }
            //init config
            $this->_init_functions();
            //init model
            $this->_model = FCom_IndexTank_Api::i()->service()->get_index($this->_indexName);
        }
        return $this->_model;
    }

    /**
     * Set scoring function to use in current search session
     * @param string $function
     * @throws Exception
     */
    public function scoring_by($function)
    {
        $this->model();
        if (empty($this->_functions[$function])){
            throw new Exception('Scoring function does not exist: ' . $function);
        }
        $this->_scoringFunction = $this->_functions[$function]->number;
    }

    /**
     * Set filter for current search session
     * @param string $category
     * @param integer $value
     */
    public function filter_by($category, $value)
    {
        $this->_filterCategory[$category][] = $value;
    }

    /**
     * Set range filter for current search session
     * @param integer $var
     * @param float $from
     * @param float $to
     */
    public function filter_range($var, $from, $to)
    {
        $this->_filterDocvar[$var][] = array($from, $to);
    }


    /**
     * Set categories for rollup
     * @param string $category
     */
    public function rollup_by($category)
    {
        $this->_rollupCategory[] = $category;

    }

    /**
     * Reset filters
     */
    public function reset_filters()
    {
        $this->_filterCategory = array();
    }

    /**
     * Get index status
     * @return array
     */
    public function status()
    {
        $metadata = $this->model()->get_metadata();
        $result = array (
            'name'          => $this->_indexName,
            'code'          => $metadata->code,
            'status'        => $metadata->status,
            'size'          => $metadata->size,
            'date'          => $metadata->creation_time
        );
        return $result;
    }


    /**
     *
     * @param string $query
     * @return array $products of FCom_Catalog_Model_Product objects
     * @throws Exception
     */
    public function search($query, $start=null, $len=null)
    {
        if (!empty($query)){

            $productFields = FCom_IndexTank_Model_ProductField::i()->get_search_list();
            $queryString = '';

            foreach($productFields as $pfield){
                $priority = '';
                if($pfield->priority > 1){
                    $priority = ' ^'.$pfield->priority;
                }
                if(!empty($queryString)){
                    $queryString .= " OR ";
                } else {
                    $queryString = $query . " OR ";
                }

                $queryString .= " {$pfield->field_name}:$query" . $priority." ";
            }

        } else {
            $queryString = "match:all";
        }
//echo $queryString;exit;
        try {
            //search($query, $start = NULL, $len = NULL, $scoring_function = NULL,
            //$snippet_fields = NULL, $fetch_fields = NULL, $category_filters = NULL,
            //$variables = NULL, $docvar_filters = NULL, $function_filters = NULL, $category_rollup = NULL, $match_any_field = NULL )
            $categoryRollup = null;
            if($this->_rollupCategory){
                $categoryRollup = implode(",", $this->_rollupCategory);
            }

            $result = $this->model()->search($queryString, $start, $len, $this->_scoringFunction,
                    null, null, $this->_filterCategory,
                    null, $this->_filterDocvar, null, $categoryRollup, true );

        } catch(Exception $e) {
            throw $e;
        }

        $this->_result = $result;
        //print_r( $this->_result);exit;
        if (!$result || $result->matches <= 0){
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }

        $products = array();
        //$product_model = FCom_Catalog_Model_Product::i();
        foreach ($result->results as $res){
            $products[] = $res->docid;
        }
        if(!$products){
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }
        $productsORM = FCom_Catalog_Model_Product::i()->orm('p')->where_in("p.id", $products)
                ->order_by_expr("FIELD(p.id, ".implode(",", $products).")");
        return $productsORM;
    }

    public function total_found()
    {
        return !empty($this->_result) ? $this->_result->matches : 0;
    }

    /**
     * Return facets with merged rollups
     * @return array
     */
    public function getFacets()
    {
        if (!isset($this->_result->facets)){
            return false;
        }
        $facets = get_object_vars($this->_result->facets);
        $res = array();
        foreach($facets as $k => $v){
            $res[$k] = get_object_vars($v);
        }
        if (!empty($this->_result->facets_rollup)){
            foreach($this->_result->facets_rollup as $k => $v){
                $res[$k] = get_object_vars($v);
            }
        }

        return $res;
    }

    /**
     * Collect all data (text fields, categoreis, variables) for $product and add it to the index
     * @param array $products of FCom_Catalog_Model_Product objects
     */
    public function add($products, $limit = 500)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }

        $counter = 0;
        $documents = array();
        foreach($products as $i => $product){
            $categories     = $this->_prepareCategories($product);
            $variables      = $this->_prepareVariables($product);
            $fields         = $this->_prepareFields($product);

            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;
            if (!empty($categories)){
                $documents[$i]['categories'] = $categories;
            }
            if (!empty($variables)){
                $documents[$i]['variables'] = $variables;
            }

            //submit every N products to IndexDen - this protect from network overloading
            if ( 0 == $counter++ % $limit ){
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }

        if ($documents){
            $this->model()->add_documents($documents);
        }
    }

    public function updateTextField($products, $field, $fieldValue)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }

        $limit = 500;
        $counter = 0;
        $documents = array();
        foreach($products as $i => $product){
            $fields[$field] = $fieldValue;
            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;

            //submit every N products to IndexDen - this protect from network overloading
            if ( 0 == $counter++ % $limit ){
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }

        if ($documents){
            $this->model()->add_documents($documents);
        }
    }

    public function update_categories($product)
    {
        $categories = $this->_prepareCategories($product);
        $this->model()->update_categories($product->id(), $categories);
    }

    public function get_category_key($category)
    {
        //return 'ct_categories___'.str_replace("/","__",$category->url_path);
        return 'ct_'.$category->id();
    }

    public function get_custom_field_key($cf_model)
    {
        //return 'cf_'.$cf_model->field_type.'___'.$cf_model->field_code;
        return 'cf_'.$cf_model->id();
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param FCom_Catalog_Model_Category $category
     */
    public function delete_categories($product, $category)
    {
        $this->delete_category($product, $this->get_category_key($category));
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param string $categoryField in IndexDen
     */
    public function delete_category($product, $categoryField)
    {
        $category = array($categoryField => "");
        $this->model()->update_categories($product->id(), $category);
    }

    public function update_variables($product)
    {
        $variables = $this->_prepareVariables($product);
        $this->model()->update_variables($product->id(), $variables);
    }

    public function update_functions()
    {
        $functions = FCom_IndexTank_Model_ProductFunction::i()->get_list();
        if(!$functions){
            return;
        }
        foreach($functions as $func){
            $this->update_function($func->number, $func->definition);
        }
    }
    public function update_function($number, $definition)
    {
        if('' === $definition){
            return $this->model()->delete_function($number);
        } else {
            return $this->model()->add_function($number, $definition);
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

    /**
     * Process facets filters to show in view
     * @param type $facets
     * @return type
     */
    public function collectFacets($facets)
    {
        $facetsData = array();
        if($facets){
            $cmp = function($a, $b)
            {
                return strnatcmp($a->name, $b->name);
            };

            $facetsFields = FCom_IndexTank_Model_ProductField::i()->get_facets_list();

            //get categories
            foreach($facets as $fname => $fvalues){
                //get other fields
                if( isset($facetsFields[$fname]) ){
                    foreach($fvalues as $fvalue => $fcount) {
                            $obj = new stdClass();
                            $obj->name = $fvalue;
                            $obj->count = $fcount;
                            $obj->key = $fname;
                            $obj->category = false;
                            if ('inclusive' == $facetsFields[$fname]->filter || empty($facetsFields[$fname]->filter)){
                                $obj->param = "f[{$obj->key}][{$obj->name}]";
                            } else {
                                $obj->param = "f[{$obj->key}][]";
                            }
                            $facetsData[$facetsFields[$fname]->field_nice_name][] = $obj;
                    }
                }
            }
            foreach($facetsData as &$values){
                usort($values, $cmp);
            }
        }
        return $facetsData;
    }

    public function collectCategories($facets)
    {
        $categoryData = array();
        if($facets){
            //get categories
            foreach($facets as $fname => $fvalues){
                //hard coded ct_categories prefix
                $pos = strpos($fname, 'ct_');
                if ($pos !== false){
                    $cat_id = substr($fname, strlen('ct_'));
                    $category = FCom_Catalog_Model_Category::i()->load($cat_id);
                    if(!$category){
                        continue;
                    }
                    $level = count(explode("/", $category->id_path))-1;
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->key = $this->get_category_key($category);
                        $obj->level = $level;
                        $obj->category = true;
                        $obj->param = "f[category]";
                        $categoryData['Categories'][$category->id_path] = $obj;
                    }
                }
            }

            if (!empty($categoryData['Categories'])){
                ksort($categoryData['Categories']);
            }
        }
        return $categoryData;
    }


    protected function _processFields($fieldsList, $product, $type='')
    {
        $result = array();
        foreach($fieldsList as $field){
            switch($field->source_type){
                case 'product':
                    //get value of product object
                    $value = $product->{$field->source_value};
                    $result[$field->field_name] = $value;
                    break;
                case 'function':
                    //call function
                    $valuesList = $this->{"_field_".$field->source_value}($product, $type);
                    //process results
                    if($valuesList){
                        if(is_array($valuesList)){
                            foreach ($valuesList as $searchName => $searchValue) {
                                $result[$searchName] = $searchValue;
                            }
                        }  else {
                            $result[$field->field_name] = $valuesList;
                        }

                    }
                    break;
            }
        }
        return $result;
    }

    protected function _prepareFields($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->get_search_list();
        $searches = $this->_processFields($fieldsList, $product, 'search');
        //add two special fields
        $searches['timestamp'] = strtotime($product->update_dt);
        $searches['match'] = "all";

        return $searches;
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @return array
     */
    protected function _prepareCategories($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->get_facets_list();
        $categories = $this->_processFields($fieldsList, $product);
        return $categories;

    }

    protected function _prepareVariables($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->get_varialbes_list();
        $variablesList = $this->_processFields($fieldsList, $product);

        $variables = array();
        foreach($fieldsList as $field){
            $variables[$field->var_number] = $variablesList[$field->source_value];
        }
        return $variables;
    }

    /**
     * Run by migration script.
     * Create index name 'products' and install scoring functions.
     */
    public function install()
    {
        //init index name
        if(false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_indexName = $indexName;
        }

        try {
            //create an index
            $this->_model = FCom_IndexTank_Api::i()->service()->create_index($this->_indexName);
        } catch(Exception $e) {
            $this->_model = FCom_IndexTank_Api::i()->service()->get_index($this->_indexName);
        }
    }

    public function drop_index()
    {
        if(false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_indexName = $indexName;
        }
        $this->model()->delete_index();
    }

    public function create_index()
    {
        if(false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_indexName = $indexName;
        }
        FCom_IndexTank_Api::i()->service()->create_index($this->_indexName);
    }


    /*************** Field init functions *******************
     * Start field functions with _field_ prefix
     * Example:
     * For field with source_type 'function' and source_value 'get_label'
     * create following function
     * private function _field_get_label()
     * {
     *      return 'Text label';
     * }
     */

    protected function _field_get_categories($product, $type='')
    {
        $categories = array();
        $productCategories = $product->categories($product->id()); //get all categories for product
        if ($productCategories){
            foreach ($productCategories as $cat) {
                $catPath = $this->get_category_key($cat);//str_replace("/","__",$cat->url_path);
                $categories[$catPath] = $cat->node_name;
            }
        }
        if ('search' == $type){
            return "/".implode("/", $categories);
        }
        return $categories;
    }

    protected function _field_get_brands($product, $type='')
    {
        return (rand(0, 100) % 2 == 0) ? "Brand 1": "Brand 2";
    }

    protected function _field_price_range_large($product, $type='')
    {
        if ($product->base_price < 100) {
            return '$0 to $99';
        } else if ($product->base_price < 200) {
            return '$100 to $199';
        }else if ($product->base_price < 300) {
            return '$200 to $299';
        }else if ($product->base_price < 400) {
            return '$300 to $399';
        }else if ($product->base_price < 500) {
            return '$400 to $499';
        }else if ($product->base_price < 600) {
            return '$500 to $599';
        }else if ($product->base_price < 700) {
            return '$600 to $699';
        }else if ($product->base_price < 800) {
            return '$700 to $799';
        }else if ($product->base_price < 900) {
            return '$800 to $899';
        }else if ($product->base_price < 1000) {
            return '$900 to $999';
        }


    }

    protected function _field_min_price_range_large($product, $type='')
    {
        if ($product->min_price < 100) {
            return '$0 to $99';
        } else if ($product->min_price < 200) {
            return '$100 to $199';
        }else if ($product->min_price < 300) {
            return '$200 to $299';
        }else if ($product->min_price < 400) {
            return '$300 to $399';
        }else if ($product->min_price < 500) {
            return '$400 to $499';
        }else if ($product->min_price < 600) {
            return '$500 to $599';
        }else if ($product->min_price < 700) {
            return '$600 to $699';
        }else if ($product->min_price < 800) {
            return '$700 to $799';
        }else if ($product->min_price < 900) {
            return '$800 to $899';
        }else if ($product->min_price < 1000) {
            return '$900 to $999';
        }


    }

    protected function _field_price_range_smart($product, $type='')
    {
        $p = $product->base_price;
        $p2_u = ceil($p/10)*10;
        $p2_d = floor($p/10)*10;
        if($p2_u == $p2_d){
            $p2_u += 10;
        }
        return '$'.$p2_d.' to $'.$p2_u;
    }




}
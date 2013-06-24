<?php

class FCom_CatalogIndex_Main extends BClass
{
    protected static $_filterParams;
    protected static $_indexData;
    protected static $_filterValues;
    protected static $_maxChunkSize = 1000;

    static public function parseUrl()
    {
        if (($getFilters = BRequest::i()->get('filters'))) {
            $getFiltersArr = explode('.', $getFilters);
            static::$_filterParams = array();
            foreach ($getFiltersArr as $filterStr) {
                if ($filterStr==='') {
                    continue;
                }
                $filterArr = explode('-', $filterStr, 2);
                if (empty($filterArr[1])) {
                    continue;
                }
                $valueArr = explode(' ', $filterArr[1]);
                foreach ($valueArr as $v) {
                    if ($v==='') {
                        continue;
                    }
                    static::$_filterParams[$filterArr[0]][$v] = $v;
                }
            }
        }
        return static::$_filterParams;
    }

    static public function getUrl($add=array(), $remove=array())
    {
        $filters = array();
        $params = static::$_filterParams;
        if ($add) {
            foreach ($add as $fKey=>$fValues) {
                foreach ((array)$fValues as $v) {
                    $params[$fKey][$v] = $v;
                }
            }
        }
        if ($remove) {
            foreach ($remove as $fKey=>$fValues) {
                foreach ((array)$fValues as $v) {
                    unset($params[$fKey][$v]);
                }
            }
        }
        foreach ($params as $fKey=>$fValues) {
            if ($fValues) {
                $filters[] = $fKey.'-'.join(' ', (array)$fValues);
            }
        }
        return BUtil::setUrlQuery(BRequest::currentUrl(), array('filters'=>join('.', $filters)));
    }


    static public function onProductSaveAfter($args)
    {
        FCom_CatalogIndex_Indexer::indexProducts(array($args['model']));
    }

    static public function onCustomFieldSaveAfter($args)
    {
        $indexField = FCom_CatalogIndex_Model_Field::i()->load('field_name', $args['field']->field_code);
        if ($indexField) {
            static::reindexField($indexField);
        }
    }
}

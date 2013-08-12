<?php

class FCom_Core_Model_Abstract extends BModel
{
    /**
     * Field name for serialized data
     *
     * Access using static::$_dataSerializedField to allow overrides
     *
     * @var string
     */
    static protected $_dataSerializedField = 'data_serialized';
    static protected $_dataField = 'data_custom';

    /**
     * Get custom data from serialized field
     *
     * Lazy `data` initialization from `data_serialized`
     * Works only for models with `data_serialized` field existing
     *
     * @param string $path slash separated path to the data within structured array
     * @return mixed
     */
    public function getData($path = null)
    {
        if (is_null($this->get('data'))) {
            $dataJson = $this->get(static::$_dataSerializedField);
            $this->set(static::$_dataField, $dataJson ? BUtil::fromJson($dataJson) : array());
        }
        $data = $this->get(static::$_dataField);
        if (is_null($path)) {
            return $data;
        }
        $pathArr = explode('/', $path);
        foreach ($pathArr as $k) {
            if (!isset($data[$k])) {
                return null;
            }
            $data = $data[$k];
        }
        return $data;
    }

    /**
     * Set custom data to serialized field
     *
     * Works only for models with `data_serialized` field existing
     *
     * @param string $path slash separated path to the data within structured array
     * @param $value mixed
     * @return FCom_Core_Model_Abstract
     */
    public function setData($path, $value = null)
    {
        if (is_array($path)) {
            foreach ($path as $p=>$v) {
                $this->setData($p, $v);
            }
            return $this;
        }
        $data = $this->getData();
        $pathArr = explode('/', $path);
        $last = sizeof($pathArr)-1;
        foreach ($pathArr as $i=>$k) {
            if ($i === $last) {
                $data[$k] = $value;
            } elseif (!isset($data[$k])) {
                $data[$k] = array();
            }
        }
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (($data = $this->get(static::$_dataField))) {
            $this->set(static::$_dataSerializedField, BUtil::toJson($data));
        }

        return true;
    }
}

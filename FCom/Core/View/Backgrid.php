<?php

class FCom_Core_View_Backgrid extends FCom_Core_View_Abstract
{
    protected function _applyPersonalization(&$config)
    {
        $gridId = !empty($config['personalize']['id']) ? $config['personalize']['id'] : $config['id'];

        // retrieve current personalization
        $pers = FCom_Admin_Model_User::i()->personalize();
        $pers = !empty($pers['grid'][$gridId]) ? $pers['grid'][$gridId] : array();
#var_dump($pers);
        $req = BRequest::i()->get();

        // prepare array to update personalization
        $personalize = array();
        foreach (array('p', 'ps', 's', 'sd', 'q') as $k) {
            if (!isset($pers[$k])) {
                $pers[$k] = null;
            }
            if (isset($req[$k]) && $pers[$k] !== $req[$k]) {
                $personalize[$k] = $req[$k];
            } elseif (isset($pers[$k])) {
                $config['state'][$k] = $pers[$k];
            }
        }

        // save personalization
        if (!empty($personalize)) {
            FCom_Admin_Model_User::i()->personalize(array('grid' => array($gridId => $personalize)));
        }

        // get columns personalization
        $persCols = array();
        $defPos = 0;
        foreach ($config['columns'] as $col) {
            if (!empty($col['name']) && !empty($pers['columns'][$col['name']])) {
                $col = BUtil::arrayMerge($col, $pers['columns'][$col['name']]);
            }
            if (empty($col['position'])) {
                $col['position'] = $defPos;
            }
            $defPos++;
            $persCols[] = $col;
        }
        usort($persCols, function($a, $b) { return $a['position'] - $b['position']; });
        $config['columns'] = $persCols;
    }

    public function getBackgridConfigJson()
    {
        $config = $this->grid['config'];
        $config['personalize_url'] = BApp::href('my_account/personalize');

        if (empty($config['id'])) {
            $config['id'] = BUtil::simplifyString($this->param('view_name'));
        }
        $config['container'] = '#'.$config['id'];

        $pos = 0;
        $columns = array();
        foreach ($config['columns'] as $k => $col) {
            if (!is_numeric($k)) {
                $col['name'] = $k;
            }
            if (empty($col['cell'])) {
                if (!empty($col['href'])) {
                    $col['cell'] = new BValue('FCom.Backgrid.HrefCell');
                }
            }

            if (!empty($col['cell'])) {
                switch ($col['cell']) {
                case 'date': case 'datetime': //TODO: locale specific display format
                    $col['cell'] = new BValue("Backgrid.Extension.MomentCell.extend({
                        modelFormat:'YYYY-MM-DD',
                        displayFormat: 'M/D/YYYY',
                        displayInUTC: false
                    })");
                    break;
                }
            }
            $columns[] = $col;
        }
        $config['columns'] = $columns;

        if (empty($config['toolbar'])) {
            $config['toolbar'] = array(
                'template' => '#backgrid-toolbar-template',
                'show_page_sizes' => true,
                'show_pages' => true,
                'show_filters' => true,
                'show_column_chooser' => true,
                'show_actions' => true,
            );
        }

        $this->_applyPersonalization($config);

        if (empty($config['state'])) {
            $config['state'] = BUtil::arrayMask(BRequest::i()->get(), 'p,ps,s,sd');
        }

        return BUtil::toJavaScript($config);
    }

    public function outputData()
    {
        $config = $this->grid['config'];
        //TODO: add _processFilters and processORM
        $data = $this->grid['orm']->paginate();

        foreach ($data['rows'] as &$row) {
            foreach ($config['columns'] as $col) {
                if (!empty($col['cell']) && !empty($col['name'])) {
                    $field = $col['name'];
                    switch ($col['cell']) {
                        case 'number':
                            $row->$field = floatval($row->$field);
                            break;
                        case 'integer':
                            $row->$field = intval($row->$field);
                            break;
                    }
                }
            }
        }
        unset($row);

        return $data;
    }

    protected function _processFilters($filter)
    {
        static $map = array(
            'eq'=>'=?','ne'=>'!=?','lt'=>'<?','le'=>'<=?','gt'=>'>?','ge'=>'>=?',
            'in'=>'IN (?)','ni'=>'NOT IN (?)',
        );
        $where = array();
        if (!empty($filter['rules'])) {
            foreach ($filter['rules'] as $r) {
                $data = $r['data'];
                if ($data==='') {
                    continue;
                }
                switch ($r['op']) {
                    case 'bw': $part = array($r['field'].' LIKE ?', $data.'%'); break;
                    case 'bn': $part = array($r['field'].' NOT LIKE ?', $data.'%'); break;
                    case 'ew': $part = array($r['field'].' LIKE ?', '%'.$data); break;
                    case 'en': $part = array($r['field'].' NOT LIKE ?', '%'.$data); break;
                    case 'cn': case 'nc': //$part = array($r['field'].' LIKE ?', '%'.$data.'%'); break;
                        $terms = explode(' ', $data);
                        $part = array('AND');
                        foreach ($terms as $term) {
                            $part[] = array($r['field'].' LIKE ?', '%'.$term.'%');
                        }
                        if ($r['op']==='nc') {
                            $part = array('NOT'=>$part);
                        }
                        break;
                    default: $part = array($r['field'].' '.$map[$r['op']], $data);
                }
                $where[$filter['groupOp']][] = $part;
            }
        }
        if (!empty($filter['groups'])) {
            foreach ($filter['groups'] as $g) {
                $where[$filter['groupOp']][] = $this->_processFilters($g);
            }
        }
        return $where;
    }

    public function processORM($orm, $method=null, $stateKey=null, $forceRequest=array())
    {
        $r = BRequest::i()->request();
        if (!empty($r['hash'])) {
            $r = (array)BUtil::fromJson(base64_decode($r['hash']));
        } elseif (!empty($r['filters'])) {
            $r['filters'] = BUtil::fromJson($r['filters']);
        }

        if ($stateKey) {
            $sess =& BSession::i()->dataToUpdate();
            $sess['grid_state'][$stateKey] = $r;
        }
        if ($forceRequest) {
            $r = array_replace_recursive($r, $forceRequest);
        }
//print_r($r); exit;
        //$r = array_replace_recursive($hash, $r);
#print_r($r); exit;
        if (!empty($r['filters'])) {
            $where = $this->_processFilters($r['filters']);
            $orm->where($where);
        }
        if (!is_null($method)) {
            //BEvents::i()->fire('FCom_Admin_View_Grid::processORM', array('orm'=>$orm));
            BEvents::i()->fire($method.'.orm', array('orm'=>$orm));
        }
        $data = $orm->jqGridData($r);
#print_r(BORM::get_last_query());
        $data['filters'] = !empty($r['filters']) ? $r['filters'] : null;
        //$data['hash'] = base64_encode(BUtil::toJson(BUtil::arrayMask($data, 'p,ps,s,sd,q,_search,filters')));
        $data['reloadGrid'] = !empty($r['hash']);
        if (!is_null($method)) {
            BEvents::i()->fire($method.'.data', array('data'=>&$data));
        }

        return $data;
    }

    public function export($orm, $class=null)
    {
        if ($class) {
            BEvents::i()->fire($class.'::action_grid_data.orm', array('orm'=>$orm));
        }
        $r = BRequest::i()->request();
        if (!empty($r['filters'])) {
            $r['filters'] = BUtil::fromJson($r['filters']);
        }
        $state = (array)BSession::i()->get('grid_state');
        if ($class && !empty($state[$class])) {
            $r = array_replace_recursive($state[$class], $r);
        }
        if (!empty($r['filters'])) {
            $where = $this->_processFilters($r['filters']);
            $orm->where($where);
        }
        if (!empty($r['s'])) {
            $orm->{'order_by_'.$r['sd']}($r['s']);
        }

        $cfg = BUtil::arrayMerge($this->default_config, $this->config);
        $cfg = $this->_processConfig($cfg);
        $columns = $cfg['grid']['colModel'];
        $headers = array();
        foreach ($columns as $i=>$col) {
            if (!empty($col['hidden'])) continue;
            $headers[] = !empty($col['label']) ? $col['label'] : $col['name'];
            if (!empty($col['editoptions']['value']) && is_string($col['editoptions']['value'])) {
                $options = explode(';', $col['editoptions']['value']);
                $col['editoptions']['value'] = array();
                foreach ($options as $o) {
                    list($k, $v) = explode(':', $o);
                    $col['editoptions']['value'][$k] = $v;
                }
                $columns[$i] = $col;
            }
        }
        $dir = BConfig::i()->get('fs/storage_dir').'/export';
        BUtil::ensureDir($dir);
        $filename = $dir.'/'.$cfg['grid']['id'].'.csv';
        $fp = fopen($filename, 'w');
        fputcsv($fp, $headers);
        $orm->iterate(function($row) use($columns, $fp) {
            if ($class) {
                //TODO: any faster solution?
                BEvents::i()->fire($class.'::action_grid_data.data_row', array('row'=>$row, 'columns'=>$columns));
            }
            $data = array();
            foreach ($columns as $col) {
                if (!empty($col['hidden'])) continue;
                $k = $col['name'];
                $val = !empty($row->$k) ? $row->$k : '';
                if (!empty($col['editoptions']['value'][$val])) {
                    $val = $col['editoptions']['value'][$val];
                }
                $data[] = $val;
            }
            fputcsv($fp, $data);
        });
        fclose($fp);
        BResponse::i()->sendFile($filename);
    }
}

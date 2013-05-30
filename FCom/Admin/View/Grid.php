<?php

class FCom_Admin_View_Grid extends FCom_Core_View_Abstract
{
    public function __construct()
    {
        $this->default_config = array(
            'grid' => array(
                'prmNames' => array(
                    'page' => 'p',
                    'rows' => 'ps',
                    'sort' => 's',
                    'order' => 'sd',
                ),
                'datatype'  => 'json',
                'jsonReader' =>  array(
                    'root' => 'rows',
                    'page'=>'p',
                    'total'=>'mp',
                    'records'=>'c',
                    'repeatitems'=>false,
                    'id'=>'id',
                ),
                'sortname'      => 'id',
                'sortorder'     => 'asc',
                'rowNum'        => 20,
                'rowList'       => array(10, 20, 50, 100, 200),
                'pager'         => true,
                'gridview'      => true,
                'viewrecords'   => true,
                'shrinkToFit'   => false,
                'forceFit'      => false,
                'autowidth'     => true,
                //'altRows'       => true,
                'width'         => '100%',
                'height'        => '100%',
                'multiselectWidth' => 30,
                'ignoreCase'    => true,
           ),
           'navGrid' => array('add'=>false, 'edit'=>false, 'del'=>false, 'refresh'=>true, 'prm'=>array(
                'search'=>array('multipleSearch'=>true, 'multipleGroup'=>true),
           )),
        );
    }

    protected function _processState($cfg)
    {
        if (empty($cfg['grid']['id'])) {
            return $cfg;
        }
        $state = BSession::i()->get('grid_state');
        if (!empty($state[$cfg['grid']['id']])) {
            $r = $state[$cfg['grid']['id']];

            if (!empty($r['p'])) $cfg['grid']['page'] = $r['p'];
            if (!empty($r['ps'])) $cfg['grid']['rowNum'] = $r['ps'];
            if (!empty($r['s'])) $cfg['grid']['sortname'] = $r['s'];
            if (!empty($r['sd'])) $cfg['grid']['sortorder'] = $r['sd'];
            if (!empty($r['filters'])) {
                $f = $r['filters'];
                $cfg['grid']['postData'] = array('_search'=>true, 'filters'=>BUtil::toJson($f));
                if (!empty($f['groupOp']) && $f['groupOp']==='AND' && !empty($f['rules'])) {
                    $cfg['grid']['search'] = true;
                    foreach ($f['rules'] as $rule) {
                        $idx = $rule['field'];
                        foreach ($cfg['grid']['columns'] as $colId=>&$col) {
                            if ($colId===$idx || !empty($col['index']) && $col['index']===$idx) {
                                $col['searchoptions']['defaultValue'] = $rule['data'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $cfg;
    }

    protected function _processPersonalization($cfg)
    {
        if (!empty($cfg['custom']['columnChooser'])) {
            $cfg[] = array('navButtonAdd',
                'caption' => '',
                'title' => 'Customize Columns',
                'buttonicon' => 'ui-icon-calculator',
                'onClickButton' => "function() { $('#{$cfg['grid']['id']}').jqGrid('columnChooser') }",
            );
        }
        if (!empty($cfg['custom']['personalize'])) {
            $gridId = is_string($cfg['custom']['personalize'])
                ? $cfg['custom']['personalize'] : $cfg['grid']['id'];
            $pers = FCom_Admin_Model_User::i()->personalize();
            if (!empty($pers['grid'][$gridId]['columns'])) {
                $persCols = $pers['grid'][$gridId]['columns'];
                foreach ($persCols as $k=>$c) {
                    if (empty($cfg['grid']['columns'][$k])) {
                        unset($persCols[$k]);
                    }
                }
                $cfg['grid']['columns'] = BUtil::arrayMerge($cfg['grid']['columns'], $persCols);
            }

            $url = BApp::href('/my_account/personalize');
            $cfg['grid']['resizeStop'] = "function(newwidth, index) {
                var cols = $('#{$cfg['grid']['id']}').jqGrid('getGridParam', 'colModel');
                $.post('{$url}', {'do':'grid.col.width', grid:'{$gridId}',
                    col:cols[index].name, width:newwidth
                });
            }";
            $cfg[] = array('navButtonAdd',
                'caption' => '',
                'title' => 'Customize Columns',
                'buttonicon' => 'ui-icon-calculator',
                'onClickButton' => "function() {
                    jQuery('#{$cfg['grid']['id']}').jqGrid('columnChooser', {
                        done:function(perm) {
                            console.log(perm, this.jqGrid('getGridParam', 'colModel'));
                            if (perm) {
                                this.jqGrid('remapColumns', perm, true);
                                $.post('{$url}', {'do':'grid.col.order', grid:'{$gridId}',
                                    cols:JSON.stringify(this.jqGrid('getGridParam', 'colModel'))
                                });
                            }
                        }
                    });
                }");
        }

        if (!empty($cfg['grid']['columns'])) {
            foreach ($cfg['grid']['columns'] as $colName=>$col) {
                if (empty($col['name'])) {
                    $col['name'] = $colName;
                }
                $cfg['grid']['colModel'][] = $col;
            }
            unset($cfg['grid']['columns']);
        }

        return $cfg;
    }

    protected function _processSubGridConfig($cfg)
    {
        if (!empty($cfg['subGrid']) && is_array($cfg['subGrid'])) {
            $cfg['grid']['gridview'] = false;
            $cfg['grid']['subGrid'] = true;
            $cfg['grid']['subGridOptions'] = array(
                'plusicon' => 'ui-icon-triangle-1-e',
                'minusicon' => 'ui-icon-triangle-1-s',
                'openicon' => 'ui-icon-arrowreturn-1-e',
                'reloadOnExpand' => false,
                'selectOnExpand' => false,
            );
            if (!empty($cfg['subGrid']['grid']['url'])) {
                $cfg['subGrid']['grid']['url'] = new BType('"'.$cfg['subGrid']['grid']['url'].'"+row_id');
            }
            $cfg['subGrid']['grid']['pager'] = new BType('pager_id');
            $cfg['subGrid']['isSubGrid'] = true;
            $jsBefore = !empty($cfg['subGrid']['custom']['jsBefore']) ? (string)$cfg['subGrid']['custom']['jsBefore'] : '';
            $jsAfter = '';
            if (!empty($cfg['subGrid']['grid']['editurl'])) {
                $jsAfter .= "subgrid.jqGrid('setGridParam', {editurl:subgrid.jqGrid('getGridParam', 'editurl')+row_id});";
            }
            $subGridView = static::i()->factory($cfg['grid']['id'].'_subgrid', array())->set('config', $cfg['subGrid']);
            $cfg['grid']['subGridRowExpanded'] = "function(subgrid_id, row_id) {
var subgrid_table_id = subgrid_id+'_t', pager_id = 'p_'+subgrid_table_id;
$('#'+subgrid_id).html('<table id=\"'+subgrid_table_id+'\" class=\"scroll\"></table><div id=\"'+pager_id+'\" class=\"scroll\"></div>');
var subgrid = $('#'+subgrid_table_id);
{$jsBefore}
{$subGridView->render()}
{$jsAfter}
            }";
            unset($cfg['subGrid']);
        }
        return $cfg;
    }

    protected function _processConfig($cfg)
    {
        $cfg = $this->_processState($cfg);
        $cfg = $this->_processPersonalization($cfg);
        $cfg = $this->_processSubGridConfig($cfg);

        $pos = 0;
        foreach ($cfg['grid']['colModel'] as &$col) {
            if (!empty($col['position'])) {
                $pos = $col['position'];
            } else {
                $col['position'] = ++$pos;
            }
            if (!empty($col['autocomplete']) && !isset($col['searchoptions']['dataInit'])) {
                $col['searchoptions']['dataInit'] = "function(el) { $(el).fcom_autocomplete({url:'{$col['autocomplete']}'}); }";
            }

            if (!empty($col['formatter'])) {
                switch ($col['formatter']) {
                case 'date':
                    //$col['editoptions'] = array();
                    $col['editrules']['date'] = true;
                    if (!isset($col['searchoptions']['sopt'])) $col['searchoptions']['sopt'] = array('eq','ne','lt','le','gt','ge');
                    if (!isset($col['searchoptions']['dataInit'])) $col['searchoptions']['dataInit'] = "function(el) {
$(el).datepicker({dateFormat:'yy-mm-dd'});
                    }";
                    if (!isset($col['formatoptions']['srcformat'])) $col['formatoptions']['srcformat'] = 'Y-m-d H:i:s';
                    if (!isset($col['formatoptions']['newformat'])) $col['formatoptions']['newformat'] = 'm/d/Y';
                    break;
                }
            }
            if (!empty($col['options'])) {
                $optArr = array();
                foreach ($col['options'] as $k=>$v) {
                    $optArr[] = $k.':'.$v;
                }
                $options = join(';', $optArr);
                if (!isset($col['formatter'])) $col['formatter'] = 'select';
                if (!isset($col['stype'])) $col['stype'] = 'select';
                if (!isset($col['edittype'])) $col['edittype'] = 'select';
                if (!isset($col['editoptions']['value'])) $col['editoptions']['value'] = $options;
                if (!isset($col['searchoptions']['value'])) $col['searchoptions']['value'] = ':All;'.$options;
                if (!isset($col['searchoptions']['defaultValue'])) $col['searchoptions']['defaultValue'] = '';
                unset($col['options']);
            }
        }
        unset($col);
        usort($cfg['grid']['colModel'], function($a, $b) {
            $i = $a['position']; $j = $b['position']; return $i<$j ? -1 : ($i>$j ? 1 : 0);
        });
        if (!empty($cfg['custom']['dblClickHref'])) {
            $cfg['grid']['ondblClickRow'] = "function(rowid, iRow, iCol, e) { location.href = '{$cfg['custom']['dblClickHref']}'+rowid; }";
        } elseif (!empty($cfg['navGrid']['edit'])) {
            $cfg['grid']['ondblClickRow'] = "function(rowid, iRow, iCol, e) { $(this).jqGrid('editGridRow', rowid); }";
        }
        if (!empty($cfg['custom']['autoresize'])) {
            if ($cfg['custom']['autoresize']===true) {
                $cfg[] = "$('html').css({overflow:'hidden'});
var top = $('#{$cfg['grid']['id']}').offset().top, pager = $('#pager-{$cfg['grid']['id']}').height();
$('#{$cfg['grid']['id']}').resizeWithWindow({x:true, dX:0, dY:top+pager});";
            } else {
                $cfg[] = "
$('#{$cfg['grid']['id']}').resizeWithWindow({initBy:'".addslashes($cfg['custom']['autoresize'])."', jqGrid:true});";
            }
        }
        if (!empty($cfg['custom']['export'])) {
            if (!empty($cfg['grid']['export_url'])) {
                $exportUrl =  $cfg['grid']['export_url'];
            } else {
                $exportUrl = BUtil::setUrlQuery($cfg['grid']['url'], array('export'=>'csv'));
            }
            $cfg[] = array('navButtonAdd',
                'caption' => '',
                'title' => 'Export to CSV',
                'buttonicon' => 'ui-icon-copy',
                'onClickButton' => "function() {
                    $('body').append('<iframe src=\"{$exportUrl}\" display=\"none\"></iframe');
                }");
        }
        /*
        if (!empty($cfg['custom']['hashState'])) {
            $cfg['grid']['serializeGridData'] = "function(data) {
    if (!$(this).data('ignore_hash')) {
        var hash = window.location.hash;
        if (hash) data = {hash:hash.replace(/^#/, '')};
        $(this).data('ignore_hash', true);
    }
    return data;
}";
            $cfg['grid']['loadComplete'] = "function(data) {
    window.location.hash = data.hash;
    if (data.reloadGrid) {
        $(this).jqGrid('triggerToolbar');
    }
}";
        }
        */
        unset($cfg['custom']);
/*
        foreach (array('add','edit','del') as $k) {
            if (!empty($cfg['navGrid'][$k])) {
                $cfg['navGrid']['prm'][$k] = array(
                    'afterSubmit'=>"function(response, postdata) {
console.log(response, postdata);
return [true, 'Testing error'];
                }");
            }
        }
*/
#echo "<pre>"; print_r($cfg); echo "</pre>"; exit;
        return $cfg;
    }

    /**
    * @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options
    */
    protected function _render()
    {
        $cfg = BUtil::arrayMerge($this->default_config, $this->config);
//echo "<pre>"; print_r($cfg); echo "</pre>";
        $cfg = $this->_processConfig($cfg);

        $isSubGrid = !empty($cfg['isSubGrid']);
        if (!$isSubGrid) {
            $id = $cfg['grid']['id'];
            $html = "<table id=\"{$id}\"></table>";
            if (!empty($cfg['grid']['pager'])) {
                $pagerId = true===$cfg['grid']['pager'] ? "pager-{$id}" : $cfg['grid']['pager'];
                $cfg['grid']['pager'] = $pagerId;
                $html .= "<div id=\"{$pagerId}\"></div>";
            }
            $html .= "<script>head(function() { jQuery('#{$id}')";
        } else {
            $quotedPagerId = "'#'+pager_id";
            $html = "jQuery('#'+subgrid_table_id)";
            unset($cfg['isSubGrid']);
        }

        $extraJS = array();
        $extraHTML = array();
        foreach ($cfg as $k=>$opt) {
            if ($k==='html') {
                $extraHTML[] = join('', (array)$opt);
                continue;
            } elseif ($k==='js' || is_string($opt)) {
                $extraJS[] = join('', (array)$opt);
                continue;
            }
            if (is_numeric($k)) {
                $k = array_shift($opt);
            }
            if (empty($quotedPagerId)) {
                if (!empty($opt['_pager'])) {
                    $localPagerId = $opt['_pager'];
                    unset($opt['_pager']);
                } else {
                    $localPagerId = $pagerId;
                }
                $quotedPagerId = "'#{$localPagerId}'";
            }
            if (is_array($opt) && !empty($opt['prm'])) {
                $prm = $opt['prm'];
                unset($opt['prm']);
            } else {
                $prm = array();
            }
            $optJS = BUtil::toJavaScript($opt);
            switch ($k) {
            case 'grid':
                $html .= ".jqGrid({$optJS})\n";
                break;

            case 'inlineNav':
            case 'navButtonAdd':
                $html .= ".jqGrid('{$k}', {$quotedPagerId}, {$optJS})\n";
                break;

            case 'navGrid':
                foreach (array('edit', 'add', 'del', 'search', 'view') as $t) {
                    if (!empty($prm[$t])) {
                        $prmJS[$t] = BUtil::toJavaScript($prm[$t]);
                    } else {
                        $prmJS[$t] = '{}';
                    }
                }
                $html .= ".jqGrid('navGrid', {$quotedPagerId}, {$optJS},"
                    ." {$prmJS['edit']}, {$prmJS['add']}, {$prmJS['del']},"
                    ." {$prmJS['search']}, {$prmJS['view']})\n";
                break;

            default:
                $html .= ".jqGrid('{$k}', {$optJS})\n";
            }
        }

        if (!$isSubGrid) {
            $html .= "; ".join("\n", $extraJS)." });</script>".join('', $extraHTML);
        }

        return $html;
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
                $where[$filter['groupOp']][] = $this->processFilters($g);
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
        //$data['hash'] = base64_encode(BUtil::toJson(BUtil::maskFields($data, 'p,ps,s,sd,q,_search,filters')));
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
<?php

class FCom_Admin_View_Abstract extends FCom_Core_View_Abstract
{
    public function addTab($id, $params)
    {
        $tabs = (array)$this->tabs;
        if (!isset($params['view'])) {
            $params['view'] = $this->tab_view_prefix.$id;
        }
        if (!isset($params['pos'])) {
            $pos = 0;
            foreach ($tabs as $tab) {
                $pos = max($pos, !empty($tab['pos']) ? $tab['pos'] : 0);
            }
            $params['pos'] = $pos+10;
        }
        $tabs[$id] = $params;
        $this->tabs = $tabs;
        return $this;
    }

    public function sortedTabs()
    {
        $tabs = (array)$this->tabs;
        uasort($tabs, function($a, $b) {
            return $a['pos']<$b['pos'] ? -1 : ($a['pos']>$b['pos'] ? 1 : 0);
        });
        #$this->tabs = $tabs;
        return $tabs;
    }
}
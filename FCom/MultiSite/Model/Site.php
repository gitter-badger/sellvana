<?php

class FCom_MultiSite_Model_Site extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_multisite_site';
    static protected $_mapCacheKey = 'FCom_MultiSite.domain_map';

    public function afterSave()
    {
        parent::afterSave();
        static::i()->createDomainMap();
    }

    static public function createDomainMap()
    {
        $map = array();
        $sites = (array)static::i()->orm()->find_many();
        foreach ($sites as $site) {
            $domains = explode("\n", $site->match_domains);
            foreach ($domains as $pattern) {
                if ($pattern[0]==='^') {
                    $regex = $pattern;
                } else {
                    $regex = str_replace('*', '.*', str_replace('.', '\\.', strtolower($pattern)));
                }
                $map[$regex] = $site->as_array();
            }
        }
        BCache::i()->save(static::$_mapCacheKey, $map);
        return $map;
    }

    static public function getDomainMap()
    {
        $map = BCache::i()->load(static::$_mapCacheKey);
        if (!$map) {
            $map = static::i()->createDomainMap();
        }
        return $map;
    }

    static public function findByDomain($domain=null)
    {
        if (is_null($domain)) {
            $domain = BRequest::i()->httpHost();
        }
        $domain = strtolower($domain);
        $map = static::i()->getDomainMap();
        $site = null;
        foreach ($map as $pattern=>$siteData) {
            if (preg_match('#'.$pattern.'#', $domain)) {
                $site = $siteData;
                break;
            }
        }
        return $siteData;
    }
}
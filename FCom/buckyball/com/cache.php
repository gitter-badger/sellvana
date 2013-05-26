<?php

class BCache extends BClass
{
    protected $_backends = array();
    protected $_backendStatus = array();
    protected $_defaultBackend;

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BCache
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function __construct()
    {
        foreach (array('File','Shmop','Apc','Memcached','Db') as $type) {
            $this->addBackend($type, 'BCache_Backend_'.$type);
        }
    }

    public function addBackend($type, $backend)
    {
        $type = strtolower($type);
        if (is_string($backend)) {
            if (!class_exists($backend)) {
                throw new BException('Invalid cache backend class name: '.$backend.' ('.$type.')');
            }
            $backend = $backend::i();
        }
        if (!is_object($backend)) {
            throw new BException('Invalid backend for type: '.$type);
        }
        if (!$backend instanceof BCache_Backend_Interface) {
            throw new BException('Invalid cache backend class interface: '.$type);
        }
        $this->_backends[$type] = $backend;
        return $this;
    }

    public function getBackend($type=null)
    {
        if (is_null($type)) { // type not specified
            if (empty($this->_defaultBackend)) { // no default backend yet
                $minRank = 10;
                $fastest = null;
                foreach ($this->_backends as $t => $backend) { // find fastest backend from available
                    $info = $backend->info();
                    if (empty($info['available'])) {
                        continue;
                    }
                    if ($info['rank'] < $minRank) {
                        $minRank = $info['rank'];
                        $fastest = $t;
                    }
                }
                $this->_defaultBackend = $fastest;
            }
            $type = $this->_defaultBackend;
        }
        $type = strtolower($type);
        $backend = $this->_backends[$type];
        if (empty($this->_backendStatus[$type])) {
            $info = $backend->info();
            if (empty($info['available'])) {
                throw new BException('Cache backend is not available: '.$type);
            }
            $config = (array)BConfig::i()->get('cache/'.$type);
            $backend->init($config);
            $this->_backendsStatus[$type] = true;
        }
        return $this->_backends[$type];
    }

    public function load($key)
    {
        return $this->getBackend()->load($key);
    }

    public function loadMany($pattern)
    {
        return $this->getBackend()->loadMany($pattern);
    }

    public function save($key, $data, $ttl=null)
    {
        return $this->getBackend()->save($key, $data, $ttl);
    }

    public function delete($key)
    {
        return $this->getBackend()->delete($key);
    }

    public function gc()
    {
        return $this->getBackend()->gc();
    }
}

interface BCache_Backend_Interface
{
    public function info();

    public function init($config = array());

    public function load($key);

    public function save($key, $data, $ttl = null);

    public function delete($key);

    public function loadMany($pattern);

    public function deleteMany($pattern);

    public function gc();
}

class BCache_Backend_File extends BClass implements BCache_Backend_Interface
{
    protected $_config = array();

    public function info()
    {
        return array('available' => true, 'rank' => 4);
    }

    public function init($config = array())
    {
        if (empty($config['dir'])) {
            $config['dir'] = BConfig::i()->get('fs/cache_dir');
        }
        if (!is_writable($config['dir'])) {
            $config['dir'] = sys_get_temp_dir().'/fulleron/'.md5(__DIR__).'/cache';
        }
        if (empty($config['default_ttl'])) {
            $config['default_ttl'] = 3600;
        }
        $this->_config = $config;
        return true;
    }

    protected function _filename($key)
    {
        $md5 = md5($key);
        return $this->_config['dir'].'/'.substr($md5, 0, 2).'/'.BUtil::simplifyString($key).'.'.substr($md5, 0, 10).'.dat';
    }

    public function load($key)
    {
        $filename = $this->_filename($key);
        if (!file_exists($filename)) {
            return null;
        }
        $fp = fopen($filename, 'r');
        $meta = @unserialize(fgets($fp, 1024));
        if (!$meta || $meta['ttl'] !== false && $meta['ts'] + $meta['ttl'] < time()) {
            fclose($fp);
            @unlink($filename);
            return null;
        }
        for ($data = ''; $chunk = fread($fp, 4096); $data .= $chunk);
        fclose($fp);
        return $data;

    }

    public function save($key, $data, $ttl = null)
    {
        $filename = $this->_filename($key);
        $dir = dirname($filename);
        BUtil::ensureDir($dir);
        $meta = array(
            'ts' => time(),
            'ttl' => !is_null($ttl) ? $ttl : $this->_config['default_ttl'],
            'key' => $key,
        );
        file_put_contents($filename, serialize($meta)."\n".serialize($data));
        return true;
    }

    public function delete($key)
    {
        $filename = $this->_filename($key);
        if (!file_exists($filename)) {
            return false;
        }
        @unlink($filename);
        return true;
    }

    /**
    * Load many items found by pattern
    *
    * @todo implement regexp pattern
    *
    * @param mixed $pattern
    */
    public function loadMany($pattern)
    {
        $files = glob($this->_config['dir'].'/*/*'.BUtil::simplifyString($pattern).'*');
        if (!$files) {
            return array();
        }
        $result = array();
        foreach ($files as $filename) {
            $fp = fopen($filename, 'r');
            $meta = unserialize(fgets($fp, 1024));
            if (!$meta || $meta['ttl'] !== false && $meta['ts'] + $meta['ttl'] < time()) {
                fclose($fp);
                @unlink($filename);
                continue;
            }
            if (strpos($meta['key'], $pattern)!==false) { // TODO: regexp search without iterating all files
                for ($data = ''; $chunk = fread($fp, 4096); $data .= $chunk);
                $result[$meta['key']] = $data;
            }
            fclose($fp);
        }
        return $result;
    }

    public function deleteMany($pattern)
    {
        if ($pattern===true || $pattern===false) { // true: remove ALL cache, false: remove EXPIRED cache
            $files = glob($this->_config['dir'].'/*/*');
        } else {
            $files = glob($this->_config['dir'].'/*/*'.BUtil::simplifyString($pattern).'*');
        }
        if (!$files) {
            return false;
        }
        $result = array();
        foreach ($files as $filename) {
            if ($pattern===true) {
                @unlink($filename);
                continue;
            }
            $fp = fopen($filename, 'r');
            $meta = unserialize(fgets($fp, 1024));
            fclose($fp);
            if (!$meta || $meta['ttl'] !== false && $meta['ts'] + $meta['ttl'] < time()
                || $pattern===false || strpos($meta['key'], $pattern)!==false // TODO: regexp search without iterating all files
            ) {
                @unlink($filename);
            }
        }
        return true;
    }

    public function gc()
    {
        $this->deleteMany(false);
        return true;
    }
}

class BCache_Backend_Apc extends BClass implements BCache_Backend_Interface
{
    protected $_config;

    public function info()
    {
        return array('available' => function_exists('apc_fetch'), 'rank' => 1);
    }

    public function init($config = array())
    {
        if (empty($config['prefix'])) {
            $config['prefix'] = substr(md5(__DIR__), 0, 16).'/';
        }
        if (empty($config['default_ttl'])) {
            $config['default_ttl'] = 3600;
        }
        $this->_config = $config;
        return true;
    }

    public function load($key)
    {
        $fullKey = $this->_config['prefix'].$key;
        if (!apc_exists($fullKey)) {
            return null;
        }
        return apc_fetch($fullKey);
    }

    public function save($key, $data, $ttl = null)
    {
        $ttl = !is_null($ttl) ? $ttl : $this->_config['default_ttl'];
        return apc_store($this->_config['prefix'].$key, $data, (int)$ttl);
    }

    public function delete($key)
    {
        return apc_delete($this->_config['prefix'].$key);
    }

    public function loadMany($pattern)
    {
        $items = new APCIterator('user');
        $prefix = $this->_config['prefix'];
        $result = array();
        foreach ($items as $item) {
            $key = $item['key'];
            if (strpos($key, $prefix)!==0) {
                continue;
            }
            if ($pattern===true || strpos($key, $pattern)!==false) {
                apc_delete($key);
            }
        }
        return $result;
    }

    public function deleteMany($pattern)
    {
        if ($pattern===false) {
            return false; // not implemented for APC, has internal expiration
        }
        $items = new APCIterator('user');
        $prefix = $this->_config['prefix'];
        foreach ($items as $item) {
            $key = $item['key'];
            if (strpos($key, $prefix)!==0) {
                continue;
            }
            if ($pattern===true || strpos($key, $pattern)!==false) {
                apc_delete($key);
            }
        }
        return true;
    }

    public function gc()
    {
        return true;
    }
}

class BCache_Backend_Memcached extends BClass implements BCache_Backend_Interface
{
    public function info()
    {
        return array('available' => class_exists('Memcached', false), 'rank' => 2);
    }

    public function init($config = array())
    {

    }

    public function load($key)
    {

    }

    public function save($key, $data, $ttl = null)
    {

    }

    public function delete($key)
    {

    }

    public function loadMany($pattern)
    {

    }

    public function deleteMany($pattern)
    {

    }

    public function gc()
    {

    }
}

class BCache_Backend_Db extends BClass implements BCache_Backend_Interface
{
    public function info()
    {
        return array('available' => false, 'rank' => 6); //TODO: figure out how to declare
    }

    public function init($config = array())
    {

    }

    public function load($key)
    {

    }

    public function save($key, $data, $ttl = null)
    {

    }

    public function delete($key)
    {

    }

    public function loadMany($pattern)
    {

    }

    public function deleteMany($pattern)
    {

    }

    public function gc()
    {

    }
}

class BCache_Backend_Shmop extends BClass implements BCache_Backend_Interface
{
    public function info()
    {
        return array('available' => false/*function_exists('shmop_open')*/, 'rank' => 1);
    }

    public function init($config = array())
    {

    }

    public function load($key)
    {

    }

    public function save($key, $data, $ttl = null)
    {

    }

    public function delete($key)
    {

    }

    public function loadMany($pattern)
    {

    }

    public function deleteMany($pattern)
    {

    }

    public function gc()
    {

    }
}


<?php

final class FCom_MarketClient_RemoteApi extends BClass
{
    public function getUrl($path = '', $params = array())
    {
        $url = 'http://127.0.0.1/fulleron/'; # 'https://fulleron.com/';
        $url .= ltrim($path, '/');
        if ($params) {
            $url = BUtil::setUrlQuery($url, $params);
        }
        return $url;
    }

    public function requestSiteNonce()
    {
        $siteKey = BConfig::i()->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/site/nonce', array(
            'admin_url' => BApp::href(),
            'site_key' => $siteKey,
        ));
        $response = BUtil::remoteHttp('GET', $url);
        return BUtil::fromJson($response);
    }

    public function requestSiteKey($nonce)
    {
        $url = $this->getUrl('api/v1/market/site/key', array(
            'nonce' => $nonce,
        ));
        $response = BUtil::remoteHttp('GET', $url);
        return BUtil::fromJson($response);
    }

    public function downloadPackage($moduleName)
    {
        $url =  $this->getUrl('api/v1/market/module/download', array('mod_name' => $moduleName));
        $data = BUtil::remoteHttp("GET", $fulleronUrl);
        $dir = BConfig::i()->get('fs/storage_dir') . '/dlc/packages';
        BUtil::ensureDir($dir);
        if (!is_writable($dir)) {
            return false;
        }
        $filename = $dir . '/' . $moduleName . '.zip';
        if (file_put_contents($filename, $data)) {
            return $filename;
        } else {
            return false;
        }
    }

    public function publishModule($data)
    {
        $siteKey = BConfig::i()->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/publish', array(
            'site_key' => $siteKey,
        ));
        $response = BUtil::remoteHttp('POST', $url, $data);
        return BUtil::fromJson($response);
    }

    public function uploadPackage($moduleName)
    {
        $mod = BModuleRegistry::i()->module($moduleName);
        $packageDir = BConfig::i()->get('fs/storage_dir') . '/marketclient/upload';
        BUtil::ensureDir($packageDir);
        $packageFilename = "{$packageDir}/{$moduleName}-{$mod->version}.zip";
        BUtil::zipCreateFromDir($packageFilename, $mod->root_dir);
        $url = $this->getUrl('api/v1/market/module/upload', array('mod_name' => $moduleName));
        $data = array(
            'package_zip' => '@'.$packageFilename,
        );
        $response = BUtil::remoteHttp('POST', $url, $data);
        return BUtil::fromJson($response);
    }
}

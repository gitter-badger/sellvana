<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 *
 * @project sellvana_core
 */
class FCom_ApiServer_Controller_V1_Import
    extends FCom_ApiServer_Controller_Abstract
{
    /**
     *
     */
    public function action_index()
    {
        /** @var FCom_Core_ImportExport $exporter */
        $exporter = $this->FCom_Core_ImportExport;
        $fromFile = fopen('php://input', 'r');
        $exporter->import($fromFile);
        $this->created(['Done']);
    }
}

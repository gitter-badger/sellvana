<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: Debug.php 835 2010-01-24 20:50:59Z kornel $
 * @link     http://phptal.org/
 */
/**
 * @package PHPTAL
 * @subpackage Php.attribute.phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_PHPTAL_Debug extends PHPTAL_Php_Attribute
{
    public function before(PHPTAL_Php_CodeWriter $codewriter)
    {
        $this->_oldMode = $codewriter->setDebug(true);
    }

    public function after(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setDebug($this->_oldMode);
    }

    private $_oldMode;
}



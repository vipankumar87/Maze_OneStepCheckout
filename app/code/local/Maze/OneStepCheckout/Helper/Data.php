<?php 

/**
 * Maze_OneStepChecout_Data helper class
 *
 * PHP version 5.4
 *
 * @category  Maze
 * @package   Maze_OneStepCheckout
 * @author    Vipan Kumar <vipan.webdeveloper@gmail.com>
 * @copyright 2014 Vipan Kumar
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version   GIT: <1.1>
 *
 */

/** Maze_OneStepChecout_Data
 *
 * @category Maze
 * @package  Maze_OneStepCheckout
 *
 * @author  Vipan Kumar <vipan.webdeveloper@gmail.com>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version Release: 1.1
 */
 
class Maze_OneStepChecout_Data extends Mage_Core_Helper_Abstract
{
    public function oneStepCheckoutEnabled()
    {
        return (bool)Mage::getStoreConfig('checkout/options/mage_onestep_enabled');
    }
}
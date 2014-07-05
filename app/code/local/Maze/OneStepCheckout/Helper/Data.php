<?php 


class Maze_OneStepChecout_Data extends Mage_Core_Helper_Abstract
{
    public function oneStepCheckoutEnabled()
    {
        return (bool)Mage::getStoreConfig('checkout/options/mage_onestep_enabled');
    }
}
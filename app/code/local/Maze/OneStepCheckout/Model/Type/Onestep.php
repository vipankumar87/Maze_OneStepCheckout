<?php

class Maze_OneStepCheckout_Model_Type_Onestep extends Mage_Checkout_Model_Type_Onepage
{
    private $_customerMailExistsMessage = '';
    
    public function __construct()
    {
        $this->_helper = Mage::helper('maze_onestep');
        $this->_customerMailExistsMessage = Mage::helper('checkout')->__('Email is already exists in database');
        $this->_checkoutSession = Mage::getSingleTon('checkout/session');
        $this->_customerSession = Mage::getSingleTon('customer/session');
    }
    
    /**
     * Initialize quote state to be valid for one page checkout
     * @return Mage_OneStepCheckout_Model_Type_Onestep
     * 
    */
    public function initCheckout()
    {
        $checkout           = $this->getCheckout();
        $customerSession    = $this->getCustomerSession();
        
        if($this->getQuote()->getIsMultiShipping())
        {
            $this->getQuote()->setIsMultiShipping(false);
            $this->getQuote()->save();
        }
        
        $customer = $customerSession->getCustomer();
        if($customer)
        {
            $this->getQuote()->assignCustomer($customer);

        }
        return $this;
    }
}
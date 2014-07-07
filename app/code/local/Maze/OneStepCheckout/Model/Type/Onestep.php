<?php

/**
 * Maze_OneStepCheckout Model Class
 *
 * PHP version 5.4
 *
 * @category  Maze
 * @package   Maze_OneStepCheckout
 * @author    Vipan Kumar <vipan.webdeveloper@gmail.com>
 * @copyright 2014 Vipan Kumar
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version   GIT: 1.1
 *
 */

/** Maze_OneStepCheckout_Helper_Data
 *
 * @category Maze
 * @package  Maze_OneStepCheckout
 *
 * @author  Vipan Kumar <vipan.webdeveloper@gmail.com>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version Release: 1.1
 */
 
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
        
        /**
         *  Commenting don't need in onestep checkout. 
         *  The step_data property of the checkout model. 
         *  This property is used to manage the step navigation within the OnePage checkout
         * 
         ***********************************************************************************
         * 
         *   if (is_array($checkout->getStepData())) {
         *      foreach ($checkout->getStepData() as $step=>$data) {
         *          if (!($step==='login' || $customerSession->isLoggedIn() && $step==='billing')) {
         *              $checkout->setStepData($step, 'allow', false);
         *          }
         *      }
         *   }
         ************************************************************************************
         *
         */
                
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
    
    /**
     * Save checkout shipping address
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
     public function saveShipping($data,  $customerAddressId)
     {
        if(empty($data)){
            return array('error' => -1, 'message'=> Mage::helper('checkout')->__('Invalid post data'));
        }
        
        $address     = $this->getQuote()->getShippingAddress();
        
        // @var $addressForm = Mage_Customer_Model_Form 
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());
        
        if(!empty($customerAddressId)){
            $customerAddress = Mage::getModel()->load($customerAddressId);
            
            if(!$customerAddress->getId())
            {
                return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid Address'));
            }
            
            if($customerAddress->getId())
            {
                if($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId())
                {
                    return array('error'=> -1, 'message' => Mage::helper('checkout')->__('Invalid Address'));
                }
                
                $address->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
                $addressForm->setEntity($address);
                
                $addressErrors  = $addressForm->validateData($address->getData());
                if ($addressErrors !== true) {
                    return array('error' => 1, 'message' => $addressErrors);
                }
            }
        }else{
            $addressForm->setEntity($address);
            // emulate request object
            $addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
            $addressErrors  = $addressForm->validateData($addressData);
            if ($addressErrors !== true) {
                return array('error' => 1, 'message' => $addressErrors);
            }
            $addressForm->compactData($addressData);
            // unset shipping address attributes which were not shown in form
            foreach ($addressForm->getAttributes() as $attribute) {
                if (!isset($data[$attribute->getAttributeCode()])) {
                    $address->setData($attribute->getAttributeCode(), NULL);
                }
            }

            $address->setCustomerAddressId(null);
            // Additional form data, not fetched by extractData (as it fetches only attributes)
            $address->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
            $address->setSameAsBilling(empty($data['same_as_billing']) ? 0 : 1);
        }

        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);

        if (($validateRes = $address->validate())!==true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        $this->getQuote()->collectTotals()->save();

        $this->getCheckout()
            ->setStepData('shipping', 'complete', true)
            ->setStepData('shipping_method', 'allow', true);

        return array();
     }
     
     /**
     * Save billing address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveBilling($data, $customerAddressId)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
        }

        $address = $this->getQuote()->getBillingAddress();
        
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());
        if (!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);

            //extra address check added
            if (!$customerAddress->getId()) {
                return array('error' => 1,
                    'message' => Mage::helper('checkout')->__('Customer Address is not valid.')
                );
            }
            if ($customerAddress->getId()) {
                if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error' => 1,
                        'message' => Mage::helper('checkout')->__('Customer Address is not valid.')
                    );
                }

                $address->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
                $addressForm->setEntity($address);
                $addressErrors  = $addressForm->validateData($address->getData());
                if ($addressErrors !== true) {
                    return array('error' => 1, 'message' => $addressErrors);
                }
            }
        } else {
            $addressForm->setEntity($address);
            // emulate request object
            $addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
            $addressErrors  = $addressForm->validateData($addressData);
            if ($addressErrors !== true) {
                return array('error' => 1, 'message' => array_values($addressErrors));
            }
            $addressForm->compactData($addressData);
            //unset billing address attributes which were not shown in form
            foreach ($addressForm->getAttributes() as $attribute) {
                if (!isset($data[$attribute->getAttributeCode()])) {
                    $address->setData($attribute->getAttributeCode(), NULL);
                }
            }
            $address->setCustomerAddressId(null);
            // Additional form data, not fetched by extractData (as it fetches only attributes)
            $address->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
        }

        // validate billing address
        if (($validateRes = $address->validate()) !== true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        $address->implodeStreetAddress();

        if (true !== ($result = $this->_validateCustomerData($data))) {
            return $result;
        }

        if (!$this->getQuote()->getCustomerId() && self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod()) {
            if ($this->_customerEmailExists($address->getEmail(), Mage::app()->getWebsite()->getId())) {
                return array('error' => 1, 'message' => $this->_customerEmailExistsMessage);
            }
        }

        if (!$this->getQuote()->isVirtual()) {
            /**
             * Billing address using otions
             */
            $usingCase = isset($data['use_for_shipping']) ? (int)$data['use_for_shipping'] : 0;

            switch ($usingCase) {
                case 0:
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shipping->setSameAsBilling(0);
                    break;
                case 1:
                    $billing = clone $address;
                    $billing->unsAddressId()->unsAddressType();
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shippingMethod = $shipping->getShippingMethod();

                    // Billing address properties that must be always copied to shipping address
                    $requiredBillingAttributes = array('customer_address_id');

                    // don't reset original shipping data, if it was not changed by customer
                    foreach ($shipping->getData() as $shippingKey => $shippingValue) {
                        if (!is_null($shippingValue) && !is_null($billing->getData($shippingKey))
                            && !isset($data[$shippingKey]) && !in_array($shippingKey, $requiredBillingAttributes)
                        ) {
                            $billing->unsetData($shippingKey);
                        }
                    }
                    $shipping->addData($billing->getData())
                        ->setSameAsBilling(1)
                        ->setSaveInAddressBook(0)
                        ->setShippingMethod($shippingMethod)
                        ->setCollectShippingRates(true);
                    $this->getCheckout()->setStepData('shipping', 'complete', true);
                    break;
            }
        }

        $this->getQuote()->collectTotals();
        $this->getQuote()->save();

        if (!$this->getQuote()->isVirtual() && $this->getCheckout()->getStepData('shipping', 'complete') == true) {
            //Recollect Shipping rates for shipping methods
            $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        }

        $this->getCheckout()
            ->setStepData('billing', 'allow', true)
            ->setStepData('billing', 'complete', true)
            ->setStepData('shipping', 'allow', true);

        return array();
    }
}
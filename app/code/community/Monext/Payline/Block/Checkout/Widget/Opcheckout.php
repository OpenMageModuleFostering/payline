<?php

class Monext_Payline_Block_Checkout_Widget_Opcheckout extends Mage_Checkout_Block_Onepage_Payment_Methods
{
    protected $_custom_methods;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('payline/checkout/onepage/widget-opcheckout-js.phtml');
   }

   /**
    *
    * @param Mage_Sales_Block_Order_History $block
    * @param Varien_Object $transport
    */
   public function addHtmlAsChild($block, $transport)
   {
       $transport->setHtml($transport->getHtml() . $this->_toHtml());
   }



   /**
    * Retrieve available payment methods
    *
    * @return array
    */
   public function getAllMethods()
   {
       $quote = $this->getQuote();
       $store = $quote ? $quote->getStoreId() : null;
       $methods = array();
       foreach ($this->helper('payment')->getStoreMethods($store, $quote) as $method) {
               $methods[] = $method;
       }
       return $methods;
   }




   public function getJsonAllMethods()
   {
       if (is_null($this->_custom_methods)) {
           $customMethods = array();
           $methods = $this->getAllMethods();
           if (!empty($methods)) {
               foreach ($methods as $_method) {
                   $_code = $_method->getCode();
                   if(stripos($_code,'payline')!==false) {
                       continue;
                   }
                   $html = preg_replace('/display:none;/', '', $this->getPaymentMethodFormHtml($_method));
                   $html = str_replace(array("\r\n","\r","\n"),"",$this->jsQuoteEscape($html));

                   //$html = $this->getPaymentMethodFormHtml($_method);
                   $customMethods[] = array('code'=>$_code,
                           'title'=>$this->escapeHtml($this->getMethodTitle($_method)),
                           'label'=>$this->getMethodLabelAfterHtml($_method),
                           'html' => ($html) ? $html : $this->getMethodTitle($_method)
                           );
               }
           }

           $this->_custom_methods = $customMethods;
       }
       return Mage::helper('core')->jsonEncode($this->_custom_methods);
   }

   public function getJsonCurrentMethods()
   {
       $currentMethods = array();
       $methods = $this->getMethods();
       if (!empty($methods)) {
           foreach ($methods as $_method) {
               $_code = $_method->getCode();
               if(stripos($_code,'payline')!==false) {
                   continue;
               } else {
                   $currentMethods[$_code] = $_code;
               }
           }
       }

       return Mage::helper('core')->jsonEncode($currentMethods);
   }

   public function getSaveUrl()
   {
       return Mage::getUrl('payline/index/cptWidgetCustom');
   }

   /**
    * Getter
    *
    * @return string
    */
   public function getCurrencyCode()
   {
       return $this->getQuote()->getBaseCurrencyCode();
   }

   /**
    * Getter
    *
    * @return float
    */
   public function getQuoteBaseGrandTotal()
   {
       return (float)$this->getQuote()->getBaseGrandTotal();
   }


}

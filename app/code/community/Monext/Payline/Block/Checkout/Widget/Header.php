<?php
class Monext_Payline_Block_Checkout_Widget_Header extends Mage_Core_Block_Text {


    public function getText()
    {
        $paylineSDK = Mage::helper('payline')->initPayline('CPT');

        $text = array();
        $text []= '<script src="' . $paylineSDK->getWidgetJavascriptUrl() .'"></script>';
        $text []= '<link rel="stylesheet" href="' . $paylineSDK->getWidgetCssUrl() .'">';

        return implode(PHP_EOL, $text);
    }
}
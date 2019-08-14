<?php

class Monext_Payline_Block_Cpt extends Mage_Payment_Block_Form
{

    /**
     * Payment methods
     * @var array
     */
    protected $_paymentMethods = null;

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('payline/Cpt.phtml');
        $redirectMsg = Mage::getStoreConfig('payment/PaylineCPT/redirect_message');
        $this->setRedirectMessage($redirectMsg);
        $this->setBannerSrc($this->getSkinUrl('images/payline-logo.png'));
    }

    /**
     * Return payment methods = primary contracts
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        if ($this->_paymentMethods === null) {
            $contracts = Mage::getModel('payline/contract')
                ->getCollection()
                ->addFilterStatus(true, Mage::app()->getStore()->getId());
            $contractList = array();
            foreach ($contracts as $contract) {
                $contractList[] = array('number' => $contract->getNumber(),
                    'type'   => $contract->getContractType(),
                    'name'   => $contract->getName());
            }
            $this->_paymentMethods = $contractList;
        }
        return $this->_paymentMethods;
    }

    /**
     * Return logo url depending on the type of card
     *
     * @param string $cardType
     * @return string
     */
    public function getLogoUrl($cardType)
    {
        switch ($cardType) {
            case 'CB' :
            case 'AMEX' :
            case 'VISA' :
            case 'MASTERCARD' :
            case 'SOFINCO' :
            case 'DINERS' :
            case 'AURORE' :
            case 'PASS' :
            case 'COFINOGA' :
            case 'PRINTEMPS' :
            case 'KANGOUROU' :
            case 'SURCOUF' :
            case 'CYRILLUS' :
            case 'FNAC' :
            case 'JCB' :
            case 'MAESTRO' :
            case 'MCVISA' :
            case 'ELV' :
            case 'MONEO' :
            case 'IDEAL' :
            case 'LEETCHI' :
            case 'MAXICHEQUE' :
            case 'NEOSURF' :
            case 'PAYFAIR' :
            case 'PAYSAFECARD' :
            case 'TICKETSURF' :
            case 'OKSHOPPING' :
            case 'MANDARINE' :
            case 'PAYPAL' :
            case 'CASINO' :
            case 'SWITCH' :
            case '1EURO.COM' :
            case 'WEXPAY' :
            case '3XCB' :
                return $this->getSkinUrl('images/payline_moyens_paiement/' . strtolower($cardType) . '.png');
            case 'CBPASS' :
                return $this->getSkinUrl('images/payline_moyens_paiement/passvisa.png');
            case 'INTERNET+' :
                return $this->getSkinUrl('images/payline_moyens_paiement/internetplus.png');
            case 'AMEX-ONE CLICK' :
                return $this->getSkinUrl('images/payline_moyens_paiement/amexoneclick.png');
            case 'SKRILL(MONEYBOOKERS)' :
                return $this->getSkinUrl('images/payline_moyens_paiement/skrill.png');
            default :
                return $this->getSkinUrl('images/payline_moyens_paiement/default.png');
        }
    }

}

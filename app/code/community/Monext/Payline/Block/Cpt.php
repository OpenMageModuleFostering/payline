<?php
class Monext_Payline_Block_Cpt extends Mage_Payment_Block_Form {
    protected function _construct() {
        parent::_construct();
        
        $this->setTemplate('payline/Cpt.phtml');
        $redirectMsg=Mage::getStoreConfig('payment/PaylineCPT/redirect_message');
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
		$contracts = Mage::getModel('payline/contract')
						->getCollection()
						->addFilterStatus(true,Mage::app()->getStore()->getId());	
		$contractList = array();
		foreach ($contracts as $contract) {
			$contractList[] = array('number' => $contract->getNumber(), 
									'type' => $contract->getContractType(),
									'name' => $contract->getName());
		}
		return $contractList;
	}
	
	/**
	 * Return logo url depending on the type of card
	 * 
	 * @param string $cardType
	 * @return string 
	 */
	public function getLogoUrl($cardType)
	{
		switch($cardType) {
			case 'CB' :
				return $this->getSkinUrl('images/payline_moyens_paiement/cb.png');
				break;
			case 'AMEX' :
				return $this->getSkinUrl('images/payline_moyens_paiement/amex.png');
				break;
			case 'VISA' :
				return $this->getSkinUrl('images/payline_moyens_paiement/visa.png');
				break;
			case 'MASTERCARD' :
				return $this->getSkinUrl('images/payline_moyens_paiement/mastercard.png');
				break;
			case 'SOFINCO' :
				return $this->getSkinUrl('images/payline_moyens_paiement/sofinco.png');
				break;
			case 'DINERS' :
				return $this->getSkinUrl('images/payline_moyens_paiement/diners.png');
				break;
			case 'AURORE' :
				return $this->getSkinUrl('images/payline_moyens_paiement/aurore.png');
				break;
			case 'PASS' :
				return $this->getSkinUrl('images/payline_moyens_paiement/pass.png');
				break;
			case 'CBPASS' :
				return $this->getSkinUrl('images/payline_moyens_paiement/passvisa.png');
				break;
			case 'COFINOGA' :
				return $this->getSkinUrl('images/payline_moyens_paiement/cofinoga.png');
				break;
			case 'PRINTEMPS' :
				return $this->getSkinUrl('images/payline_moyens_paiement/printemps.png');
				break;
			case 'KANGOUROU' :
				return $this->getSkinUrl('images/payline_moyens_paiement/kangourou.png');
				break;
			case 'SURCOUF' :
				return $this->getSkinUrl('images/payline_moyens_paiement/surcouf.png');
				break;
			case 'CYRILLUS' :
				return $this->getSkinUrl('images/payline_moyens_paiement/cyrillus.png');
				break;
			case 'FNAC' :
				return $this->getSkinUrl('images/payline_moyens_paiement/fnac.png');
				break;
			case 'JCB' :
				return $this->getSkinUrl('images/payline_moyens_paiement/jcb.png');
				break;
			case 'MAESTRO' :
				return $this->getSkinUrl('images/payline_moyens_paiement/maestro.png');
				break;
			case 'MCVISA' :
				return $this->getSkinUrl('images/payline_moyens_paiement/mcvisa.png');
				break;
			case 'ELV' :
				return $this->getSkinUrl('images/payline_moyens_paiement/elv.png');
				break;
			case 'MONEO' :
				return $this->getSkinUrl('images/payline_moyens_paiement/moneo.png');
				break;
			case 'IDEAL' :
				return $this->getSkinUrl('images/payline_moyens_paiement/ideal.png');
				break;
			case 'INTERNET+' :
				return $this->getSkinUrl('images/payline_moyens_paiement/internetplus.png');
				break;
			case 'LEETCHI' :
				return $this->getSkinUrl('images/payline_moyens_paiement/leetchi.png');
				break;
			case 'MAXICHEQUE' :
				return $this->getSkinUrl('images/payline_moyens_paiement/maxicheque.png');
				break;
			case 'NEOSURF' :
				return $this->getSkinUrl('images/payline_moyens_paiement/neosurf.png');
				break;
			case 'PAYFAIR' :
				return $this->getSkinUrl('images/payline_moyens_paiement/payfair.png');
				break;
			case 'PAYSAFECARD' :
				return $this->getSkinUrl('images/payline_moyens_paiement/paysafecard.png');
				break;
			case 'TICKETSURF' :
				return $this->getSkinUrl('images/payline_moyens_paiement/ticketsurf.png');
				break;
			case 'OKSHOPPING' :
				return $this->getSkinUrl('images/payline_moyens_paiement/okshopping.png');
				break;
			case 'MANDARINE' :
				return $this->getSkinUrl('images/payline_moyens_paiement/mandarine.png');
				break;
			case 'PAYPAL' :
				return $this->getSkinUrl('images/payline_moyens_paiement/paypal.png');
				break;
			case 'CASINO' :
				return $this->getSkinUrl('images/payline_moyens_paiement/casino.png');
				break;
			case 'SWITCH' :
				return $this->getSkinUrl('images/payline_moyens_paiement/switch.png');
				break;
			case 'AMEX-ONE CLICK' :
				return $this->getSkinUrl('images/payline_moyens_paiement/amexoneclick.png');
				break;
			case '1EURO.COM' :
				return $this->getSkinUrl('images/payline_moyens_paiement/1euro.png');
				break;
			case 'SKRILL(MONEYBOOKERS)' :
				return $this->getSkinUrl('images/payline_moyens_paiement/skrill.png');
				break;
			case 'WEXPAY' :
				return $this->getSkinUrl('images/payline_moyens_paiement/wexpay.png');
				break;
			case '3XCB' :
				return $this->getSkinUrl('images/payline_moyens_paiement/3xcb.png');
				break;
			default :
				return $this->getSkinUrl('images/payline_moyens_paiement/default.png');
		}
	}
}

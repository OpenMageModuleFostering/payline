<?php
class Monext_Payline_Block_Direct extends Mage_Payment_Block_Form
{
    protected $_canUseForMultishipping  = false;

    /**
     * Cc available types
     * @var array
     */
    protected $_ccAvailableTypes = null;

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('payline/Direct.phtml');
        $redirectMsg=Mage::getStoreConfig('payment/PaylineNX/redirect_message');
        $this->setRedirectMessage($redirectMsg);
        $this->setBannerSrc($this->getSkinUrl('images/monext/payline-logo.png'));
    }

    public function getCcAvailableTypes()
    {
        if ($this->_ccAvailableTypes === null) {
            $this->_ccAvailableTypes = Mage::helper('payline')->getCcContracts();
        }

        return $this->_ccAvailableTypes;
    }

    public function getCurrrentCcType()
    {
        $ccType = $this->getInfoData('cc_type');

        //TODO: If a card should be selected by default we have to choose one
        foreach ($this->getCcAvailableTypes() as $contract) {
            $ccType = $contract->getId();
            break;
        }

        return $ccType;
    }


    public function getTypeLogo($type)
    {
        return $this->getSkinUrl('images/monext/payline_moyens_paiement/' . strtolower($type) . '.png');
    }

    public function getSecureLogo()
    {
        return $this->getSkinUrl('images/monext/payline_moyens_paiement/default.png');
    }

    public function getSecureLegend()
    {
        return $this->__('secured with Payline');
    }

    public function getCcMonths()
    {
        $months = array();
        $months[0] =  Mage::helper('payline')->__('Month');
        $months['01'] = '01';
        $months['02'] = '02';
        $months['03'] = '03';
        $months['04'] = '04';
        $months['05'] = '05';
        $months['06'] = '06';
        $months['07'] = '07';
        $months['08'] = '08';
        $months['09'] = '09';
        $months['10'] = '10';
        $months['11'] = '11';
        $months['12'] = '12';
        return $months;
    }

    public function getCcYears()
    {
        $years = array();
        $today = getdate();
        $years[0] =  Mage::helper('payline')->__('Year');
        $index1 = substr($today['year'],2);

        $years[$index1] = $today['year'];
        $years[$index1+1] = $years[$index1]+1;
        $years[$index1+2] = $years[$index1]+2;
        $years[$index1+3] = $years[$index1]+3;
        $years[$index1 + 4] = $years[$index1] + 4;
        $years[$index1 + 5] = $years[$index1] + 5;
        return $years;
    }

    public function hasVerification()
    {
        return true;
    }

    public function getAjaxErrors()
    {

        if(!Mage::helper('payline')->isProduction()) {
            $errors=array(  '09101'=>'Accès non autorisé',
                            '09102'=>'Compte commerçant bloqué ou désactivé',
                            '02703'=>'Action non autorisée',
                            '02303'=>'Numéro de contrat invalide',
                            '02623'=>'Nombre d’essai maximal atteint',
                            '02624'=>'Carte expirée',
                            '02625'=>'Format du numéro de carte incorrect',
                            '02626'=>'Format de la date d’expiration incorrect ou date non fournie',
                            '02627'=>'Format du CVV incorrect ou CVV non fourni',
                            '02628'=>'Format de l’URL de retour incorrect',
                            '02631'=>'Delay exceeded'
                    );
        } else {
            $errors = array();
        }

        return Mage::helper('core')->jsonEncode($errors);
    }

    public function getTokenUrl()
    {
        return Mage::helper('payline')->initPayline('DIRECT')->getServletTokenUrl();
    }

    public function getTokenReturnURL()
    {
        return Mage::getUrl('payline/index/tokenReturn');
    }


    public function getCryptedKeys()
    {
        return Mage::helper('core')->jsonEncode(Mage::helper('payline')->getCryptedKeys());
    }


    public function getAccessKeyRef()
    {
        return Mage::helper('payline')->getWeb2TokenKey();
    }

    public function isWalletEnabled()
    {
        return Mage::getStoreConfig('payment/PaylineWALLET/active');
    }
}

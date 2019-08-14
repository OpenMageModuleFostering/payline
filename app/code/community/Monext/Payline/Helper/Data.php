<?php

class Monext_Payline_Helper_Data extends Mage_Core_Helper_Data
{

    const SECURITY_MODE                      = 'SSL';
    const CREATE_INVOICE_SHOP_RETURN         = 'return';
    const VERSION                            = 4;

    /**
     * Currency codes (ISO 4217) supported by Payline
     * @var array
     */
    protected $_supportedCurrencyCodes       = array(
        'ALL' => '8', // Lek
        'DZD' => '12', // Algerian Dinar
        'ARS' => '32', // Argentine Peso
        'AUD' => '36', // Australian Dollar
        'BSD' => '44', // Bahamian Dollar
        'BHD' => '48', // Bahraini Dinar
        'BDT' => '50', // Taka
        'AMD' => '51', // Armenian Dram
        'BBD' => '52', // Barbados Dollar
        'BMD' => '60', // Bermudian Dollar (customarily known as Bermuda Dollar)
        'BTN' => '64', // Ngultrum
        'BOB' => '68', // Boliviano
        'BWP' => '72', // Pula
        'BZD' => '84', // Belize Dollar
        'SBD' => '90', // Solomon Islands Dollar
        'BND' => '96', // Brunei Dollar
        'MMK' => '104', // Kyat
        'BIF' => '108', // Burundi Franc
        'KHR' => '116', // Riel
        'CAD' => '124', // Canadian Dollar
        'CVE' => '132', // Cape Verde Escudo
        'KYD' => '136', // Cayman Islands Dollar
        'LKR' => '144', // Sri Lanka Rupee
        'CLP' => '152', // Chilean Peso
        'CNY' => '156', // Yuan Renminbi
        'COP' => '170', // Colombian Peso
        'KMF' => '174', // Comoro Franc
        'CRC' => '188', // Costa Rican Colon
        'HRK' => '191', // Croatian Kuna
        'CUP' => '192', // Cuban Peso
        'CYP' => '196', // Cyprus Pound
        'CZK' => '203', // Czech Koruna
        'DKK' => '208', // Danish Krone
        'DOP' => '214', // Dominican Peso
        'SVC' => '222', // El Salvador Colon
        'ETB' => '230', // Ethiopian Birr
        'ERN' => '232', // Nakfa
        'EEK' => '233', // Kroon
        'FKP' => '238', // Falkland Islands Pound
        'FJD' => '242', // Fiji Dollar
        'DJF' => '262', // Djibouti Franc
        'GMD' => '270', // Dalasi
        'GHC' => '288', // Cedi
        'GIP' => '292', // Gibraltar Pound
        'GTQ' => '320', // Quetzal
        'GNF' => '324', // Guinea Franc
        'GYD' => '328', // Guyana Dollar
        'HTG' => '332', // Gourde
        'HNL' => '340', // Lempira
        'HKD' => '344', // Hong Kong Dollar
        'HUF' => '348', // Forint
        'ISK' => '352', // Iceland Krona
        'INR' => '356', // Indian Rupee
        'IDR' => '360', // Rupiah
        'IRR' => '364', // Iranian Rial
        'IQD' => '368', // Iraqi Dinar
        'ILS' => '376', // New Israeli Sheqel
        'JMD' => '388', // Jamaican Dollar
        'JPY' => '392', // Yen
        'KZT' => '398', // Tenge
        'JOD' => '400', // Jordanian Dinar
        'KES' => '404', // Kenyan Shilling
        'KPW' => '408', // North Korean Won
        'KRW' => '410', // Won
        'KWD' => '414', // Kuwaiti Dinar
        'KGS' => '417', // Som
        'LAK' => '418', // Kip
        'LBP' => '422', // Lebanese Pound
        'LSL' => '426', // Loti
        'LVL' => '428', // Latvian Lats
        'LRD' => '430', // Liberian Dollar
        'LYD' => '434', // Libyan Dinar
        'LTL' => '440', // Lithuanian Litas
        'MOP' => '446', // Pataca
        'MWK' => '454', // Kwacha
        'MYR' => '458', // Malaysian Ringgit
        'MVR' => '462', // Rufiyaa
        'MTL' => '470', // Maltese Lira
        'MRO' => '478', // Ouguiya
        'MUR' => '480', // Mauritius Rupee
        'MXN' => '484', // Mexican Peso
        'MNT' => '496', // Tugrik
        'MDL' => '498', // Moldovan Leu
        'MAD' => '504', // Moroccan Dirham
        'OMR' => '512', // Rial Omani
        'NAD' => '516', // Namibian Dollar
        'NPR' => '524', // Nepalese Rupee
        'ANG' => '532', // Netherlands Antillian Guilder
        'AWG' => '533', // Aruban Guilder
        'VUV' => '548', // Vatu
        'NZD' => '554', // New Zealand Dollar
        'NIO' => '558', // Cordoba Oro
        'NGN' => '566', // Naira
        'NOK' => '578', // Norwegian Krone
        'PKR' => '586', // Pakistan Rupee
        'PAB' => '590', // Balboa
        'PGK' => '598', // Kina
        'PYG' => '600', // Guarani
        'PEN' => '604', // Nuevo Sol
        'PHP' => '608', // Philippine Peso
        'GWP' => '624', // Guinea-Bissau Peso
        'QAR' => '634', // Qatari Rial
        'ROL' => '642', // Old Leu
        'RUB' => '643', // Russian Ruble
        'RWF' => '646', // Rwanda Franc
        'SHP' => '654', // Saint Helena Pound
        'STD' => '678', // Dobra
        'SAR' => '682', // Saudi Riyal
        'SCR' => '690', // Seychelles Rupee
        'SLL' => '694', // Leone
        'SGD' => '702', // Singapore Dollar
        'SKK' => '703', // Slovak Koruna
        'VND' => '704', // Dong
        'SIT' => '705', // Tolar
        'SOS' => '706', // Somali Shilling
        'ZAR' => '710', // Rand
        'ZWD' => '716', // Zimbabwe Dollar
        'SZL' => '748', // Lilangeni
        'SEK' => '752', // Swedish Krona
        'CHF' => '756', // Swiss Franc
        'SYP' => '760', // Syrian Pound
        'THB' => '764', // Baht
        'TOP' => '776', // Pa'anga
        'TTD' => '780', // Trinidad and Tobago Dollar
        'AED' => '784', // UAE Dirham
        'TND' => '788', // Tunisian Dinar
        'TMM' => '795', // Manat
        'UGX' => '800', // Uganda Shilling
        'MKD' => '807', // Denar
        'EGP' => '818', // Egyptian Pound
        'GBP' => '826', // Pound Sterling
        'TZS' => '834', // Tanzanian Shilling
        'USD' => '840', // US Dollar
        'UYU' => '858', // Peso Uruguayo
        'UZS' => '860', // Uzbekistan Sum
        'VEB' => '862', // Bolivar
        'WST' => '882', // Tala
        'YER' => '886', // Yemeni Rial
        'ZMK' => '894', // Kwacha
        'TWD' => '901', // New Taiwan Dollar
        'SDG' => '938', // Sudanese Dinar
        'UYI' => '940', // Uruguay Peso en Unidades Indexadas
        'RSD' => '941', // Serbian Dinar
        'MZN' => '943', // Metical
        'AZN' => '944', // Azerbaijanian Manat
        'RON' => '946', // New Leu
        'CHE' => '947', // WIR Euro
        'CHW' => '948', // WIR Franc
        'TRY' => '949', // New Turkish Lira
        'XAF' => '950', // CFA Franc BEAC
        'XCD' => '951', // East Caribbean Dollar
        'XOF' => '952', // CFA Franc BCEAO
        'XPF' => '953', // CFP Franc
        'XBA' => '955', // Bond Markets Units European Composite Unit (EURCO)
        'XBB' => '956', // European Monetary Unit (E.M.U.-6)
        'XBC' => '957', // European Unit of Account 9(E.U.A.-9)
        'XBD' => '958', // European Unit of Account 17(E.U.A.-17)
        'XAU' => '959', // Gold
        'XDR' => '960', // SDR
        'XAG' => '961', // Silver
        'XPT' => '962', // Platinum
        'XTS' => '963', // Codes specifically reserved for testing purposes
        'XPD' => '964', // Palladium
        'SRD' => '968', // Surinam Dollar
        'MGA' => '969', // Malagascy Ariary
        'COU' => '970', // Unidad de Valor Real
        'AFN' => '971', // Afghani
        'TJS' => '972', // Somoni
        'AOA' => '973', // Kwanza
        'BYR' => '974', // Belarussian Ruble
        'BGN' => '975', // Bulgarian Lev
        'CDF' => '976', // Franc Congolais
        'BAM' => '977', // Convertible Marks
        'EUR' => '978', // Euro
        'MXV' => '979', // Mexican Unidad de Inversion (UID)
        'UAH' => '980', // Hryvnia
        'GEL' => '981', // Lari
        'BOV' => '984', // Mvdol
        'PLN' => '985', // Zloty
        'BRL' => '986', // Brazilian Real
        'CLF' => '990', // Unidades de formento
        'USN' => '997', // (Next day)
        'USS' => '998', // (Same day)
        'XXX' => '999' // The codes assigned for transactions where no currency is involved
    );

    protected $_correspCCType           = array(
        'AMEX'   => 'AMEX', // American Express
        'CB'     => 'VISA', // Visa
        'MCVISA' => 'MASTERCARD' // MasterCard
    );

    protected $_availablePaymentMethods = array(
        'DIRECT', 'CPT', 'NX', 'WALLET'
    );
    
    public $merchantId                  = '';
    public $accessKey                   = '';
    public $proxyHost                   = '';
    public $proxyPort                   = '';
    public $proxyLogin                  = '';
    public $proxyPassword               = '';
    public $production                  = '';
    public $securityMode                = '';
    public $languageCode                = '';
    public $paymentAction               = '';
    public $paymentMode                 = '';
    public $paymentMethod               = '';
    public $cancelUrl                   = '';
    public $notificationUrl             = '';
    public $returnUrl                   = '';
    public $customPaymentTemplateUrl    = '';
    public $contractNumber              = '';
    public $contractNumberList          = '';
    public $secondaryContractNumberList = '';
    public $customPaymentPageCode       = '';
    public $paymentCurrency             = '';
    public $orderCurrency               = '';
    public $walletId                    = '';
    public $isNewWallet                 = false;
    public $primaryMaxfailRetry         = '';
    public $primaryCallTimeout          = '';
    public $secondaryMaxfailRetry       = '';
    public $secondaryCallTimeout        = '';
    public $switchBackTimer             = '';
    public $paylineWsSwitchEnable       = '';
    public $ini_file                    = '';
    public $paylineErrCode              = '';
    public $paylineErrToken             = '';
    public $primaryTokenPrefix          = '';
    public $secondaryTokenPrefix        = '';

    /**
     * Loaded payment contract types
     * @var array
     */
    protected $_paymentContractType = array();

    /**
     * Is the method code used by Payline?
     * @param string $methodCode Method code
     * @return bool
     */
    public function isPayline($methodCode)
    {
        return strpos(strtolower($methodCode), 'payline') !== false;
    }

    /**
     * Check whether specified currency code is supported
     * @param string $code
     * @return bool
     */
    private function isCurrencyCodeSupported($code)
    {
        return array_key_exists($code, $this->_supportedCurrencyCodes);
    }

    /**
     * Returns the numeric currency code of the chosen currency
     * @param string $currencyCode
     * @return string
     */
    public function getNumericCurrencyCode($alphaCurrencyCode)
    {

        if ($this->isCurrencyCodeSupported($alphaCurrencyCode)) {
            return $this->_supportedCurrencyCodes[$alphaCurrencyCode];
        } else {
            return '0000';
        }
    }

    protected function _hasToSentWalletId()
    {
        return (
            Mage::getStoreConfig('payment/PaylineWALLET/active')
            && (
                Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')
                || Mage::getStoreConfig('payment/PaylineNX/send_wallet_id')
                || Mage::getStoreConfig('payment/PaylineDirect/send_wallet_id')
            )
        );
    }

    /**
     * 
     * Test if we can create a new Wallet
     */
    public function canSubscribeWallet()
    {
        $automateSubscriptionEnable = Mage::getStoreConfig('payment/payline_common/automate_wallet_subscription');
        $customer                   = Mage::getSingleton('customer/session')->getCustomer();
        return $automateSubscriptionEnable && $customer->getWalletId() == '';
    }

    public function createWalletForCurrentCustomer($paylineSDK, $array)
    {
        if ($this->canSubscribeWallet()) {
            $array['contractNumber']     = $array['payment']['contractNumber'];
            $array['wallet']['walletId'] = Mage::getModel('payline/wallet')->generateWalletId();
            $walletResult                = $paylineSDK->createWallet($array);
            if (isset($walletResult['result']['code']) && $walletResult['result']['code'] == '02500') {
                try {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $customer->setWalletId($array['wallet']['walletId'])
                        ->setWalletContractNumber($array['contractNumber'])
                        ->save();
                } catch (Mage_Core_Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * 
     * Initialize a payline webservice for payment
     * @param string $paymentMethod (CPT, NX or DIRECT)
     * @param $_numericCurrencyCode If provided, will also initialize currency
     */
    public function initPayline($paymentMethod, $_numericCurrencyCode = null)
    {
        if (!in_array($paymentMethod, $this->_availablePaymentMethods)) {
            return false;
        }
        $this->paymentMethod = $paymentMethod;
        if ($_numericCurrencyCode) {
            $this->paymentCurrency = $_numericCurrencyCode;
            $this->orderCurrency   = $this->paymentCurrency;
        }
        $xmlConfigPath    = 'payment/Payline' . $paymentMethod;
        $commonConfigPath = 'payment/payline_common';
        $paylineFolder    = Mage::getBaseDir() . '/app/code/community/Monext/Payline/PaylinePHPKit/';
        $this->merchantId = Mage::getStoreConfig($commonConfigPath . '/merchant_id');
        $this->accessKey  = Mage::getStoreConfig($commonConfigPath . '/access_key');
        if (Mage::getStoreConfig($commonConfigPath . '/proxy_host') == '') {
            $this->proxyHost = null;
        } else {
            $this->proxyHost = Mage::getStoreConfig($commonConfigPath . '/proxy_host');
        }
        if (Mage::getStoreConfig($commonConfigPath . '/proxy_port') == '') {
            $this->proxyPort = null;
        } else {
            $this->proxyPort = Mage::getStoreConfig($commonConfigPath . '/proxy_port');
        }
        $this->proxyLogin    = Mage::getStoreConfig($commonConfigPath . '/proxy_login');
        $this->proxyPassword = Mage::getStoreConfig($commonConfigPath . '/proxy_password');
        $this->production    = Mage::getStoreConfig($commonConfigPath . '/production');
        $this->securityMode  = self::SECURITY_MODE;
        $this->languageCode  = Mage::getStoreConfig($commonConfigPath . '/language');

        //Wallet :
        //If wallet_id is sent & registered, Payline will offer a checkbox "use previous payment information"
        $customerSession   = Mage::getSingleton('customer/session');
        $hasToSendWalletId = Mage::getStoreConfig($xmlConfigPath . '/send_wallet_id');
        if ($hasToSendWalletId) {
            if ($customerSession->isLoggedIn()) {
                $customer = $customerSession->getCustomer();
                if ($walletId = $customer->getWalletId()) {
                    $this->walletId = $walletId;
                }
            }
        }
        //If wallet_id is sent & NOT registered, Payline will save the wallet
        // if isNewWallet walletId will be sent in privateData, so we'll be able to save it in notifyAction (if payment is OK)
        if (Mage::getStoreConfig('payment/PaylineWALLET/active') && Mage::getStoreConfig('payment/payline_common/automate_wallet_subscription')) {
            if ($customerSession->isLoggedIn()) {
                $customer = $customerSession->getCustomer();
                if (!$customer->getWalletId()) {
                    $this->walletId    = Mage::getModel('payline/wallet')->generateWalletId();
                    $this->isNewWallet = true;
                }
            }
        }

        if ($paymentMethod == 'NX') {
            $this->paymentMode        = 'NX';
            $this->paymentAction      = 101;
            $this->cancelUrl          = Mage::getUrl('payline/index/nxcancel');
            $this->notificationUrl    = Mage::getUrl('payline/index/nxnotif');
            $this->returnUrl          = Mage::getUrl('payline/index/nxreturn');
            $this->contractNumberList = $this->_prepareContractList(true, true);
        } elseif ($paymentMethod == 'CPT') {
            $this->paymentMode        = 'CPT';
            $this->paymentAction      = Mage::getStoreConfig($xmlConfigPath . '/payline_payment_action');
            $this->cancelUrl          = Mage::getUrl('payline/index/cptcancel');
            $this->notificationUrl    = Mage::getUrl('payline/index/cptnotif');
            $this->returnUrl          = Mage::getUrl('payline/index/cptreturn');
            $this->contractNumberList = $this->_prepareContractList(true, false);
        } elseif ($paymentMethod == 'WALLET') {//1 clic payment
            $this->paymentMode           = 'CPT';
            $this->paymentAction         = Mage::getStoreConfig($xmlConfigPath . '/payline_payment_action');
            $this->updatePersonalDetails = Mage::getStoreConfig($xmlConfigPath . '/update_personal_details');
            $this->updatePaymentDetails  = Mage::getStoreConfig($xmlConfigPath . '/update_payment_details');
            $this->contractNumberList    = $this->_prepareContractList(true, true);
        } else {//direct
            $this->paymentMode        = 'CPT';
            $this->paymentAction      = Mage::getStoreConfig($xmlConfigPath . '/payline_payment_action');
            $this->cancelUrl          = "";
            $this->notificationUrl    = "";
            $this->returnUrl          = "";
            $this->contractNumberList = $this->_prepareContractList(true, true);
        }
        $this->customPaymentTemplateUrl = Mage::getStoreConfig($xmlConfigPath . '/template_url');

        //primary and secondary contracts number
        $cNumber = explode(';', $this->contractNumberList);
        if (isset($cNumber[0])) {
            $this->contractNumber = $cNumber[0];
        }
        $this->secondaryContractNumberList = $this->_prepareContractList(false, false);

        // default template
        $this->customPaymentPageCode = Mage::getStoreConfig($xmlConfigPath . '/custom_payment_page_code');

        // "responsive" template
        $customTheme = $this->_checkUserAgentAgainstRegexps($xmlConfigPath . '/custom_payment_page_code_ua_regexp');
        if ($customTheme) {
            $this->customPaymentPageCode = $customTheme;
        }

        $this->primaryMaxfailRetry   = Mage::getStoreConfig($commonConfigPath . '/primary_max_fail_retry');
        $this->primaryCallTimeout    = Mage::getStoreConfig($commonConfigPath . '/primary_call_timeout');
        $this->primaryReplayTimer    = Mage::getStoreConfig($commonConfigPath . '/primary_replay_timer');
        $this->secondaryMaxfailRetry = Mage::getStoreConfig($commonConfigPath . '/secondary_max_fail_retry');
        $this->secondaryCallTimeout  = Mage::getStoreConfig($commonConfigPath . '/secondary_call_timeout');
        $this->secondaryReplayTimer  = Mage::getStoreConfig($commonConfigPath . '/secondary_replay_timer');

        $this->switchBackTimer = Mage::getStoreConfig($commonConfigPath . '/switch_back_timer');

        $this->paylineWsSwitchEnable = Mage::getStoreConfig($commonConfigPath . '/payline_ws_switch_enable');

        $this->ini_file = $paylineFolder . 'configuration/HighDefinition.ini';

        $this->paylineErrCode  = Mage::getStoreConfig($commonConfigPath . '/payline_err_code');
        $this->paylineErrToken = Mage::getStoreConfig($commonConfigPath . '/payline_err_token');

        $this->primaryTokenPrefix   = Mage::getStoreConfig($commonConfigPath . '/primary_token_prefix');
        $this->secondaryTokenPrefix = Mage::getStoreConfig($commonConfigPath . '/secondary_token_prefix');

        require_once($paylineFolder . 'lib/paylineSDK.php');
        $payline                           = new paylineSDK($this->merchantId, $this->accessKey, $this->proxyHost, $this->proxyPort, $this->proxyLogin, $this->proxyPassword, $this->production);
        $payline->returnURL                = $this->returnUrl;
        $payline->cancelURL                = $this->cancelUrl;
        $payline->notificationURL          = $this->notificationUrl;
        $payline->customPaymentTemplateURL = $this->customPaymentTemplateUrl;
        $payline->languageCode             = $this->languageCode;
        $payline->customPaymentPageCode    = $this->customPaymentPageCode;
        $payline->securityMode             = $this->securityMode;
        $payline->paymentAction            = $this->paymentAction;
        return $payline;
    }

    protected function _prepareContractList($primary = true, $filterCB = false)
    {
        $contractList = array();

        $currentStoreId = Mage::app()->getStore()->getId();

        $contracts = Mage::getModel('payline/contract')->getCollection();
        if ($primary) {
            $contracts->addFilterStatus(true, $currentStoreId);
        } else {
            $contracts->addFilterStatus(false, $currentStoreId);
        }

        if ($filterCB) {
            $contracts->addFieldToFilter('contract_type', array('in' => array('CB', 'AMEX', 'MCVISA')));
        }

        foreach ($contracts as $contract) {
            $contractList[] = $contract->getNumber();
        }

        return implode(';', $contractList);
    }

    /**
     * Translate a credit card type from Mage to Payline
     * @param string $MageCCType
     */
    public function transcoCCType($MageCCType)
    {
        return $this->_correspCCType[$MageCCType];
    }

    /**
     * Create the invoice when the customer is redirected to the shop
     */
    public function automateCreateInvoiceAtShopReturn($mode, $order)
    {
        $action           = Mage::getStoreConfig('payment/Payline' . $mode . '/payline_payment_action');
        $canCreateInvoice = Mage::getStoreConfig('payment/Payline' . $mode . '/automate_invoice_creation');
        if ($mode == 'NX') {
            $action = Monext_Payline_Model_Cpt::ACTION_AUTH_CAPTURE;
        }
        if ($canCreateInvoice == self::CREATE_INVOICE_SHOP_RETURN) {
            $this->createInvoice($action, $order);
        }
    }

    public function createInvoice($action, $order)
    {
        $invoice = $order->prepareInvoice();
        if ($action == Monext_Payline_Model_Cpt::ACTION_AUTH) {
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        } else {
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        }
        $invoice->register();
        $order->setIsInProcess(true);
        try {
            $transactionSave = Mage::getModel('core/resource_transaction');
            $transactionSave->addObject($order)
                ->addObject($invoice)
                ->save();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('payline/logger')->log('[automateCreateInvoiceAtShopReturn] '
                . $order->getIncrementId()
                . ' unable to save the invoice'
            );
        }
    }

    /**
     * 
     * Get the contract number from a transaction (needen if defautl contract number!= of the one trully used - fore instance in a web payment & card AMEX)
     * @param PaylineSDK $paylineSDK 
     * @param string $transactionId
     * @param string $orderRef
     */
    public function getTransactionContractNumber($paylineSDK, $transactionId, $orderRef)
    {
        $arrayTransDetails = array(
            'archiveSearch'      => '',
            'transactionId'      => $transactionId,
            'orderRef'           => $orderRef,
            'startDate'          => '',
            'endDate'            => '',
            'transactionHistory' => '',
            'version'            => Monext_Payline_Helper_Data::VERSION
        );
        $result            = $paylineSDK->getTransactionDetails($arrayTransDetails);
        if (is_string($result)) {
            Mage::helper('payline/logger')->log('[getTransactionContractNumber] ' . $result);
            return '';
        } elseif (isset($result['result']) && $result['result']['code'] != '0000' && $result['result']['code'] != '2500' && $result['result']['code'] != '04003') {
            //Back to default
            Mage::helper('payline/logger')->log('[getTransactionContractNumber] ' .
                'Error while retrieving transaction contract number for transactionId' . ' ' . $transactionId . ' and order ' . $orderRef . ' error : ' . $result['result']['shortMessage']);
            return '';
        } else {
            return $result['payment']['contractNumber'];
        }
    }

    public function doReauthorization($orderIncId)
    {
        // Is this method used?? @jacquesbh
        mail('jab@monsieurbiz.com', 'debug payline', mageDebugBacktrace(true));

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncId);
        if ($order->getId()) {
            $payment       = $order->getPayment();
            $paymentMethod = $payment->getMethod();
            if (stripos($paymentMethod, 'payline') !== false) {
                $mode       = explode('Payline', $paymentMethod);
                $array      = array();
                $paylineSDK = Mage::helper('payline')->initPayline($mode[1], Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode()));

                // PAYMENT
                $array['payment']['amount']         = round($order->getBaseGrandTotal() * 100);
                $array['payment']['currency']       = Mage::helper('payline')->getNumericCurrencyCode($order->getBaseCurrencyCode());
                $array['payment']['action']         = Monext_Payline_Model_Cpt::ACTION_AUTH;
                $array['payment']['mode']           = 'CPT';
                $array['payment']['contractNumber'] = $this->getTransactionContractNumber($paylineSDK, $payment->getCcTransId(), $orderIncId);
                // TRANSACTION INFO
                $array['transactionID']             = $payment->getCcTransId();
                // ORDER
                $array['order']['ref']              = substr($orderIncId, 0, 50);
                $array['order']['amount']           = $array['payment']['amount'];
                $array['order']['currency']         = $array['payment']['currency'];

                // PRIVATE DATA
                $privateData          = array();
                $privateData['key']   = "orderRef";
                $privateData['value'] = $orderIncId;
                $paylineSDK->setPrivate($privateData);
                try {
                    $response = $paylineSDK->doReAuthorization($array);
                    if (isset($response['result']) && isset($response['result']['code']) && $response['result']['code'] != '00000') {
                        $errorMessage = Mage::helper('payline')->__("PAYLINE - Capture error") . ": ";
                        $errorMessage .= isset($response['result']['longMessage']) ? $response['result']['longMessage'] : '';
                        $errorMessage .= isset($response['result']['code']) ? " (code " . $response['result']['code'] . ")<br/>" : '';
                        Mage::helper('payline/Logger')->log('[doReauthorization] [' . $orderIncId . '] ' . $errorMessage);
                        Mage::throwException($errorMessage);
                    } else {
                        $payment->setCcTransId($response['transaction']['id']);
                        $transaction = Mage::getModel('sales/order_payment_transaction');
                        $transaction->setOrder($order);
                        $transaction->setOrderPaymentObject($payment)
                            ->loadByTxnId($response['transaction']['id']);
                        $transaction->setTxnId($response['transaction']['id']);
                        $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                        $transaction->save();
                        $payment->save();
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        } else {
            Mage::throwException($this->__("The order #%s doesn't exist", $orderIncId));
        }
    }

    public function encodeString($string)
    {
        $string = iconv('UTF-8', "ASCII//TRANSLIT", $string);
        return $string;
    }

    public function setOrderStatus($order, $status)
    {
        $state = Mage::getModel('sales/order_status')->getCollection()
            ->joinStates()
            ->addFieldToFilter('state_table.status', $status)
            ->getFirstItem()
            ->getState();

        if (empty($state)) {
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
        }
        $order->setState($state, $status);
    }

    protected function _checkUserAgentAgainstRegexps($regexpsConfigPath)
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $configValueSerialized = Mage::getStoreConfig($regexpsConfigPath, Mage::app()->getStore());

        if (!$configValueSerialized) {
            return false;
        }

        $regexps = @unserialize($configValueSerialized);

        if (empty($regexps)) {
            return false;
        }

        return self::getPackageByUserAgent($regexps, $regexpsConfigPath);
    }

    public static function getPackageByUserAgent(array $rules)
    {
        foreach ($rules as $rule) {

            $regexp = '/' . trim($rule['regexp'], '/') . '/';

            if (@preg_match($regexp, $_SERVER['HTTP_USER_AGENT'])) {
                return $rule['value'];
            }
        }

        return false;
    }

    public function getPaymentContractType($methodCode, $selector)
    {
        if (empty($methodCode)) {
            return '';
        }

        if (!isset($this->_paymentContractType[$methodCode])) {
            $this->_paymentContractType[$methodCode] = array();
        }
        if (!isset($this->_paymentContractType[$methodCode][$selector])) {
            $name = '';
            switch ($methodCode) {
                case 'PaylineDIRECT':
                    $paymentContract = Mage::getModel('payline/contract')
                        ->load($selector);
                    $name = $paymentContract->getName();
                    break;

                default:
                    $paymentContract = Mage::getModel('payline/contract')
                        ->getCollection()
                        ->addFieldToFilter('number', $selector)
                        ->getFirstItem();
                    $name = $paymentContract->getContractType();
            }
            $this->_paymentContractType[$methodCode][$selector] = $name;
        } else {
            $name = $this->_paymentContractType[$methodCode][$selector];
        }
        return $name;
    }

    /**
     * @param $order Order object
     * @param $paymentMode Monext_Payline_Model_Cpt::ACTION_AUTH / ACTION_AUTH_CAPTURE
     * @param bool $isReAuth true if re-authorization, false if not
     */
    public function setOrderStatusAccordingToPaymentMode($order, $paymentMode, $isReAuth = false)
    {
        $paymentMethod       = $order->getPayment()->getMethod();
        $reauthOrderStatus   = Mage::getStoreConfig('payment/payline_common/reauthorized_order_status');
        $capturedOrderStatus = Mage::getStoreConfig('payment/payline_common/captured_order_status');
        $authOrderStatus     = Mage::getStoreConfig('payment/payline_common/authorized_order_status');

        /* Monext_Payline_Model_Cpt::ACTION_RE_AUTH stand for reauth and reauth+capture.
         * So set the exact transaction type in order to set the right order status. */
        if ($paymentMode == Monext_Payline_Model_Cpt::ACTION_RE_AUTH) {
            $reauthAction = Mage::getStoreConfig('payment/' . $paymentMethod . '/action_when_order_seven_day_old');
            $paymentMode  = $reauthAction;
        }
        $arrayReAuth = array(
            Monext_Payline_Model_Cpt::ACTION_AUTH         => $reauthOrderStatus,
            Monext_Payline_Model_Cpt::ACTION_AUTH_CAPTURE => $capturedOrderStatus,
            Monext_Payline_Model_Cpt::ACTION_CAPTURE      => $capturedOrderStatus,
        );
        $arrayAuth   = array(
            Monext_Payline_Model_Cpt::ACTION_AUTH         => $authOrderStatus,
            Monext_Payline_Model_Cpt::ACTION_AUTH_CAPTURE => $capturedOrderStatus,
            Monext_Payline_Model_Cpt::ACTION_CAPTURE      => $capturedOrderStatus
        );

        $errorTxt = '[Monext_Payline_Helper_Data#setOrderStatusAccordingToPaymentMode]'
                . ' ERROR payment ' . $paymentMode . ' action not found. Unable to set order status';
        if ($isReAuth) {
            if (isset($arrayReAuth[$paymentMode])) {
                $conf = $arrayReAuth[$paymentMode];
            } else {
                Mage::log($errorTxt);
                return;
            }
        } else {
            if (isset($arrayAuth[$paymentMode])) {
                $conf = $arrayAuth[$paymentMode];
            } else {
                Mage::log($errorTxt);
                return;
            }
        }
        $this->setOrderStatus($order, $conf);
    }

    /**
     * Check if transaction exist
     * @param array Can contain following keys : transaction_id, txn_id
     * @return false if transaction doesn't exist, otherwise return the transaction model
     */
    public function transactionExist($params)
    {
        if (isset($params['txn_id'])) {
            $transaction = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addFieldToFilter('txn_id', $params['txn_id'])
            ->getFirstItem();
        } elseif (isset($params['transaction_id'])) {
            $transaction = Mage::getModel('sales/order_payment_transaction')->load($params['transaction_id']);
        }
        if ($transaction->getId()) {
            return $transaction;
        }
        return false;
    }

    /**
     * Return all contract with is_included_wallet_list db columns set to true.
     * Filtered by store_id and website_id
     * @param $store_id {int} can be null
     * @param $website_id {int} can be null
     * @return collection of matched contracts
     */
    public function getContractWalletList($store_id = null, $website_id = null, $isDefaultLevel = false)
    {
        if ($isDefaultLevel) {
            $collection = Mage::getModel('payline/contract')->getCollection()->addFieldToFilter('is_included_wallet_list', 1);
        } else {
            $collection = Mage::getModel('payline/contract')->getCollection();
            if (isset($store_id)) {
                $collection->addStoreFilter($store_id);
            }
            if (isset($website_id)) {
                $collection->addWebsiteFilter($website_id);
            }
            $collection->getSelect()->where(
                new Zend_Db_Expr('IFNULL(status.is_included_wallet_list,main_table.is_included_wallet_list)') . '=1');
        }
        return $collection;
    }

    /**
     * Build a contract number wallet array used by doWebPayment & createWebWallet
     * @return array An array of contracts number or an null if not found
     */
    public function buildContractNumberWalletList()
    {
        $storeId            = Mage::app()->getStore()->getStoreId();
        $contractCollection = $this->getContractWalletList($storeId);
        $contractsNumber    = array();
        foreach ($contractCollection as $contract) {
            $contractsNumber[] = $contract['number'];
        }
        return sizeof($contractsNumber) > 0 ? $contractsNumber : null;
    }

}

// end class

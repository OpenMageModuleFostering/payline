<?php

class Monext_Payline_Helper_Widget extends Monext_Payline_Helper_Data
{

    protected $_token;


    /**
     * Order increment ID getter (either real from order or a reserved from quote)
     *
     * @return string
     */
    protected function _getReservedOrderId($quote)
    {
        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId()->save();
        }
        return $quote->getReservedOrderId();
    }

    public function getDataToken()
    {
        if (is_null($this->_token)) {
            $this->_token = false;
            try {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $orderId = $this->_getReservedOrderId($quote);
                $quote->setRealOrderId($orderId);

                $array = Mage::helper('payline/payment')->initWithQuote($quote);
                $array['version'] = Monext_Payline_Helper_Data::VERSION;
                $array['payment']['action'] = Mage::getStoreConfig('payment/PaylineCPT/payline_payment_action');
                $array['payment']['mode'] = 'CPT';
                $array['payment']['contractNumber'] = $this->getDefaultContractNumberForWidget();
                $array['contracts'] = $this->getContractsForWidget(true);
                $array['secondContracts'] = $this->getContractsForWidget(false);
                if(empty($array['secondContracts'])) {
                    $array['secondContracts'] = array('');
                }


                $paylineSDK = $this->initPayline('CPT', $array['payment']['currency']);
                $paylineSDK->returnURL          = Mage::getUrl('payline/index/cptReturnWidget');
                $paylineSDK->cancelURL          = $paylineSDK->returnURL;
                $paylineSDK->notificationURL    = $paylineSDK->returnURL;


                // WALLET
                // ADD CONTRACT WALLET ARRAY TO $array
                $helperPayline = Mage::helper('payline');
                $array['walletContracts'] = $helperPayline->buildContractNumberWalletList();

                if (Mage::getStoreConfig('payment/PaylineCPT/send_wallet_id')) {

                    if (! isset($array['buyer']['walletId'])) {
                        if (isset($this->walletId)) {
                            $array['buyer']['walletId'] = $this->walletId;
                        }
                    }

                    $expiredWalletId = false;
                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                        $customer = Mage::getSingleton('customer/session')->getCustomer();
                        if ($customer->getWalletId() && ! Mage::getModel('payline/wallet')->checkExpirationDate()) {
                            $expiredWalletId = true;
                        }
                    }

                    if ($expiredWalletId) {
                        $this->walletId = null;
                    }

                    if ($helperPayline->canSubscribeWallet()) {
                        // If the wallet is new (registered during payment), we must
                        // save it in the private data since it's not sent back by
                        // default
                        if ($this->isNewWallet) {
                            if ($this->walletId) {
                                $paylineSDK->setPrivate(array('key' => 'newWalletId',
                                                              'value' => $this->walletId));
                            }
                        }
                    }
                }

                $response = $paylineSDK->doWebPayment($array);
                if(isset($response) and $response['result']['code'] == '00000' and !empty($response['token'])){
                    $this->_token =  $response['token'];
                    Mage::getModel('payline/token')
                    ->setOrderId($quote->getRealOrderId())
                    ->setToken($this->_token)
                    ->setDateCreate(time())
                    ->save();
                }

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return $this->_token;
    }

    public function getDataTemplate()
    {
        return $this->getCptConfigTemplate();
    }

} // end class

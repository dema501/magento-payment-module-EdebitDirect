<?php
/**
 *
 * @category   Mage
 * @package    Liftmode_EdebitDirect
 * @copyright  Copyright (c)  Dmitry Bashlov, contributors.
 */

class Liftmode_EdebitDirect_Model_Method_EdebitDirect extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_METHOD_EDEBITDIRECT_CODE = 'edebitdirect';

    protected $_code = self::PAYMENT_METHOD_EDEBITDIRECT_CODE;

    protected $_formBlockType = 'edebitDirect/form_edebitDirect';
    protected $_infoBlockType = 'edebitDirect/info_edebitDirect';

    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_isInitializeNeeded          = false;
    protected $_canVoid                     = true;


    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper($this->_code)->__('Invalid amount for authorization.'));
        }

        $payment->setAmount($amount);

        $data = $this->_doSale($payment);

        $payment->setTransactionId($data['TransactionId'])
                ->setIsTransactionClosed(0);

        return $this;
    }


    /**
     * Check void availability
     *
     * @param   Varien_Object $invoicePayment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        return $this->_canVoid;
    }


    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper($this->_code)->__('Invalid amount for authorization.'));
        }

        $payment->setAmount($amount);

        $data = $this->_doSale($payment);

        $payment->setTransactionId($data['TransactionId'])
                ->setIsTransactionClosed(0);

        return $this;
    }


    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }


    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        $orderTransactionId = $this->_getParentTransactionId($payment);

        if (!$orderTransactionId) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        $data = $this->_doValidate(...$this->_doDelete($orderTransactionId));

        $payment->setTransactionId($orderTransactionId)
                ->setIsTransactionClosed(1);

        return $this;
    }


    /**
     * Parent transaction id getter
     *
     * @param Varien_Object $payment
     * @return string
     */
    public function _getParentTransactionId(Varien_Object $payment)
    {
        return $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
    }


    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Liftmode_EdebitDirect_Model_EdebitDirect
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $info->setRoutingNumber($data->getRoutingNumber())
             ->setAccountNumber($data->getAccountNumber());

        return $this;
    }


    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl($id = "")
    {
        if (!empty($id)) {
            $id .= '/';
        }

        return ($this->getConfigData('apitest')) ? 'https://dev.edebitdirect.com/app/api/v1/check/' . $id  : 'https://api.edebitdirect.com/app/api/v1/check/' . $id ;
    }


    public function log($data)
    {
        Mage::log($data, null, 'EdebitDirect.log');
    }


    private function _sanitizeData($data) {
        if (is_string($data)) {
            return preg_replace('/"number":\s*"[^"]*([^"]{4})"/i', '"number":"***$1"', preg_replace('/"ccv":\s*"([^"]*)"/i', '"ccv":"***"', $data));
        }

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    return $this->_sanitizeData($v);
                } else {
                    if (in_array($k, array('number', 'CardNumber')) {
                        $data[$k] = "***" . substr($data[$k], -4);
                    }

                    if (in_array($k, array('cvv', 'CardCVV')) {
                        $data[$k] = "***";
                    }
                }
            }
        }


        return $data;
    }


    private function _doSale(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $data = array(
            "amount"          => (float) $payment->getAmount(), // Yes Decimal Total dollar amount with up to 2 decimal places.
            "account_number"  => strval($payment->getAccountNumber()), // Yes String Bank account number. This will be validated if the Bank Account Verification feature has been enabled on your account.
            "routing_number"  => strval($payment->getRoutingNumber()), // Yes String Bank routing number. This will be validated.
            "check_number"    => (int) substr($order->getIncrementId(), -7), // Yes Integer Check number
            "customer_name"   => strval($billing->getFirstname()) . ' ' . strval($billing->getLastname()), // Yes String Account holder's first and last name
            "customer_street" => substr(strval($billing->getStreet(1)), 0, 49), // Yes String The street portion of the mailing address associated with the customer's checking account. Include any apartment number or mail codes here. Any line breaks will be stripped out.
            "customer_city"   => strval($billing->getCity()), // Yes String The city portion of the mailing address associated with the customer's checking
            "customer_state"  => strval($billing->getRegionCode()),// Yes String The state portion of the mailing address associated with the customer's checking account. It must be a valid US state or territory
            "customer_zip"    => strval($billing->getPostcode()), // Yes String The zip code portion of the mailing address associated with the customer's checking account. Accepted formats: XXXXX,  XXXXX-XXXX
            "customer_phone"  => substr(str_replace(array(' ', '(', ')', '+', '-'), '', strval($billing->getTelephone())), -10), // Yes String Customer's phone number
            "customer_email"  => strval($order->getCustomerEmail()), // Yes String Customer's email address. Must be a valid address. Upon processing of the draft an email will be sent to this address.
            "memo"            => 'Order ' . $order->getIncrementId() . ' at ' . Mage::app()->getStore()->getFrontendName() . '. Thank you.', // No String A memo to include on the draft
        );


        // prepare to request
        $jsonData = json_encode($data);

        return $this->_doValidate(...$this->_doPost($jsonData), ...[$jsonData]);
    }


    private function _doValidate($resCode, $data = [], $postData)
    {
        if ((int) substr($resCode, 0, 1) !== 2) {
            $message = "";

            foreach ($data['check'] as $field => $errors) {
                $message .= sprintf("\r\nthe issue is in %s field\r\n", $field);

                foreach ($errors as $errcode => $errval) {
                    $message .= sprintf(" - %s\r\n", $errval);
                }
            }

            $this->log(array('try to doValidate', 'httpStatusCode' => $resCode, 'RespJson' => $data, 'ReqJson' => $postData));

            if (Mage::getStoreConfig('slack/general/enable_notification')) {
                $notificationModel   = Mage::getSingleton('mhauri_slack/notification');
                $notificationModel->setMessage(
                    Mage::helper($this->_code)->__("*EdebitDirect payment failed with data:*\nEdebitDirect response ```%s``````%s```\n\nData sent ```%s```", $resCode, json_encode($data), $this->_sanitizeData($postData))
                )->send(array('icon_emoji' => ':cop::skin-tone-5:'));
            }

            Mage::throwException(Mage::helper($this->_code)->__("Error during process payment: response code: %s %s", $resCode, $message));
        }

        return $data;
    }

    private function _doRequest($url, $extReqHeaders = array(), $extReqOpts = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $reqHeaders = array(
          'Content-Type: application/json',
          'Cache-Control: no-cache',
          'Authorization: apikey ' . Mage::helper('core')->decrypt($this->getConfigData('login')) . ':'. Mage::helper('core')->decrypt($this->getConfigData('trans_key'))
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($reqHeaders, $extReqHeaders));

        foreach ($extReqOpts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $resp = curl_exec($ch);

        list ($respHeaders, $body) = explode("\r\n\r\n", $resp, 2);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $errCode = curl_errno($ch);
        $errMessage = curl_error($ch);

        curl_close($ch);

        if (!empty($body)) {
            $body = json_decode($body, true);
        }

        foreach (explode("\r\n", $respHeaders) as $hdr) {
            if (preg_match("!Location: http.*\/(.*)\/!", $hdr, $matches)) {
                $body['TransactionId'] = $matches[1];
            }
        }

        if ($errCode || $errMessage) {
            $this->log(array('doRequest', 'url' => $url, 'httpRespCode' => $httpCode, 'httpRespHeaders' => $respHeaders, 'httpRespBody' => $body, 'httpReqHeaders' => array_merge($reqHeaders, $extReqHeaders), 'httpReqExtraOptions' => $extReqOpts, 'errCode' => $errCode, 'errMessage' => $errMessage));


            if (Mage::getStoreConfig('slack/general/enable_notification')) {
                $notificationModel   = Mage::getSingleton('mhauri_slack/notification');
                $notificationModel->setMessage(
                    Mage::helper($this->_code)->__("*EdebitDirect payment failed with data:*\nEdebitDirect response ```%s %s```\n\nData sent ```%s```", $errCode, $errMessage, $this->_sanitizeData(!empty($extReqOpts[CURLOPT_POSTFIELDS]) ? $extReqOpts[CURLOPT_POSTFIELDS] : ''))
                )->send(array('icon_emoji' => ':cop::skin-tone-5:'));
            }

            Mage::throwException(Mage::helper($this->_code)->__("Error during process payment: response code: %s %s", $httpCode, $errMessage));
        }

        return array($httpCode, $body);
    }

    private function _doPost($query)
    {
        return $this->_doRequest($this->getURL(), array(
            'Content-Length: ' . strlen($query),
        ), array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
        ));
    }

    private function _doDelete($id)
    {
        return $this->_doRequest($this->getURL($id), array(
        ), array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ));
    }

    public function _doGet($id)
    {
        return $this->_doRequest($this->getURL($id), array(
        ), array(
            CURLOPT_RETURNTRANSFER => true,
        ));
    }
}

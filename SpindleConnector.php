<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Spindle;

/**
 * Connects to Spindle for billing purposes. Is supposed to provide PHP with a
 * a way to connect to the service and make calls much like the ones exampled
 * on http://wiki.spindlehq.com/display/API/API+Call+List
 *
 * @author Rob Little <rlittle@spindle.com>
 * @copyright (c) 2014, Spindle Inc
 * 
 */
final class SpindleConnector
{
  
    /**
     * Your user settings provided by Spindle
     */
    protected $_PRIVATE_KEY = '';
    protected $_CID = '';
    protected $_SID = '';
    protected $_USERNAME = '';
    protected $_PASSWORD = '';
 
    
    /** DO NOT EDIT BELOW THIS LINE */
    protected $_TEST_API_BASE_URL = 'https://integration.spindleapi.com/1/';
    protected $_API_BASE_URL = 'https://integration.spindleapi.com/1/';
    protected $_MCRYPT_ENCODING = MCRYPT_RIJNDAEL_128;
    protected $_MCRYPT_MODE = MCRYPT_MODE_CBC;
    protected $_API_FUNCTION_DOMAIN = array();
    
    /** Populated at run time with values */
    protected $_SESSION_ID = '';
   
   /**
    * Constructor
    * @param string $pk The PrivateKey provided by Spindle
    * @param string $cid The CID [explain] provided by Spindle
    * @param string $sid The SID [explain] provided by Spindle
    * @param string $un The Username for your Spindle Account
    * @param string $pw The Password for your spindle Account
    * @return mixed
    */
   public function __construct($pk=false, $cid=false, $sid=false, $un=false, $pw=false)
   {
       (!$pk && $pk != '')? $this->ReportMissingParam('private_key') : $this->_PRIVATE_KEY = $pk;
       (!$cid && $cid != '')? $this->ReportMissingParam('cid') : $this->_CID = $cid;
       (!$sid && $sid != '')? $this->ReportMissingParam('sid') : $this->_SID = $sid;
       (!$un && $un != '')? $this->ReportMissingParam('username') : $this->_USERNAME = $un;
       (!$pw && $pw != '')? $this->ReportMissingParam('password') : $this->_PASSWORD = $pw;
   }

    /**
     * CreateSession
     * Generates a session for a logged in user.  Returns the user’s SessionID.
     * @return  mixed
     */
    public function CreateSession($param_string = null, $encrypted_data = null, $return_json = null)
    {
        return $this->GetCURLResponse('Session/CreateSession', $param_string, $encrypted_data, $return_json);
    }
   
    /**
     * RegisterDevice
     * Registers the mobile device with the server.
     * Returns the devices PrivateKey, SID, MID, and additional merchant information.
     * This call is for mobile devices only that have fully registered and compatible swipers.
     * @return mixed
     */
    public function RegisterDevice($param_string = null, $encrypted_data = null, $return_json = null)
    {
        return $this->GetCURLResponse('Session/RegisterDevice', $param_string, $encrypted_data, $return_json);
    }
   
    /**
     * Authorize
     * Submits an authorization request to the server.
     * Authorize’s the card for the amount sent.
     * Will not place the transaction into the Capture batch.
     * Returns the Authorize’s TransactionID and Transaction Status.
     * @return mixed
     */
    public function Authorize($params = null)
    {
        return $this->GetResponseForAction('Transaction/Authorize', $params);
    }
   
    /**
     * Capture
     * Submits a capture request to the server.
     * Returns the Capture’s TransactionID and Transaction status.
     * A capture can only be called after a successful Authorization call.
     * @return mixed
     */
    public function Capture($params = null)
    {
        return $this->GetResponseForAction('Transaction/Capture',$params);
    }
   
    /**
     * Sale
     * Submits a Sale request to the server.
     * Returns the Sale’s TransactionID and Transaction status.
     * @return mixed
     */
    public function Sale($params = null)
    {
        return $this->GetResponseForAction('Transaction/Sale',$params);
    }
   
    /**
     * Refund
     * Submits a refund request to the server.
     * Returns the Refund’s TransactionID  and Transaction status.
     * A refund can be called if the transaction has already been settled.
     * @return mixed
     */
    public function Refund($params = null)
    {
        return $this->GetResponseForAction('Transaction/Refund',$params);
    }
   
    /**
     * Void
     * Submits a void request to the server.
     * Returns the Void’s TransactionID and Transaction status.
     * A void can be called if the transaction has not already settled.
     * @return mixed
     */
    public function Void($params = null)
    {
        return $this->GetResponseForAction('Transaction/Void',$params);
    }
   
    /**
     * ProcessSignature
     * Submits a signature file to be associated with the passed Transaction ID.
     * Returns if signature was successful.
     * Used primarily with mobile devices and terminals.
     * On this POST request, the Content-Type must be passed as,
     * “application/x-www-form-urlencoded” for the signature to be processed correctly.
     * @return mixed
     */
    public function ProcessSignature($params = null)
    {
        return $this->GetResponseForAction('Transaction/ProcessSignature',$params);
    }
   
    /**
     * TransactionHistory
     * Retrieves a list of transactions and their current status within the system.
     * @return mixed
     */
    public function TransactionHistory($params = null)
    {
        return $this->GetResponseForAction('Transaction/TransactionHistory',$params);
    }
   
    /**
     * RegisterCard
     * Registers a card within the card Vault.
     * @return mixed
     */
    public function RegisterCard($params = null)
    {
        return $this->GetResponseForAction('Vault/RegisterCard',$params);
    }
   
    /**
     * RetrieveCard
     * Retrieves a card that is stored within the Vault.
     * @return mixed
     */
    public function RetrieveCard($params = null)
    {
        return $this->GetResponseForAction('Vault/RetrieveCard',$params);
    }
   
    /**
     * RemoveCard
     * Permanently removes a card that is stored within the Vault.
     * @return mixed
     */
    public function RemoveCard($params = null)
    {
        return $this->GetResponseForAction('Vault/RemoveCard',$params);
    }
   
    /**
     * ChangePassword
     * Change Password for a user account.
     * Follows all normal security procedures and most know the old user password.
     * @return mixed
     */
    public function ChangePassword($params = null)
    {
        return $this->GetResponseForAction('Utility/ChangePassword',$params);
    }
   
    /**
     * TipAdjustment
     * Submits a tip adjustment to an existing Sale transaction.
     * Returns the original sale TransactionID and Status of the Tip Adjustment.
     * A Tip Adjustment can be called after a successful Sale,
     * but before Process (batching) occurs.
     * @return mixed
     */
    public function TipAdjustment($params = null)
    {
        return $this->GetResponseForAction('Transaction/TipAdjustment',$params);
    }
   
    /**
     * BoardMerchant
     * Submits a merchant to be underwritten and/or activated to allow for credit card processing
     * @return mixed
     */
    public function BoardMerchant($params = null)
    {
        return $this->GetResponseForAction('Board/BoardMerchant',$params);
    }
   
    /**
     * Checkout
     * If enabled, allows for data to be posted to a url that will
     * display a page designed to complete a transaction.
     * Upon successful processing, returns a result to the url on file
     * for "Hosted Success Url".  Any unsuccessful process will return
     * to the url on file for "Hosted Failure Url".
     * (These urls are defined during the process that enable the Hosted/Checkout functionality,
     * see support@spindle.com)
     * @return mixed
     */
    public function Checkout($params = null)
    {
        return $this->GetResponseForAction('Hosting/Checkout',$params);
    }

    public function GetCID()
    {
        return $this->_CID;
    }
    
    public function GetSID()
    {
        return $this->_SID;
    }
    
    public function GetUsername()
    {
        return $this->_USERNAME;
    }
    
    public function GetPassword()
    {
        return $this->_PASSWORD;
    }
    
    public function GetSessionID()
    {
        return $this->_SESSION_ID;
    }
    
    /**
     *************************************************************************************
     * Utility type functions
     *************************************************************************************
     */
    public function Encrypt()
    {
        error_log(self::GetEncryptionKey());
        
        srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
        if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
        // Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, self::PRIVATE_KEY, self::GetCrc32String(), MCRYPT_MODE_CBC, $iv));
        // We're done!
        return $encrypted;
        
    }
    
    protected function GetResponseForAction($action = null, $params = null)
    {
        if(!$action || empty($action)) {
            return $this->ReportMissingParam('RESPONSE_REQUEST_ACTION');
        }
        
        if(!$params || empty($params)) {
            return $this->ReportMissingParam('RESPONSE_REQUEST_' . $action . '_PARAMS');
        }
        
        
        return $this->GetCURLResponse($action,
                                      $this->BuildEncodedStringFromArray($params),
                                      $this->GetEncryptedCheckSum( $this->BuildStringFromArrayForEncryption($params)));
    }
   
    protected function aes256_cbc_encrypt($key, $data, $iv) {
        if(32 !== strlen($key)) $key = hash('SHA256', $key, true);
        if(16 !== strlen($iv)) $iv = hash('MD5', $iv, true);
        $padding = 16 - (strlen($data) % 16);
        $data .= str_repeat(chr($padding), $padding);
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
    }
    
    private function GetEncryptionIV()
    {
        return substr($this->_PRIVATE_KEY, 0, 16);
    }
    
    private function GetEncryptionKey()
    {
 
        return substr($this->_PRIVATE_KEY, 0, 32);
    }
    
    protected function GetEncryptedCheckSum($data = null)
    {
        $_data = (!$data || empty($data)) ? $_data = $this->GetCrc32String() : $data;
        //$_data = $this->GetCrc32String(); // slated for removal after test
        $_key = $this->GetEncryptionKey();
        $_iv  = $this->GetEncryptionIV();
        if(32 !== strlen($_key)) $_key = hash('SHA256', $_key, true);
        if(16 !== strlen($_iv)) $_iv = hash('MD5', $_iv, true);
        $_padding = 16 - (strlen($_data) % 16);
        $_data .= str_repeat(chr($_padding), $_padding);
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $_key, $_data, MCRYPT_MODE_CBC, $_iv));
    }
    
    protected function GetCrc32String()
    {
        return crc32( strtoupper( "".$this->_CID.$this->_CID.$this->_PASSWORD.$this->_SID.$this->_USERNAME ) );
    }
    
    /**
     * BuildEncodedStringFromArray
     * Takes the incoming array and builds a string with urlencoded values.
     * array('key'=>'value') becomes '&key='.urlencode(value)
     * @param mixed $ar The array to reduce to string
     * @return string
     */
    protected function BuildEncodedStringFromArray($ar = null)
    {
        if(!$ar || empty($ar)) {
            $this->ReportMissingParam('ArrayToEncodedString'); // turn on when ready
        }
        
        $_arrayToString = '';
        
        foreach($ar as $key => $value) {
            $_arrayToString .= "&amp;$key=" . urlencode($value);
        }
        
       return $_arrayToString;

    }
    
    protected function BuildStringFromArrayForEncryption($ar = null)
    {
        if(!$ar || empty($ar)) {
            $this->ReportMissingParam('StringFromArrayForEncoding');
        }
        /* debug */
        error_log(__FUNCTION__ . 'ar ' . print_r($ar, true));
        
        $_arrayToString = '';
        
        foreach($ar as $key => $value) {
            $_arrayToString .= $value;
        }
        
        /* debug */
        error_log(__FUNCTION__ . 'ar-string ' . $ar);
        
        return $_arrayToString;
    }
    
    protected function GetEncodedParamString()
    {
        return sprintf( "CID=%s&Password=%s&SID=%s&Username=%s",
                       urlencode($this->_CID),
                       urlencode($this->_PASSWORD),
                       urlencode($this->_SID),
                       urlencode($this->_USERNAME));
    }
    protected function ReportError($msg = null, $code = null)
    {
        if(!$msg || empty($msg)) {
            $msg = 'There was an unspecified error.';
        }
        
        if(!$code || empty($code)) {
            $code = '9999';
        }
        
        error_log("ERROR [$code] " . ucwords($msg) );
    }
    
    protected function ReportMissingParam($paramName=false)
    {
        if(!$paramName) {
            $this->ReportError('Parameter ' . $paramName . ' is missing', '0001');
        }
    }
    
    private function FormatJsonResponse($message=null, $code='0000')
    {
        if(!$message) {
            error_log( json_encode( array('message'=>'You have to call '.__FUNCTION__.' with an array', 'code'=>$code)));
        }
        
        error_log( json_encode( array('message'=>$message, 'code'=>$code)));
    }
    
    private function GetCURLResponse($method = null, $encodedParamString = null, $encryptedData = null, $returnArray = false)
    {
        
        $_encoded_param_string = (!$encodedParamString && !empty($encodedParamString)) ? $encodedParamString : $this->GetEncodedParamString();
        
        error_log(__FUNCTION__ . ' _encoded_param_string ' . $_encoded_param_string);
        
        $_encrypted_data = (!$encryptedData && !empty($encryptedData)) ? $encryptedData : $this->GetEncryptedCheckSum();
        
        error_log(__FUNCTION__ . ' _encrypted_data ' . $_encrypted_data);
        
        $_url = $this->_API_BASE_URL . $method . '?' . $_encoded_param_string . '&Checksum=' . $_encrypted_data;
        
        error_log(__FUNCTION__ . ' _url ' . $_url);
        
        //$_url = $this->_API_BASE_URL . $method . '?' . $this->GetEncodedParamString() . '&Checksum=' . urlencode($this->GetEncryptedCheckSum() );
        
        $__ch = curl_init($_url);
        curl_setopt($__ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($__ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($__ch, CURLOPT_SSLVERSION,3);
        curl_setopt($__ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($__ch, CURLOPT_TIMEOUT, '3');
        $__content = trim(curl_exec($__ch));
        curl_close($__ch);
 
        if($returnArray) {
            return json_decode($__content, true);
        }
        return $__content;  
    }

   
}
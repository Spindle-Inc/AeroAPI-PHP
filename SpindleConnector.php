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
    protected $_API_BASE_URL = 'https://api.spindleapi.com/1/';
    protected $_MCRYPT_ENCODING = \MCRYPT_RIJNDAEL_128;
    protected $_MCRYPT_MODE = \MCRYPT_MODE_CBC;
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
    public function CreateSession($encrypted_data = null, $return_json = null)
    {
        $params = array('CID'           => $this->GetCID(),
                        'Password'      => $this->GetPassword(),
                        'SID'           => $this->GetSID(),
                        'Username'      => $this->GetUsername());

        $sessionResponse = $this->GetCURLResponse('Session/CreateSession', 
                                                    $this->ArrayToParamString($params), 
                                                    $encrypted_data, 
                                                    $return_json);
        $responseAsJSON = json_decode($sessionResponse, true);
        $this->_SESSION_ID = $responseAsJSON['SessionID'];
        
        return $sessionResponse;
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
        $encoded_params = $params;
        $this->PrepareCardAndCVVInfo($params, $encoded_params);
        
        return $this->GetCURLResponse('Transaction/Authorize', 
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
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
        $encoded_params = $params;
        $this->PrepareCardAndCVVInfo($params, $encoded_params);
        
        return $this->GetCURLResponse('Transaction/Capture',
                                           $this->ArrayToParamString($params), 
                                           $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }
   
    /**
     * Sale
     * Submits a Sale request to the server.
     * Returns the Sale’s TransactionID and Transaction status.
     * @return mixed
     */
    public function Sale($params = null)
    {
        $encoded_params = $params;
        $this->PrepareCardAndCVVInfo($params, $encoded_params);
        
        return $this->GetCURLResponse('Transaction/Sale', 
                                      $this->ArrayToParamString($encoded_params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)) );
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
        $encoded_params = $params;
        $this->PrepareCardAndCVVInfo($params, $encoded_params);
        
        return $this->GetCURLResponse('Transaction/Refund',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
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
        $encoded_params = $params;
        $this->PrepareCardAndCVVInfo($params, $encoded_params);
        
        return $this->GetCURLResponse('Transaction/Void',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
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
        return $this->GetCURLResponse('Transaction/ProcessSignature',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }
   
    /**
     * TransactionHistory
     * Retrieves a list of transactions and their current status within the system.
     * @return mixed
     */
    public function TransactionHistory($params = null)
    {
        return $this->GetCURLResponse('Transaction/TransactionHistory',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }
   
    /**
     * RegisterCard
     * Registers a card within the card Vault.
     * @return mixed
     */
    public function RegisterCard($params = null)
    {
        $encoded_params = $params;
        $encoded_params['CardNumber'] = urlencode($this->GetEncryptedValue($encoded_params['CardNumber']));
        $encoded_params['CVV'] = urlencode($this->GetEncryptedValue($encoded_params['CVV']));
        $params['CardNumber'] = $this->GetEncryptedValue($encoded_params['CardNumber']);
        $params['CVV'] = $this->GetEncryptedValue($encoded_params['CVV']);
        return $this->GetCURLResponse('Vault/RegisterCard',
                                      $this->ArrayToParamString($encoded_params),
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params) ));
    }
   
    /**
     * RetrieveCard
     * Retrieves a card that is stored within the Vault.
     * @return mixed
     */
    public function RetrieveCard($params = null)
    {
        return $this->GetCURLResponse('Vault/RetrieveCard',
                                      $this->ArrayToParamString($params),
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)) );
    }
   
    /**
     * RemoveCard
     * Permanently removes a card that is stored within the Vault.
     * @return mixed
     */
    public function RemoveCard($params = null)
    {
        return $this->GetCURLResponse('Vault/RemoveCard',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }
   
    /**
     * ChangePassword
     * Change Password for a user account.
     * Follows all normal security procedures and most know the old user password.
     * @return mixed
     */
    public function ChangePassword($params = null)
    {
        return $this->GetCURLResponse('Utility/ChangePassword',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
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
        return $this->GetCURLResponse('Transaction/TipAdjustment',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }
   
    /**
     * BoardMerchant
     * Submits a merchant to be underwritten and/or activated to allow for credit card processing
     * @return mixed
     */
    public function BoardMerchant($params = null)
    {
        return $this->GetCURLResponse('Board/BoardMerchant',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
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
        return $this->GetCURLResponse('Hosting/Checkout',
                                      $this->ArrayToParamString($params), 
                                      $this->GetEncryptedCheckSum( $this->ArrayToEncryptableString($params)));
    }

    /** 
     * Returns the assigned CID
     * @return string
     */
    public function GetCID()
    {
        return $this->_CID;
    }
    
    /** 
     * Returns the assigned SID
     * @return string
     */
    public function GetSID()
    {
        return $this->_SID;
    }
    
    /** 
     * Returns the assigned Username
     * @return string
     */
    public function GetUsername()
    {
        return $this->_USERNAME;
    }
    
    /**
     * Returns the assigned Password
     * @return string
     */
    public function GetPassword()
    {
        return $this->_PASSWORD;
    }
    
    /** 
     * Returns the assigned SessionID
     * @return string
     */
    public function GetSessionID()
    {
        return $this->_SESSION_ID;
    }
    
    
    /**
     *************************************************************************************
     * Utility type functions
     *************************************************************************************
     */

    protected function PrepareCardAndCVVInfo(&$params, &$encoded_params)
    {
        $encoded_params = $params;
        $encoded_params['CardNumber'] = urlencode($this->GetEncryptedValue($encoded_params['CardNumber']));
        $encoded_params['CVV'] = urlencode($this->GetEncryptedValue($encoded_params['CVV']));
        $params['CardNumber'] = $this->GetEncryptedValue($params['CardNumber']);
        $params['CVV'] = $this->GetEncryptedValue($params['CVV']);
    }
    
    private function GetEncryptionIV()
    {
        return \substr($this->_PRIVATE_KEY, 0, 16);
    }
    
    private function GetEncryptionKey()
    {
 
        return \substr($this->_PRIVATE_KEY, 0, 32);
    }
    
    protected function GetEncryptedValue($data = null)
    {
        if(!$data) {
            return false;
        }
        $_data = $data;
        $_key = $this->GetEncryptionKey();
        $_iv  = $this->GetEncryptionIV();
        if(32 !== \strlen($_key)){ $_key = \hash('SHA256', $_key, true);}
        if(16 !== \strlen($_iv)) { $_iv = \hash('MD5', $_iv, true);}
        $_padding = 16 - (\strlen($_data) % 16);
        $_data .= \str_repeat(\chr($_padding), $_padding);

        $returnCode = \mcrypt_encrypt(\MCRYPT_RIJNDAEL_128, $_key, $_data, \MCRYPT_MODE_CBC, $_iv);
        
        return base64_encode($returnCode);
    }
    
    protected function GetEncryptedCheckSum($data = null)
    {
        $_data = (!$data || empty($data)) ? $_data = $this->GetCrc32String() : $this->GetCrc32StringFromData($data);
        $_key = $this->GetEncryptionKey();
        $_iv  = $this->GetEncryptionIV();
        if(32 !== \strlen($_key)){ $_key = \hash('SHA256', $_key, true);}
        if(16 !== \strlen($_iv)) { $_iv = \hash('MD5', $_iv, true);}
        $_padding = 16 - (\strlen($_data) % 16);
        $_data .= \str_repeat(\chr($_padding), $_padding);

        $returnCode = \mcrypt_encrypt(\MCRYPT_RIJNDAEL_128, $_key, $_data, \MCRYPT_MODE_CBC, $_iv);

        return \base64_encode($returnCode);
    }
    
    protected function GetCrc32String()
    {
        return \sprintf('%u', \crc32(\strtoupper( "".$this->_CID.$this->_CID.$this->_PASSWORD.$this->_SID.$this->_USERNAME )));
    }
    
    protected function GetCrc32StringFromData($data = null)
    {
        if (!is_array($data)) {
            $crc = sprintf('%u', \crc32(\strtoupper($data)));
            return $crc;
        } 
        
        $_data = $this->GetCID();
        
        \ksort($data);
        
        foreach ($data as $key => $value) {
            $_data .= $value;
        }

        $crc = sprintf('%u', \crc32(\strtoupper($data)));
        
        return $crc;
    }

    
    /** 
     * Converts an array to encryptable string (duplicate, removing)
     * @param mixed $params
     * @return string
     */
    protected function ArrayToEncryptableString($params = null)
    {
        if (!$params) {
            return false;
        }
        $_arrayToString = $this->GetCID();
        
        foreach($params as $key => $value) {
            $_arrayToString .= $value;
        }
        return $_arrayToString;
    }
    
    /**
     * Converts array params to param string
     * @param mixed $params
     * @return string
     */
    protected function ArrayToParamString($params = null)
    {
        if (!$params) {
            return false;
        }
        
        $_arrayToString = '';
        
        foreach($params as $key => $value) {
            
            $_arrayToString .= "&$key=" . \str_replace(' ', '+', $value);
        }
        return \ltrim($_arrayToString, '&');
    }

    /**
     * Error reporting function
     * @param string $msg
     * @param string $code
     */
    protected function ReportError($msg = null, $code = null)
    {
        if(!$msg || empty($msg)) {
            $msg = 'There was an unspecified error.';
        }
        
        if(!$code || empty($code)) {
            $code = '9999';
        }
        
    }
    
    /**
     * Reports a missing param in request
     * @param type $paramName
     */
    protected function ReportMissingParam($paramName=false)
    {
        if(!$paramName) {
            $this->ReportError('Parameter ' . $paramName . ' is missing', '0001');
        }
    }
    
    /** 
     * Calls out to the Spindle endpoint
     * @param string $method
     * @param string $encodedParamString
     * @param string $encryptedData
     * @param boolean $returnArray
     * @return mixed
     */
    private function GetCURLResponse($method = null, $encodedParamString = null, $encryptedData = null, $returnArray = false)
    {
        $_encoded_param_string = ($encodedParamString && !empty($encodedParamString)) ? $encodedParamString : $this->GetParamString();
        $_encrypted_data = ($encryptedData && !empty($encryptedData)) ? $encryptedData : $this->GetEncryptedCheckSum();
        $_url = $this->_TEST_API_BASE_URL . $method . '?' . print_r($_encoded_param_string, true) . '&Checksum=' . \urlencode($_encrypted_data);
        $__ch = \curl_init($_url);
        \curl_setopt($__ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($__ch, \CURLOPT_BINARYTRANSFER, 1);
        $__content = \trim(\curl_exec($__ch));
        \curl_close($__ch);
 
        if($returnArray) {
            return \json_decode($__content, true);
        }
        return $__content;  
    }

   
}
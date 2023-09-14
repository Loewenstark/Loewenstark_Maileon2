<?php

namespace Loewenstark\Maileon2\Model;

class Api
{
    const HTTP_CODE_OK = '200';
    const HTTP_CODE_CREATED = '201';
    const HTTP_CODE_NO_CONTENT = '204';
    
    const MAILEON_PERM_NONE = 1;
    const MAILEON_PERM_SINGLE_OPTIN = 2;
    const MAILEON_PERM_CONFIRMED = 3;
    const MAILEON_PERM_DOUBLE_OPTIN = 4;
    const MAILEON_PERM_DOUBLE_OPTIN_PLUS = 5;
    const MAILEON_PERM_OTHER = 6;

    const MAILEON_SYNCMODE_UPDATE = 1;
    const MAILEON_SYNCMODE_IGNORE = 2;

    /**
     *
     * @var \Loewenstark\Maileon2\Helper\Data
     */
    protected $maileonHelper;

    protected $api_result = null;
    protected $api_status = null;
    protected $api_error = null;

    protected $is_frontend = false;

    public function __construct(
        \Loewenstark\Maileon2\Helper\Data $maileonHelper,
        array $data = array()
    ) {
        $this->maileonHelper = $maileonHelper;
    }

    /**
     *
     * @param bool $mode
     * @return \Loewenstark\Maileon2\Model\Api
     */
    public function setIsFrontend($mode = true)
    {
        if (is_bool($mode)) {
            $this->is_frontend = $mode;
        }
        return $this;
    }

    /**
     *
     * @todo AddException Log
     * @param string $email
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function subscribe($email)
    {
        $email = trim($email ?? '');
        if (!$this->validateEmail($email)) {
            return false;
        }
        $params = array(
            'permission' => self::MAILEON_PERM_NONE,
            'sync_mode'  => self::MAILEON_SYNCMODE_IGNORE,
            'src'        => 'Magento/'.$this->maileonHelper->getMagentoVersion(),
            'doi'        => true,
            'doiplus'    => $this->maileonHelper->getUseDoiPlus(),
        );

        $doi_key = $this->maileonHelper->getDoiKey();
        if ($doi_key && isset($params['doi']) && $params['doi']) {
            $params['doimailing'] = $doi_key;
        }
        $this->apiRequest('contacts/email/'.$email, $params, 'POST', null, true);
        if ($this->api_status == self::HTTP_CODE_CREATED) {
            return true;
        }
        return false;
    }

    /**
     *
     * @todo AddException Log
     * @param string $email
     * @return boolean
     */
    public function unsubscribe($email)
    {
        $email = trim($email ?? '');
        if (!$this->validateEmail($email)) {
            return false;
        }
        $this->apiRequest('contacts/email/'.$email.'/unsubscribe', null, 'DELETE');
        if ($this->api_status == self::HTTP_CODE_NO_CONTENT) {
            return true;
        }
        return false;
    }

    /**
     *
     * @todo AddException Log
     * @param string $email
     * @return boolean
     */
    public function isSubscribed($email)
    {
        $email = trim($email ?? '');
        if (!$this->validateEmail($email)) {
            return false;
        }
        $this->apiRequest('contacts/email/'.$email, null, 'GET');
        if ($this->api_status == self::HTTP_CODE_OK) {
            $xml = simplexml_load_string($this->api_result);
            $permArray = [
                self::MAILEON_PERM_DOUBLE_OPTIN,
                self::MAILEON_PERM_DOUBLE_OPTIN_PLUS
            ];
            if ($xml && isset($xml->permission)
                    && in_array((int)$xml->permission, $permArray)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $path URL Path
     * @param string|array $query URL Additional Parameters (optional)
     * @param string $type HTTP - GET|POST|DELETE (optional)
     * @param string|array $postData (optional)
     * @param bool $json (optional)
     * @return void
     */
    protected function apiRequest($path, $query = null, $type = 'GET', $postData = null, $json = false)
    {
        // reset values
        $this->api_result = null;
        $this->api_status = null;
        $this->api_error = null;

        $postData = $this->postData($postData, $json);
        $query = $this->arrayTohttpQuery($query);

        $requestPath = $path . ($query ? '?'.$query : '');
        
        $ch = $this->prepareCurl($json);
        curl_setopt($ch, CURLOPT_URL, 'https://api.maileon.com/1.0/' . $requestPath);
        
        switch (strtoupper($type)) {
            case 'GET':
                $type = 'GET';
                break;
            case 'POST':
                $type = 'POST';
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                break;
            case 'DELETE':
                $type = 'DELETE';
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }
        $this->api_result = curl_exec($ch);
        $this->api_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->api_error  = curl_error($ch);
        curl_close($ch);
        return;
    }

    /**
     *
     * @param string $email
     * @return boolean
     */
    public function validateEmail($email)
    {
        if (!\Zend_Validate::is($email ?? '', \Magento\Framework\Validator\EmailAddress::class)) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param type $postData
     * @param type $json
     * @return string
     */
    protected function postData($postData, $json = false)
    {
        if (empty($postData) && $json) {
            return '{}';
        }
        if (is_array($postData) && $json) {
            return json_encode($postData);
        }
        return $this->arrayTohttpQuery($postData);
    }

    /**
     *
     * @param string|array $query_data
     * @return string
     */
    protected function arrayTohttpQuery($query_data)
    {
        if (is_array($query_data)) {
            foreach ($query_data as $_key => $value) {
                if (is_bool($value)) {
                    $query_data[$_key] = ($value ? 'true' : 'false');
                }
            }
            return http_build_query($query_data);
        }
        return $query_data;
    }

    /**
     *
     * @param type $json
     */
    protected function prepareCurl($json = false)
    {
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            throw new Exception('Your System does not Support TLSv1_2 : CURL_SSLVERSION_TLSv1_2');
            return;
        }
        // if it will be removed in the future
        if (!defined('CURLINFO_HTTP_CODE')) {
            define('CURLINFO_HTTP_CODE', CURLINFO_RESPONSE_CODE);
        }
        $mimeType = 'application/vnd.maileon.api+xml';
        if ($json) {
            $mimeType = 'application/vnd.maileon.api+json';
        }
        $header = [
            'Content-Type: '.$mimeType.'; charset=utf-8',
            'Accept: '.$mimeType,
            'Authorization: Basic ' . $this->maileonHelper->getBase64ApiKey(),
            'Expect:'
        ];
        $ch = curl_init();
        $system = php_uname('s');
        $arch = php_uname('m');
        $type = $system;
        $cversion = curl_version();

        if ($system == 'Linux') {
            $type = 'X11';
        }

        $ua = 'Mozilla/5.0 ('.$type.'; '.$system.' '.$arch.') cURL/'
                .$cversion['version'].' Magento/'
                .$this->maileonHelper->getMagentoVersion();

        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->maileonHelper->getVerfiyPeer());
        
        $timeout = $this->maileonHelper->getTimeout();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($this->maileonHelper->getForceTls12()) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        return $ch;
    }
}

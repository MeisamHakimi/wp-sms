<?php

namespace WP_SMS\Gateway;

class payamakalmas extends \WP_SMS\Gateway
{
    private $wsdl_link = "http://79.175.167.50/webservice/server.php?wsdl";
    private $client = null;
    public $tariff = "http://almasac.ir/";
    public $unitrial = true;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "09xxxxxxxx";

        if (!class_exists('nusoap_client')) {
            include_once WP_SMS_DIR . 'includes/libraries/nusoap.class.php';
        }

        $this->client = new \nusoap_client($this->wsdl_link);

        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8      = true;
    }

    public function SendSMS()
    {

        /**
         * Modify sender number
         *
         * @param string $this ->from sender number.
         * @since 3.4
         *
         */
        $this->from = apply_filters('wp_sms_from', $this->from);

        /**
         * Modify Receiver number
         *
         * @param array $this ->to receiver number
         * @since 3.4
         *
         */
        $this->to = apply_filters('wp_sms_to', $this->to);

        /**
         * Modify text message
         *
         * @param string $this ->msg text message.
         * @since 3.4
         *
         */
        $this->msg = apply_filters('wp_sms_msg', $this->msg);

        // Get the credit.
        $credit = $this->GetCredit();

        // Check gateway credit
        if (is_wp_error($credit)) {
            // Log the result
            $this->log($this->from, $this->msg, $this->to, $credit->get_error_message(), 'error');

            return $credit;
        }


        $result = $this->client->call("SENDSMS", array(
            'UserName'   => $this->username,
            'Password'   => $this->password,
            'LineNumber' => $this->from,
            'Recivers'   => implode($this->to, ','),
            'SMSSMG'     => $this->msg,
            'MesClass'   => '1'
        ));

        if ($result) {
            // Log the result
            $this->log($this->from, $this->msg, $this->to, $result);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */
            do_action('wp_sms_send', $result);

            return $result;
        }
        // Log th result
        $this->log($this->from, $this->msg, $this->to, $result, 'error');

        return new \WP_Error('send-sms', $result);

    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new \WP_Error('account-credit', __('Username and Password are required.', 'wp-sms'));
        }

        $result = $this->client->call("Credit", array(
            'UserName' => $this->username,
            'Password' => $this->password
        ));

        // this methid is undefined in webservice.
        return '1';
    }
}
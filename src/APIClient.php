<?php

namespace EmailListVerify;

class APIClient {

    /**
     * YOUR API KEY. Don't add it here, set it in the constructor.
     * @var string
     */
    protected $KEY = '';

    /**
     * Base endpoint
     * @var string
     */
    protected $base_url = "https://apps.emaillistverify.com/api/";

    /** @var string Last response/verification status */
    public $last_status = null;

    /**
     * Counts valid api request done.
     * Automatically incremented when making a valid verifyEmail request.
     * @var int
     */
    public $request_count = 0;

    /**
     * Return values for emails that can't be verifyed.
     * Set this variable to affect return value of parseStatus method.
     * Defaults to null.
     * @var mixed
     */
    public $unkown_return = null;

    /**
     * Return values for disposable emails.
     * Set this variable to affect return value of parseStatus method.
     * Defaults to true.
     * @var mixed
     */
    public $disposable_return = true;

    /**
     * Return values for not existent emails.
     * Set this variable to affect return value of parseStatus method.
     * Defaults to false.
     * @var mixed
     */
    public $notexists_return = false;

    /**
     * Return values for disabled emails.
     * Set this variable to affect return value of parseStatus method.
     * Defaults to false.
     * @var mixed
     */
    public $disabled_return = false;

    /**
     * Return values for spam traps.
     * Set this variable to affect return value of parseStatus method.
     * Defaults to false.
     * @var mixed
     */
    public $spamtrap_return = false;

    /**
     * Instance Emai List Verify Api Client
     * @param string $api_key
     */
    public function __construct($api_key) {
        $this->KEY = $api_key;
    }

    /**
     * Verifies a single email.
     * Returns true if email is valid, false if email is invalid.
     * Email that couldn't be verifyed (status=unknown) returns the value of
     * "EmailListVerify\APIClient->unknown_return" so you can configure them as
     * valid/invalid/anything else.
     * @param string $email
     * @return boolean
     * #@throws \Exception
     */
    public function verifyEmail($email) {
        $params = array(
            'email' => $email,
            'secret' => $this->KEY
        );

        $httpcode = null;
        $response = $this->curl_get($this->base_url . 'verifyEmail', $params, $httpcode);

        if ($response == false) {
            throw new \Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
        } else {
            $this->last_status = $response;
            return $this->parseStatus($response);
        }
    }

    /**
     * Parses Status codes and return true/false or an \Exception.
     * Read both codes return by OneByONe verification than file verification.
     * @url http://www.emaillistverify.com/docs/result-guide
     * @param type $status
     * @return boolean
     * @throws \Exception
     */
    public function parseStatus($status) {
        switch ($status) {
            // VALID
            case 'ok_for_all';
            //  email server is saying that it is ready to accept letters to any email
            case 'accept_all':
            // server is set to accept all emails at a specific domain, these domains accept any email you send to them
            case 'ok':
                //  all is OK. server is saying that it is ready to receive a letter to this address, and no tricks have been detected
                // Valid request.
                ++$this->request_count;
                return true;
            // INVALID
            case 'fail':
            // Invalid Email
            case 'unknown_email':
            // server saying that delivery failed, and the email does not exist
            case 'smtp_error':
            // SMTP answer from the server is invalid, destination server reported an internal error to us
            case 'smtp_protocol':
            // destination server allows us to connect, but SMTP session was closed before the email was verified
            case 'attempt_rejected':
            // delivery failed, reason similar to “rejected”
            case 'domain_error':
            // email server for the whole domain is not installed or is incorrect, so no emails are deliverable
            case 'dead_server':
            // email server is dead, no connection to it exists
            case 'error':
                // server is saying that delivery failed, but no information about the email exists
                // Valid request.
                ++$this->request_count;
                return false;
            // GREY AREA
            case 'unknown_email':
                // Valid request.
                ++$this->request_count;
                // server saying that delivery failed, and the email does not exist
                ++$this->notexists_return;
                return false;
            case 'unknown':
                // Valid request.
                ++$this->request_count;
                // Unkown result
                return $this->unkown_return;
            case 'disposable':
                // Valid request.
                ++$this->request_count;
                return $this->disposable_return;
            case 'email_disabled':
                // Valid request.
                ++$this->request_count;
                return $this->disabled_return;
            case 'spam_traps':
                // Valid request.
                ++$this->request_count;
                return $this->spamtrap_return;
            // ERRORS
            case 'antispam_system':
                throw new \Exception(get_class() . ': some anti-spam technology is blocking the verification progress.');
            case 'relay_error':
                throw new \Exception(get_class() . ': delivery fail because a relaying problem took place.');
            case 'syntax_error':
            case 'incorrect':
                throw new \Exception(get_class() . ': no email provided in request. Email syntax error (example: myaddress[at]gmail.com, must be myaddress@gmail.com).');
            case 'key_not_valid':
                throw new \Exception(get_class() . ': no api key provided in request or invalid.');
            case 'missing_paramteres':
                throw new \Exception(get_class() . ': there are no validations remaining to complete this attempt.');
        }
    }

    /**
     * Upload a file containing a list of email to verify.
     * Returns file id, used to query "getApiFileInfo" api.
     * @param string $file_name
     * @param string $file_path
     * @return type
     * @throws \Exception
     */
    public function verifyApiFile($file_name, $file_path) {

        if (!file_exists($file_path)) {
            throw new \Exception(get_class() . ": can't upload {$file_name}, file doesn't exists:\n{$file_path}");
        } else {
            $post_params = array(
                'file_contents' => "@$file_path"
            );

            $query_params = array(
                'secret' => $this->KEY,
                'filename' => $file_name
            );

            $httpcode = null;
            $response = $this->curl_post($this->base_url . 'verifyApiFile?' . http_build_query($query_params), $post_params, $httpcode);
            if ($response == false) {
                throw new \Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
            } else {
                // Save
                $this->last_file_id = $response;

                // Valid request.
                ++$this->request_count;

                return $response;
            }
        }
    }

    public $last_file_id = '';

    /**
     * Check file status.
     * @param string $id
     * @return FileInfo
     * @throws \Exception
     */
    public function getApiFileInfo($id) {
        $params = array(
            'id' => $id,
            'secret' => $this->KEY
        );

        $httpcode = null;
        $response = $this->curl_get($this->base_url . 'getApiFileInfo', $params, $httpcode);

        if ($response == false) {
            throw new \Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
        } else {

            // Valid request.
            ++$this->request_count;

            return new FileInfo($response);
        }
    }

    /**
     * Check last uploaded file id info.
     * @return FileInfo
     */
    public function getLastfileInfo() {
        return $this->getApiFileInfo($this->last_file_id);
    }

    /**
     * Perform a Curl GET request. Returns data event if errors occurs.
     * Pass $httpcode to read response's HTTP code.
     * @param string $URL
     * @param array $fields
     * @param int $httpcode
     * @return string|false
     */
    protected function curl_get($URL, $fields, & $httpcode = null) {

        $fields_string = http_build_query($fields);

        if (stripos($URL, '?') > -1) {
            $fields_string = '&' . $fields_string;
        } else {
            $fields_string = '?' . $fields_string;
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL . $fields_string);
        // common setup
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($c);
        $httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents) {
            return $contents;
        } else {
            return false;
        }
    }

    /**
     * Make a POST request to given url.
     * Pass $httpcode to read response's HTTP code.
     * @param string $URL
     * @param array $fields
     * @param int $httpcode
     * @return string|boolean
     */
    protected function curl_post($URL, $fields, & $httpcode = null) {

        //set the url, number of POST vars, POST data
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $fields);
        // common setup
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($c);
        $httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents) {
            return $contents;
        } else {
            return false;
        }
    }

}

<?php

namespace EmailListVerify;

class APIClient {

    /**
     * YOUR API KEY. Don't add it here, pass it in the constructor.
     * @var string
     */
    protected $KEY = '';

    /**
     * Base endpoint
     * @var string
     */
    protected $base_url = "https://apps.emaillistverify.com/api/";

    /**
     * Counts valid api request done.
     * Automatically incremented when making a valid verifyEmail request.
     * @var int
     */
    public $request_count = 0;

    /**
     * Return values for emails that can't be verifyed.
     * Defaults to null.
     * @var mixed
     */
    protected $unkown_return = null;

    /**
     * Instance Emai List Verify Api Client
     * @param string $api_key
     */
    public function __construct($api_key) {
        $this->KEY = $api_key;
    }

    /**
     * Use this function to set up what is your desired retunr value when a
     * verification attempts fails with "unknown".
     * Ex: set it at true to treat emails as valid.
     * @param mixed $value
     */
    public function setUnknownVerificationReturnValue($value) {
        $this->unkown_return = $value;
    }

    /**
     * Verifies a single email.
     * Returns true if email is valid, false if email is invalid.
     * Email that couldn't be verifyed (status=unknown) returns the value of
     * "EmailListVerify\APIClient->unknown_return" so you can configure them as
     * valid/invalid/anything else.
     * @param string $email
     * @return boolean
     * #@throws Exception
     */
    public function verifyEmail($email) {
        $params = array(
            'email' => $email,
            'secret' => $this->KEY
        );

        $httpcode = null;
        $response = $this->curl_get($this->base_url . 'verifyEmail', $params, $httpcode);

        if ($response == false) {
            throw new Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
        } else {

            switch ($response) {
                case 'ok':
                    // Valid request.
                    ++$this->request_count;
                    // Valid Email
                    return true;
                case 'fail':
                    // Valid request.
                    ++$this->request_count;
                    // Invalid Email
                    return false;
                case 'unknown':
                    // Valid request.
                    ++$this->request_count;
                    // Unkown result
                    return $this->unkown_return;
                case 'incorrect':
                    throw new Exception(get_class() . ': no email provided in request. Email syntax error (example: myaddress[at]gmail.com, must be myaddress@gmail.com).');
                case 'key_not_valid':
                    throw new Exception(get_class() . ': no api key provided in request or invalid.');
                case 'missing_paramteres':
                    throw new Exception(get_class() . ': there are no validations remaining to complete this attempt.');
            }
        }
    }

    /**
     * Upload a file containing a list of email to verify.
     * Returns file id, used to query "getApiFileInfo" api.
     * @param string $file_name
     * @param string $file_path
     * @return type
     * @throws Exception
     */
    public function verifyApiFile($file_name, $file_path) {

        if (!file_exists($file_path)) {
            throw new Exception(get_class() . ": can't upload {$file_name}, file doesn't exists:\n{$file_path}");
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
                throw new Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
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
     * @throws Exception
     */
    public function getApiFileInfo($id) {
        $params = array(
            'id' => $id,
            'secret' => $this->KEY
        );

        $httpcode = null;
        $response = $this->curl_get($this->base_url . 'verifyEmail', $params, $httpcode);

        if ($response == false) {
            throw new Exception(get_class() . ": request failed with HTTP code: {$httpcode}.");
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

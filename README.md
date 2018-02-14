# EmailListVerify API PHP library

This library provides a convient interface for email verification with
[emaillistverify](http://www.emaillistverify.com/) in PHP.

## Installation

Install via composer: `composer require korra88/email-list-verify-api-php-client`.
Or download in your project and include the two files in `src/` directory.

### Usage

First obtain an api key by registering in emailistverify, creating an
application, and set it in the constructor.

```php
// composer autoloader
require_once '/path/to/vendor/autoload.php';
$emailVerify = new EmailListVerify\APIClient(YOUR_API_KEY);
```

Then you can either can verify emails one by one with:

```php
$email = "your_email@example.com";
try {
    $status = $emailVerify->verifyEmail($email);
} catch (Exception $e) {
    echo "\n" . $e->getMessage();
    $status = false;
}
echo "\n{$email} status: " . ($status ? 'valid' : 'invalid')
```

Or upload a file with a list of emails in a csv-like format:

```php
$email_file_path = __DIR__ . '/email_list.csv';
$email_file_name = 'test_emails.csv';
try {
    $file_id = $emailVerify->verifyApiFile($email_file_name, $email_file_path);
} catch (Exception $e) {
    echo "\n" . $e->getMessage();
}
echo "\nCreated file {$file_id}.";
```

and monitor it's status with:


```php
try {
    $file_info = $emailVerify->getApiFileInfo($file_id);
echo "\nFile status: {$file_info->status}";
} catch (Exception $e) {
    echo "\n" . $e->getMessage();
}
```

When status is `finished` you can download the file (using `$file_info->link2`) and read results.

### Official documentation

More information can be found in official documentation [here](http://www.emaillistverify.com/docs/index).

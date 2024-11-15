# VeraCrypt Crash Collector

## Overview

**VeraCrypt Crash Collector** is a web application designed to gather and manage crash reports from the VeraCrypt desktop
application running on Linux and macOS.
This app ensures that users have control over their data, as crash reports are only sent if users explicitly allow it
after VeraCrypt detects a crash has occurred.

The collected crash reports provide vital information to improve the stability and performance of VeraCrypt by helping
to identify and resolve issues.

Similar projects are f.e. https://github.com/tdf/crash-srv.

## Crash Reporting Mechanism

When a crash occurs, the following information is gathered by the crash reporting system:

- **Program version**: The specific version of VeraCrypt that encountered the issue.
- **Operating system version**: The version of the OS on which the crash occurred.
- **Hardware architecture**: Information about the CPU architecture (e.g., x86_64, ARM).
- **Checksum of the VeraCrypt executable**: A checksum that helps verify the integrity of the executable.
- **Error category**: The signal number indicating the type of error.
- **Error address**: The memory address where the fault occurred.
- **Call stack**: The sequence of function calls leading up to the error.

### Important Note

No personal information is included in the crash reports. The call stack captured is purely technical and does not contain
any user data.

## Purpose

The goal of VeraCrypt Crash Collector is to streamline the crash report management process and provide a clear path to
fixing any technical issues in VeraCrypt. It helps developers identify and resolve bugs by analyzing the crash data collected.

## Contribution

Contributions are welcome! Please follow the [contribution guidelines](CONTRIBUTING.md) when submitting a pull request.

## License

This project is licensed under the [Apache License 2.0](LICENSE).

## Requirements

- PHP 8.1 and up, with the SQLite and PHPRedis extensions (using SQLite Library 3.35.4 or later)
- a webserver configured to run PHP
- a Redis server
- Composer, to install the required dependencies

Note: PostgreSQL and MariaDB >= 10.5 should also work as an alternative to SQLite, but so far they have been tested less
extensively.

## Installation

1. run `composer install` at the root of the project
2. check the configuration in `.env`, and, if required, change any value by saving it in a file named `.env.local`
3. make sure that the `var/data` directory is writeable (this is where the app will create its sqlite db by default),
   as well as `var/logs` and `var/cache/twig`
4. configure php:

    - for a production installation, it is recommended to follow the owasp guidelines available at
       https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
    - it is recommended to use Redis for php session storage instead of the default file-based storage
5. create an administrator user: run the cli command `php ./bin/console user:create --is-superuser <username> <email> <firstname> <lastname>`
6. set up a cronjob (daily or weekly is fine) running the cli command `php ./bin/console token:prune`
7. set up the webserver:

    - configure the vhost root directory to be the `public` directory. No http access to any other folder please
    - make sure .php scripts are executed via the php interpreter
    - the file to serve when a directory index is requested should be `index.php`
    - no rewrite rules are necessary
8. navigate to `https://your-host/report/upload.php` to upload crash reports; to `https://your-host/admin/index.php` for browsing them

### Advanced configuration

* Removing `.php` from the URLs used by the application

  In order to have the application use "php-less" URLs, you have to 1. set up the webserver so that it will try to
  pass requests for URLs not ending in `.php` to the php interpreter, and 2. configure the application accordingly.

  Point 1 can be done, for Nginx, following f.e. the instructions at
  https://serverfault.com/questions/761627/nginx-rewrite-to-remove-php-from-files-has-no-effect-but-to-redirect-to-homepag

  For point 2, add `URLS_STRIP_INDEX_DOT_PHP=true` and `URLS_STRIP_PHP_EXTENSION=true` to file `.env.local`

* Optimizing SQLite performance and scalability

  Optionally, run the SQLite pragma `journal_mode=WAL` to have optimized performance and concurrency

  Optionally, set up cronjobs to run the SQLite pragmas `optimize` and `integrity_check`

* Using Redis for PHP session storage

  Google is your friend - there are countless guides for this.

## How it works

Once uploaded, crash reports are stored in a SQLite database. They are not available for examination to the public, but
only to registered users of this web application. For a short time after the upload, the submitter of a crash report can
see the uploaded data and is given a chance to remove it if desired.

The web interface is kept extremely simple by design. Besides supporting the anonymous upload of the crash reports, it
allows application users to browse them and to change their own login password. The only way to manage the application's
users accounts (create, remove, update, enable/disable them) is via a command-line script.

### The upload API

The interaction between VeraCrypt and the Crash Collector is the following:

1. VeraCrypt sends a POST request to the url `/report/upload.php` using `application/x-www-form-urlencoded` encoding.
   In case of success, a 303 redirection response is returned.
   In case of errors with the POST request data, a 400 response is returned, with `plain/text` content tipe, and
   error messages displayed one per line.
   In case of unexpected / server errors, a 40x or 50x response can also be returned.
2. In case of a successful upload, VeraCrypt should start a browser session and send the user to the redirection target
   URL given at step 1.

Rate limiting is implemented, to avoid spamming of the upload page.

### Troubleshooting and Debugging

The name of the fields to submit at step 1 above can be seen by setting `ENABLE_BROWSER_UPLOAD=true` in config. file
`.env.local`, and pointing a browser at the `/report/upload.php` URL.
That results in the display of a crash-report upload form which can be filled in manually.

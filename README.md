# VeraCrypt Crash Collector

## Overview

**VeraCrypt Crash Collector** is a web application designed to gather and manage crash reports from the VeraCrypt desktop
application running on Linux and macOS.
This app ensures that users have control over their data, as crash reports are only sent if users explicitly allow it
after VeraCrypt detects a crash has occurred.

The collected crash reports provide vital information to improve the stability and performance of VeraCrypt by helping
to identify and resolve issues.

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

## Installation

1. run `composer install` at the root of the project
2. check the configuration in `.env`, and, if required, change any value by saving it in a file named `.env.local`
3. make sure that the `var/data` directory is writeable (this is where the app will create its sqlite db by default),
   as well as `var/logs` and `var/cache/twig`
4. configure php:

    - for a production installation, it is recommended to follow the owasp guidelines available at
       https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
    - it is recommended to use Redis for php session storage
5. create an administrator user: run the cli command `php ./bin/console user:create --is-superuser <username> <email> <firstname> <lastname>`
6. set up a cronjob (daily or weekly is fine) running the cli command `php ./bin/console forgotpasswordtoken:prune`
7. set up the webserver:

    - configure the vhost root directory to be the `public` directory. No http access to any other folder please
    - make sure .php scripts are executed via the php interpreter
    - no rewrite rules are necessary
8. navigate to `https://your-host/upload/` to upload crash reports; to `https://your-host/admin/` for browsing them
9. optionally, run the SQLite pragma `journal_mode=WAL` to have optimized performance and concurrency
10. optionally, set up cronjobs to run the SQLite pragmas `optimize` and `integrity_check`

## How it works

Once uploaded, crash reports are stored in a SQLite database. They are not available for examination to the public, but
only to users of this web application.

The web interface is kept extremely simple by design. Besides supporting the anonymous upload of the crash reports, it
allows application users to browse them and to change their own login password. The only way to manage the application's
users accounts (create, remove, update, enable/disable them) is via a command-line script.

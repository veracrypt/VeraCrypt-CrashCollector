# VeraCrypt Crash Collector

## Overview

**VeraCrypt Crash Collector** is a web application designed to gather and manage crash reports from the VeraCrypt desktop application running on Linux and macOS. This app ensures that users have control over their data, as crash reports are only sent if users explicitly allow it after VeraCrypt detects a crash has occurred.

The collected crash reports provide vital information to improve the stability and performance of VeraCrypt by helping to identify and resolve issues.

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

No personal information is included in the crash reports. The call stack captured is purely technical and does not contain any user data.

## Purpose

The goal of VeraCrypt Crash Collector is to streamline the crash report management process and provide a clear path to fixing any technical issues in VeraCrypt. It helps developers identify and resolve bugs by analyzing the crash data collected.

## Contribution

Contributions are welcome! Please follow the [contribution guidelines](CONTRIBUTING.md) when submitting a pull request.

## License

This project is licensed under the [Apache License 2.0](LICENSE).


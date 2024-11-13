# Contributing to VeraCrypt Crash Collector

Thank you for considering contributing to VeraCrypt Crash Collector! Your contributions help improve the project, and we appreciate your effort. The following guidelines will assist you through the contribution process.

## Getting Started

### 1. Fork the Repository

- Navigate to the [VeraCrypt-CrashCollector](https://github.com/veracrypt/VeraCrypt-CrashCollector) repository and click "Fork."
- Clone your fork locally:
  ```bash
  git clone https://github.com/your-username/VeraCrypt-CrashCollector.git
  ```
- Set up the upstream remote to keep your fork up-to-date with the original repository:
  ```bash
  git remote add upstream https://github.com/veracrypt/VeraCrypt-CrashCollector.git
  ```

### 2. Set Up Your Development Environment

Ensure you have the required tools installed to run a PHP web application. 

- **PHP**: Make sure you have PHP installed on your system.
- **Web Server**: Use a local web server like Apache or Nginx, or use the built-in PHP development server:
  ```bash
  php -S localhost:8000
  ```

### 3. Create a New Branch

Before you start working, create a new branch for your changes:
```bash
git checkout -b feature/your-feature-name
```

Use a clear, descriptive name for your branch, such as `fix/issue-123` or `feature/new-feature`.

### 4. Make Your Changes

Make your changes in the new branch. Be sure to:

- Follow the **coding standards** and existing conventions.
- Write **clear, concise comments** where necessary.
- Add or update **tests** if you are adding new functionality.
- Regularly run the project to ensure everything is working.

### 5. Test Your Changes

Run the application locally to ensure your changes work using your preferred PHP development setup.

### 6. Commit Your Changes

After making sure everything is working, commit your changes with a meaningful message:
```bash
git commit -m "Fix issue with crash report handling in macOS"
```

Try to keep your commits small and focused on a specific change.

### 7. Push to Your Fork

Push your changes to your fork on GitHub:
```bash
git push origin feature/your-feature-name
```

### 8. Create a Pull Request (PR)

Once your changes are pushed, open a Pull Request (PR) in the original repository:

1. Go to the [Pull Requests](https://github.com/veracrypt/VeraCrypt-CrashCollector/pulls) section.
2. Click "New Pull Request."
3. Choose your branch and provide a descriptive title and detailed description of your changes.

Make sure to link to any relevant issues using `Fixes #issue_number` in the description. This will automatically close the linked issue when the PR is merged.

## Code Reviews

All PRs are subject to review by maintainers or other contributors. Please:

- Be open to feedback.
- Address requested changes promptly.
- Participate in discussions if necessary.

Reviewing ensures code quality, consistency, and alignment with project goals. Don't hesitate to ask for clarification if you're unsure about any feedback.

## Contribution Guidelines

### Bug Reports

If you encounter a bug, please submit an issue to help us investigate:

- **Title**: A concise description of the issue.
- **Steps to Reproduce**: A detailed list of steps to reproduce the bug.
- **Expected Behavior**: What should have happened.
- **Actual Behavior**: What actually happened, including error messages if applicable.
- **Versions**: The VeraCrypt version and the OS version (Linux/macOS) you are using.
- **Logs or Crash Reports**: Attach relevant logs or crash reports, if available.

### Feature Requests

We welcome new feature suggestions! If you have an idea, submit an issue labeled "feature request" with the following details:

- **Use Case**: Why this feature is needed.
- **Proposed Solution**: A description of how it might work.
- **Alternatives Considered**: Other possible approaches (if applicable).

### Coding Standards

- Follow the **existing code style** and patterns.
- Always include **descriptive comments** in your code.
- Write **unit tests** for new features or bug fixes when applicable.
- Ensure your changes do not break existing functionality.

### Commit Guidelines

- Keep commits small and focused.
- Use descriptive commit messages, following this format:
  - **fix**: for bug fixes.
  - **feat**: for new features.
  - **docs**: for documentation changes.
  - **refactor**: for code improvements.
  - **test**: for test changes or additions.

Example commit message:
```
feat: add crash report parsing for Linux
```

## License

By contributing to VeraCrypt Crash Collector, you agree that your contributions will be licensed under the [Apache License 2.0](LICENSE).

---

Thank you for contributing! We look forward to collaborating with you.

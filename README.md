# PHPGit

PHPGit is a web-based Git repository hosting platform developed using PHP. It provides an intuitive interface for
managing Git repositories, users, and access controls.

PHPGit is build as school project with clean PHP.

## Planned Features

- Create, manage, and delete Git repositories via the web interface
- User authentication and access control
- Repository browsing and file viewing
- Commit history and diff viewer
- Web-based repository initialization and cloning instructions

## Requirements

- PHP 8.0 or higher
- Composer
- Web server (Apache, Nginx, etc.)
- Git

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Azreyo/phpgit.git
   cd phpgit
   ```
2. Install dependencies using Composer:
   ```bash
   composer install
   ```
3. Configure your web server to serve the appropriate directory (`src`).
4. Copy the example environment file and update configuration as needed:
   ```bash
   cp .env.example .env
   ```
5. Set appropriate permissions for storage and cache directories if required.

## Usage

- Access the application via your web browser at the configured URL.
- Register a new user or log in with existing credentials.
- Create and manage repositories from the dashboard.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.


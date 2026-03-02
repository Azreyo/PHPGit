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

## Security
1. Allow apache2 *mod_rewrite* and *mod_headers*
```bash
   sudo a2enmod rewrite headers
   sudo systemctl restart apache2
```
2. Add security `.htaccess`
```bash
   Options -Indexes

   <FilesMatch "(^\.env(\..*)?$|config\.php|composer\.(json|lock)$)">
      Require all denied
   </FilesMatch>

   <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /

      RewriteRule ^(includes|pages)(/|$) - [F,L]
   </IfModule>

   <IfModule mod_headers.c>
      Header always set X-Content-Type-Options "nosniff"
      Header always set X-Frame-Options "SAMEORIGIN"
      Header always set Referrer-Policy "strict-origin-when-cross-origin"
   </IfModule>

```

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Azreyo/phpgit.git
   cd phpgit
   ```
   
2. Install php8.*
   ```bash
   sudo apt install php8.4-cli php8.4-fpm php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath
   ```
3. Install dependencies using Composer:
   ```bash
   composer install
   ```
4. Configure your web server to serve the appropriate directory (`src`). 
5. Copy the example environment file and update configuration as needed:
   ```bash
   cp src/.env.example src/.env
   ```
6. Set appropriate permissions for storage and cache directories if required.

## Usage

- Access the application via your web browser at the configured URL.
- Register a new user or log in with existing credentials.
- Create and manage repositories from the dashboard.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.


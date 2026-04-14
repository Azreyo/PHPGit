# PHPGit

PHPGit is a web-based Git repository hosting platform developed using PHP. It provides an intuitive interface for
managing Git repositories, users, and access controls.

> **NOTE: PHPGit is build as school project with clean PHP.**

## Planned Features

- Create, manage, and delete Git repositories via the web interface
- User authentication and access control
- Repository browsing and file viewing
- Commit history and diff viewer
- Web-based repository initialization and cloning instructions

## Requirements

- PHP ^8.4 (see `composer.json`)
- PHP extensions: pdo (ext-pdo), http (ext-http)
- Linux 6.17.*
- Composer
- Web server (Apache, Nginx, etc.)
- Git 2.43.*

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

3. Enable local SSL with the generated `phpgit.local` certificate files
```bash
   sudo a2enmod ssl
   sudo cp apache/phpgit.local.conf /etc/apache2/sites-available/phpgit.local.conf
   sudo a2ensite phpgit.local.conf
   echo "127.0.0.1 phpgit.local" | sudo tee -a /etc/hosts
   sudo systemctl reload apache2
```

If your project is not in `/home/x/PHPGit`, update `DocumentRoot`, `SSLCertificateFile`, and
`SSLCertificateKeyFile` inside `apache/phpgit.local.conf`.

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Azreyo/phpgit.git
   cd phpgit
   ```
   
2. Install php8.*
   ```bash
   sudo apt install php8.4-cli php8.4-fpm php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-opcache libapache2-mod-php
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
7. Run the installer script to set up the database and initial configuration:
   ```bash
   php installer.php
   ```
   The installer also asks for Apache `ServerName` and whether HTTPS should be enabled, then updates
   `apache/phpgit.local.conf` with your current project path and selected mode.

## Usage

- Access the application via your web browser at the configured URL.
- Register a new user or log in with existing credentials.
- Create and manage repositories from the dashboard.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.


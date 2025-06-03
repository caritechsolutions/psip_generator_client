#!/bin/bash

# EPG PSIP Client Installation Script
# This script installs the EPG PSIP Client with all dependencies

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
WEB_ROOT="/var/www/html"
EPG_PATH="$WEB_ROOT/epg"
DB_HOST="192.168.110.15"
DB_NAME="cariepg"
DB_USER="newroot2"
DB_PASS="Password!10"
NGINX_USER="www-data"

# Functions
print_header() {
    echo -e "${GREEN}============================================${NC}"
    echo -e "${GREEN}$1${NC}"
    echo -e "${GREEN}============================================${NC}"
}

print_error() {
    echo -e "${RED}ERROR: $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}WARNING: $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then 
        print_error "Please run as root (use sudo)"
        exit 1
    fi
}

# Get user inputs
get_user_inputs() {
    print_header "Configuration Setup"
    
    echo "Web files will be installed to: $EPG_PATH"
    echo ""
    echo "Database Configuration:"
    read -p "Database Host [$DB_HOST]: " input
    DB_HOST=${input:-$DB_HOST}
    
    read -p "Database Name [$DB_NAME]: " input
    DB_NAME=${input:-$DB_NAME}
    
    read -p "Database User [$DB_USER]: " input
    DB_USER=${input:-$DB_USER}
    
    read -sp "Database Password [$DB_PASS]: " input
    echo ""
    DB_PASS=${input:-$DB_PASS}
    
    echo ""
    echo "Configuration Summary:"
    echo "Installation Path: $EPG_PATH"
    echo "Database: $DB_USER@$DB_HOST/$DB_NAME"
    echo ""
    
    read -p "Continue with installation? (y/n): " confirm
    if [ "$confirm" != "y" ]; then
        echo "Installation cancelled"
        exit 0
    fi
}

# Install system dependencies
install_dependencies() {
    print_header "Installing System Dependencies"
    
    apt-get update
    apt-get install -y \
        git \
        g++ \
        make \
        libssl-dev \
        libcurl4-openssl-dev \
        libsrt-openssl-dev \
        libpcsclite-dev \
        libedit-dev \
        libusb-1.0-0-dev \
        dpkg-dev \
        python3 \
        python3-pip \
        python3-setuptools \
        dos2unix \
        doxygen \
        graphviz \
        build-essential \
        mysql-client
    
    print_success "System dependencies installed"
}

# Install PHP for existing Nginx
install_php() {
    print_header "Installing PHP for Nginx"
    
    # Check if Nginx is installed
    if ! command -v nginx &> /dev/null; then
        print_error "Nginx is not installed. Please install Nginx first."
        exit 1
    fi
    
    apt-get install -y \
        php-fpm \
        php-mysql \
        php-curl \
        php-gd \
        php-mbstring \
        php-xml \
        php-zip
    
    # Get PHP version
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    
    # Configure PHP-FPM
    systemctl start php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm
    
    print_success "PHP $PHP_VERSION installed"
    
    # Remind about nginx configuration
    print_warning "Ensure your Nginx configuration includes PHP support"
    print_warning "The generic socket path /var/run/php/php-fpm.sock should work"
}

# Install TSDuck
install_tsduck() {
    print_header "Installing TSDuck"
    
    # Always install to /root/tsduck
    TSDUCK_PATH="/root/tsduck"
    
    if [ -d "$TSDUCK_PATH" ]; then
        print_warning "TSDuck directory already exists at $TSDUCK_PATH"
        read -p "Remove and reinstall? (y/n): " confirm
        if [ "$confirm" == "y" ]; then
            rm -rf "$TSDUCK_PATH"
        else
            print_warning "Skipping TSDuck installation"
            # Find existing binary path
            if [ -d "$TSDUCK_PATH/bin" ]; then
                TSDUCK_BIN_PATH=$(find $TSDUCK_PATH/bin -type d -name "*x86_64*" | head -1)
                print_success "Found existing TSDuck binaries at: $TSDUCK_BIN_PATH"
            fi
            return
        fi
    fi
    
    # Clone TSDuck
    cd /root
    git clone https://github.com/tsduck/tsduck.git
    cd tsduck
    
    # Build TSDuck
    print_warning "Building TSDuck (this may take several minutes)..."
    make -j$(nproc)
    
    # Set up environment
    echo "source $TSDUCK_PATH/scripts/setenv.sh" >> /root/.bashrc
    
    # Find the actual binary path after build
    TSDUCK_BIN_PATH=$(find $TSDUCK_PATH/bin -type d -name "*x86_64*" | head -1)
    
    print_success "TSDuck installed"
    print_success "TSDuck binaries found at: $TSDUCK_BIN_PATH"
}

# Install web files
install_web_files() {
    print_header "Installing Web Files"
    
    # Create directories
    mkdir -p "$EPG_PATH"
    mkdir -p "$EPG_PATH/scripts"
    mkdir -p "$EPG_PATH/styles"
    mkdir -p "$EPG_PATH/transports"
    
    # Copy files from current directory
    cp -r web/* "$EPG_PATH/" 2>/dev/null || true
    cp *.php "$EPG_PATH/" 2>/dev/null || true
    
    # If web directory exists, copy from there
    if [ -d "web" ]; then
        cp -r web/scripts/* "$EPG_PATH/scripts/" 2>/dev/null || true
        cp -r web/styles/* "$EPG_PATH/styles/" 2>/dev/null || true
        cp web/*.html "$EPG_PATH/" 2>/dev/null || true
    fi
    
    # Update database configuration
    sed -i "s/\$dbHost = '.*'/\$dbHost = '$DB_HOST'/" "$EPG_PATH/epg_gen.php"
    sed -i "s/\$dbName = '.*'/\$dbName = '$DB_NAME'/" "$EPG_PATH/epg_gen.php"
    sed -i "s/\$dbUsername = '.*'/\$dbUsername = '$DB_USER'/" "$EPG_PATH/epg_gen.php"
    sed -i "s/\$dbPassword = '.*'/\$dbPassword = '$DB_PASS'/" "$EPG_PATH/epg_gen.php"
    
    # Update all file paths to use /var/www/html/epg
    find "$EPG_PATH" -name "*.php" -type f -exec sed -i "s|/var/www/html/transports|/var/www/html/epg/transports|g" {} \;
    
    # Update TSDuck binary paths
    if [ ! -z "$TSDUCK_BIN_PATH" ]; then
        # Update the actual TSDuck binary path
        find "$EPG_PATH" -name "*.php" -type f -exec sed -i "s|/root/tsduck/bin/release-x86_64-crane2|$TSDUCK_BIN_PATH|g" {} \;
        find "$EPG_PATH" -name "*.php" -type f -exec sed -i "s|/root/tsduck/tsduck/bin/release-x86_64-testmodulator|$TSDUCK_BIN_PATH|g" {} \;
    fi
    
    # Set permissions
    chown -R $NGINX_USER:$NGINX_USER "$EPG_PATH"
    chmod -R 755 "$EPG_PATH"
    chmod -R 777 "$EPG_PATH/transports"
    
    print_success "Web files installed"
}

# Configure sudoers for all commands
configure_sudoers() {
    print_header "Configuring Sudo Permissions"
    
    cat > /etc/sudoers.d/epg-psip << EOF
# EPG PSIP Client permissions
# Allow www-data to run all commands without password
$NGINX_USER ALL=(ALL) NOPASSWD: ALL
EOF

    chmod 440 /etc/sudoers.d/epg-psip
    
    print_success "Sudo permissions configured (ALL commands allowed for $NGINX_USER)"
}

# Test database connection
test_database() {
    print_header "Testing Database Connection"
    
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT COUNT(*) FROM services;" > /dev/null 2>&1; then
        print_success "Database connection successful"
    else
        print_error "Could not connect to database"
        print_warning "Please check your database settings"
        print_warning "You can update them later in: $EPG_PATH/epg_gen.php"
    fi
}

# Create test script
create_test_script() {
    cat > "$EPG_PATH/test_installation.php" << 'EOF'
<?php
echo "EPG PSIP Client Installation Test\n";
echo "=================================\n\n";

// Test PHP
echo "PHP Version: " . PHP_VERSION . "\n";

// Test MySQL extension
echo "MySQL Extension: " . (extension_loaded('mysqli') ? "OK" : "MISSING") . "\n";

// Test write permissions
$test_file = __DIR__ . "/transports/test.txt";
if (file_put_contents($test_file, "test") !== false) {
    echo "Write Permissions: OK\n";
    unlink($test_file);
} else {
    echo "Write Permissions: FAILED\n";
}

// Test sudo
$output = [];
exec("sudo whoami 2>&1", $output, $return);
echo "Sudo Access: " . ($return === 0 ? "OK (running as " . implode('', $output) . ")" : "FAILED") . "\n";

// Test TSDuck
$output = [];
exec("which tsp 2>&1", $output, $return);
echo "TSDuck in PATH: " . ($return === 0 ? "OK" : "NOT IN PATH") . "\n";

// Check actual TSDuck binary location
$tsduck_paths = glob("/root/tsduck/bin/*/tsp");
if (!empty($tsduck_paths)) {
    echo "TSDuck found at: " . $tsduck_paths[0] . "\n";
} else {
    echo "TSDuck binary not found in expected location\n";
}

// Test database connection
require_once(__DIR__ . '/epg_gen.php');
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
    echo "Database Connection: OK\n";
} catch (Exception $e) {
    echo "Database Connection: FAILED - " . $e->getMessage() . "\n";
}

echo "\nInstallation test complete.\n";
?>
EOF
    
    chmod 755 "$EPG_PATH/test_installation.php"
    chown $NGINX_USER:$NGINX_USER "$EPG_PATH/test_installation.php"
}

# Create path update script
create_path_update_script() {
    cat > "$EPG_PATH/update_paths.sh" << 'EOF'
#!/bin/bash
# Update TSDuck paths if binary location changes

EPG_PATH="/var/www/html/epg"
TSDUCK_BIN_PATH=$(find /root/tsduck/bin -type d -name "*x86_64*" | head -1)

if [ -z "$TSDUCK_BIN_PATH" ]; then
    echo "Error: Could not find TSDuck binary path"
    exit 1
fi

echo "Updating TSDuck paths to: $TSDUCK_BIN_PATH"

# Update all PHP files
find "$EPG_PATH" -name "*.php" -type f -exec sed -i "s|/root/tsduck/bin/[^/]*/|$TSDUCK_BIN_PATH/|g" {} \;

echo "Path update complete!"
EOF
    
    chmod +x "$EPG_PATH/update_paths.sh"
}

# Main installation flow
main() {
    clear
    echo -e "${GREEN}"
    echo "╔═══════════════════════════════════════╗"
    echo "║     EPG PSIP Client Installer         ║"
    echo "╚═══════════════════════════════════════╝"
    echo -e "${NC}"
    
    check_root
    get_user_inputs
    
    install_dependencies
    install_php
    install_tsduck
    install_web_files
    configure_sudoers
    test_database
    create_test_script
    create_path_update_script
    
    print_header "Installation Complete!"
    echo ""
    echo "Access the web interface at: http://$(hostname -I | awk '{print $1}')/epg/"
    echo ""
    echo "Test the installation: php $EPG_PATH/test_installation.php"
    echo ""
    if [ ! -z "$TSDUCK_BIN_PATH" ]; then
        echo "TSDuck binaries installed at: $TSDUCK_BIN_PATH"
    fi
    echo ""
    echo "If TSDuck path changes, run: $EPG_PATH/update_paths.sh"
    echo ""
    print_success "Installation completed successfully!"
}

# Run main function
main

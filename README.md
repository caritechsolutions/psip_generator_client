# EPG PSIP Client

ATSC PSIP (Program and System Information Protocol) table generator for digital TV systems. This client polls an EPG database and generates ATSC-compliant PSIP tables for broadcast.

## Features

- Generates ATSC PSIP tables (MGT, TVCT, STT, EIT, ETT)
- Web-based transport configuration interface
- Automatic EPG data polling from MySQL database
- Real-time STT (System Time Table) updates
- Multiple transport stream support
- Automatic table version management

## Requirements

- Ubuntu/Debian Linux
- PHP 7.4+ with MySQL support
- TSDuck compiled and installed
- MySQL/MariaDB database access
- Apache/Nginx web server
- sudo privileges

## Installation

1. Clone this repository:
```bash
git clone https://github.com/caritechsolutions/psip_generator_client.git
cd psip_generator_client


# EPG PSIP Client Installation Guide

## Overview
The EPG PSIP Client generates ATSC PSIP (Program and System Information Protocol) tables for digital TV systems by polling an EPG database.

## Prerequisites
- Ubuntu/Debian Linux server
- Nginx web server (already installed)
- MySQL/MariaDB database with EPG data
- Root access for installation

## Installation Steps

### 1. Download and Run Install Script
```bash
# Make the script executable
chmod +x install.sh

# Run as root
sudo ./install.sh
```

### 2. During Installation
The installer will:
- Prompt for database connection details
- Install system dependencies
- Install PHP-FPM for Nginx
- Build and install TSDuck
- Copy web files to `/var/www/html/epg/`
- Configure sudo permissions
- Test database connection

### 3. Post-Installation

#### Verify Nginx Configuration
Ensure your Nginx default site includes PHP support:
```nginx
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
}
```

#### Test Installation
```bash
php /var/www/html/epg/test_installation.php
```

#### Update Database Configuration (if needed)
```bash
chmod +x /var/www/html/epg/update_db_config.sh
sudo /var/www/html/epg/update_db_config.sh
```

## Usage

### Access Web Interface
Navigate to: `http://your-server-ip/epg/`

### Creating a Transport
1. Click "Add Transport" in the web interface
2. Configure:
   - Transport name
   - Channel lineup (channel number, name, source ID, etc.)
   - Output settings (UDP address and control port)
3. Save configuration

### Starting a Transport
1. Select the transport from the list
2. Click "On/Off Transport" button
3. The system will:
   - Generate EPG data from database
   - Start broadcasting PSIP tables via UDP

### Monitoring
- Check transport status in the web interface (green = running)
- View logs: `tail -f /var/www/html/epg/execution_log.txt`
- Test UDP output: `sudo tcpdump -i any -n udp port [your-port]`

## File Locations
- **Web Interface**: `/var/www/html/epg/`
- **Transport Configs**: `/var/www/html/epg/transports/[transport-name]/`
- **TSDuck Binaries**: `/root/tsduck/bin/`
- **Logs**: `/var/www/html/epg/execution_log.txt`

## Troubleshooting

### PHP Not Working
- Check PHP-FPM status: `systemctl status php*-fpm`
- Verify Nginx error log: `tail -f /var/log/nginx/error.log`

### Database Connection Failed
- Run: `/var/www/html/epg/update_db_config.sh`
- Verify MySQL is accepting remote connections
- Check firewall rules

### TSDuck Path Issues
- Run: `/var/www/html/epg/update_paths.sh`
- This updates all PHP files with the correct TSDuck binary path

### Transport Won't Start
- Check sudo permissions: `sudo -l -U www-data`
- Verify TSDuck is installed: `ls -la /root/tsduck/bin/*/tsp`
- Check PHP error log: `tail -f /var/log/php*.log`

## Database Requirements
The MySQL database must contain:
- `services` table with columns: `service_id`, `service_name`, `xmltv_id`
- `events` table with columns: `event_id`, `service_id`, `start_time`, `duration`, `event_name`, `event_desc`

## Support Files
- **install.sh** - Main installation script
- **update_db_config.sh** - Update database credentials
- **test_installation.php** - Verify installation
- **update_paths.sh** - Fix TSDuck paths if needed

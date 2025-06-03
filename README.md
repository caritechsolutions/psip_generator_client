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

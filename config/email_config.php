<?php
/**
 * Email Configuration for KATSS Admin Panel
 * Using PHPMailer for reliable email delivery
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');        // Your SMTP server
define('SMTP_PORT', 587);                      // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'muhiredieu7@gmail.com'); // Your email
define('SMTP_PASSWORD', 'muhire@2007');    // App-specific password
define('SMTP_ENCRYPTION', 'tls');              // tls or ssl
define('SMTP_AUTH', true);                     // Enable authentication
define('SMTP_DEBUG', 0);                       // 0=off, 1=client, 2=server

// Email Settings
define('MAIL_FROM_ADDRESS', 'noreply@katss.ac.rw');
define('MAIL_FROM_NAME', 'KATSS Admin Panel');
define('MAIL_REPLY_TO', 'admin@katss.ac.rw');
define('MAIL_REPLY_TO_NAME', 'KATSS Support');

// Alternative SMTP providers:
// 
// Gmail:
// SMTP_HOST = 'smtp.gmail.com'
// SMTP_PORT = 587
// SMTP_ENCRYPTION = 'tls'
// Use App Password: https://myaccount.google.com/apppasswords
//
// Outlook/Hotmail:
// SMTP_HOST = 'smtp.office365.com'
// SMTP_PORT = 587
// SMTP_ENCRYPTION = 'tls'
//
// Yahoo:
// SMTP_HOST = 'smtp.mail.yahoo.com'
// SMTP_PORT = 587
// SMTP_ENCRYPTION = 'tls'
//
// Hostinger:
// SMTP_HOST = 'smtp.hostinger.com'
// SMTP_PORT = 587
// SMTP_ENCRYPTION = 'tls'
//
// Namecheap:
// SMTP_HOST = 'mail.privateemail.com'
// SMTP_PORT = 587
// SMTP_ENCRYPTION = 'tls'
<?php
/**
 * Email Configuration File
 * 
 * IMPORTANT: This file contains sensitive information and should not be 
 * committed to any public repository.
 */

// Email server settings
$GLOBALS['email_server'] = [
    'enabled' => true,    // Set to false to disable email sending
    'method' => 'smtp',   // Options: 'mail' (PHP mail), 'smtp', 'log'
    
    // SMTP configuration (for Gmail)
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',  // Options: 'ssl', 'tls', false
        'auth' => true,
        'username' => 'luxsmith656@gmail.com', // Your email
        'password' => 'lxnfpqehppfhopgv', // Your app password
        'debug' => 0,     // Debug level: 0-4
    ],
    
    // Logging configuration (if method is 'log')
    'log' => [
        'path' => 'email_logs',
        'format' => 'html', // or 'text'
    ],
];

// Email content configuration
$GLOBALS['email_config'] = [
    'from_email' => 'luxsmith656@gmail.com', // Add your email here
    'from_name' => 'MWPD Filing System',
    'reply_to' => 'luxsmith656@gmail.com', // Add your email here
    'signature' => '<p>Thank you,<br>MWPD Administration</p>',
    'logo_url' => 'https://mwpd.gov.ph/wp-content/uploads/2022/10/DMW-Logo.png',
];

?>

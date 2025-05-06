<?php
/**
 * Simple PHPMailer replacement class for MWPD
 * This is a lightweight class to simulate PHPMailer functionality
 * without requiring the full library
 */
class PHPMailer {
    // Basic properties
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $ErrorInfo = '';
    public $Host = 'smtp.gmail.com';
    public $Port = 587;
    public $SMTPSecure = 'tls';
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $CharSet = 'UTF-8';
    public $SMTPDebug = 0;
    public $ContentType = 'text/html';
    
    // Recipients
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    
    /**
     * Set mailer to use SMTP
     */
    public function isSMTP() {
        // Just a placeholder - we'll handle SMTP differently
        return true;
    }
    
    /**
     * Set content type to HTML
     */
    public function isHTML($isHtml = true) {
        // Just a placeholder
        return true;
    }
    
    /**
     * Add a recipient
     */
    public function addAddress($address, $name = '') {
        $this->to[] = [$address, $name];
        return true;
    }
    
    /**
     * Set From email address and name
     */
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }
    
    /**
     * Add Reply-To address
     */
    public function addReplyTo($address, $name = '') {
        $this->ReplyTo[] = [$address, $name];
        return true;
    }
    
    /**
     * Send the email
     * This implementation uses direct SMTP connection to Gmail
     */
    public function send() {
        // Verify we have at least one recipient
        if (empty($this->to)) {
            $this->ErrorInfo = 'No recipients set';
            return false;
        }
        
        try {
            // For Gmail, port 587 requires TLS, not SSL
            // First connect without encryption
            $socket = fsockopen($this->Host, $this->Port, $errno, $errstr, 30);
            
            if (!$socket) {
                $this->ErrorInfo = "Could not connect to SMTP server: $errstr ($errno)";
                return false;
            }
            
            // Read server greeting
            $greeting = fgets($socket, 515);
            if (substr($greeting, 0, 3) != '220') {
                $this->ErrorInfo = "Invalid greeting from server: " . trim($greeting);
                fclose($socket);
                return false;
            }
            
            // Say EHLO to server
            fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $ehlo_response = fgets($socket, 515);
            if (substr($ehlo_response, 0, 3) != '250') {
                $this->ErrorInfo = "EHLO failed: " . trim($ehlo_response);
                fclose($socket);
                return false;
            }
            
            // Clear remaining EHLO response lines
            while (substr($ehlo_response, 3, 1) == '-') {
                $ehlo_response = fgets($socket, 515);
            }
            
            // Authenticate
            fputs($socket, "AUTH LOGIN\r\n");
            $auth_response = fgets($socket, 515);
            if (substr($auth_response, 0, 3) != '334') {
                $this->ErrorInfo = "AUTH failed: " . trim($auth_response);
                fclose($socket);
                return false;
            }
            
            // Send username
            fputs($socket, base64_encode($this->Username) . "\r\n");
            $user_response = fgets($socket, 515);
            if (substr($user_response, 0, 3) != '334') {
                $this->ErrorInfo = "Username rejected: " . trim($user_response);
                fclose($socket);
                return false;
            }
            
            // Send password
            fputs($socket, base64_encode($this->Password) . "\r\n");
            $pass_response = fgets($socket, 515);
            if (substr($pass_response, 0, 3) != '235') {
                $this->ErrorInfo = "Authentication failed: " . trim($pass_response);
                fclose($socket);
                return false;
            }
            
            // Set sender
            fputs($socket, "MAIL FROM:<{$this->From}>\r\n");
            $from_response = fgets($socket, 515);
            if (substr($from_response, 0, 3) != '250') {
                $this->ErrorInfo = "Sender rejected: " . trim($from_response);
                fclose($socket);
                return false;
            }
            
            // Set recipient(s)
            foreach ($this->to as $recipient) {
                fputs($socket, "RCPT TO:<{$recipient[0]}>\r\n");
                $rcpt_response = fgets($socket, 515);
                if (substr($rcpt_response, 0, 3) != '250') {
                    $this->ErrorInfo = "Recipient rejected: " . trim($rcpt_response);
                    fclose($socket);
                    return false;
                }
            }
            
            // Send message data
            fputs($socket, "DATA\r\n");
            $data_response = fgets($socket, 515);
            if (substr($data_response, 0, 3) != '354') {
                $this->ErrorInfo = "DATA command failed: " . trim($data_response);
                fclose($socket);
                return false;
            }
            
            // Format email headers
            $headers = "From: {$this->FromName} <{$this->From}>\r\n";
            $to_header = '';
            foreach ($this->to as $recipient) {
                if (!empty($to_header)) $to_header .= ', ';
                $to_header .= "{$recipient[1]} <{$recipient[0]}>";
            }
            $headers .= "To: $to_header\r\n";
            $headers .= "Subject: {$this->Subject}\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: {$this->ContentType}; charset={$this->CharSet}\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "\r\n";
            
            // Send the email
            fputs($socket, $headers . $this->Body . "\r\n.\r\n");
            $end_response = fgets($socket, 515);
            if (substr($end_response, 0, 3) != '250') {
                $this->ErrorInfo = "Message sending failed: " . trim($end_response);
                fclose($socket);
                return false;
            }
            
            // Quit SMTP session
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return true;
        } catch (Exception $e) {
            $this->ErrorInfo = "Exception: " . $e->getMessage();
            return false;
        }
    }
}

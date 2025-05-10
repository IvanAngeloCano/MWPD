<?php
session_start();
require_once 'connection.php';

// If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// If already consented, redirect to dashboard
if (isset($_SESSION['privacy_consent']) && $_SESSION['privacy_consent'] == 1) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['consent']) && $_POST['consent'] == 'agree') {
        try {
            // Update user in database
            $stmt = $pdo->prepare('UPDATE users SET privacy_consent = 1, first_login = 0 WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            
            // Update session
            $_SESSION['privacy_consent'] = 1;
            $_SESSION['first_login'] = 0;
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    } else {
        $error = 'You must agree to the privacy policy to continue.';
    }
}

$pageTitle = "Privacy Consent - MWPD Filing System";
include '_head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Consent - MWPD Filing System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
  <div style="position: relative;">
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 50%; background-color: #1976D2; z-index: 1;"></div>
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; height: 50%; background-color: #f5f5f5; z-index: 1;"></div>

    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; position: relative; z-index: 2;">
      <div style="background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; max-width: 800px; padding: 30px;">
        <img src="assets/images/DMW Logo.png" alt="DMW Logo" style="max-width: 150px; margin: 0 auto 20px; display: block;">
        <h2 style="color: #1976D2; margin-bottom: 20px; text-align: center;">Data Privacy Consent</h2>
        
        <div style="text-align: left; margin-top: 20px;">
          <p style="margin-bottom: 15px; line-height: 1.6;">Welcome to the MWPD Filing System. Before you proceed, please read and agree to our Data Privacy Policy.</p>
          
          <div style="background: #f9f9f9; border: 1px solid #eee; border-radius: 5px; padding: 20px; margin: 20px 0; max-height: 300px; overflow-y: auto;">
            <h3 style="color: #1976D2; margin: 0 0 15px; font-size: 1.2rem;">Data Privacy Act of 2012 (Republic Act No. 10173)</h3>
            <p style="margin-bottom: 15px; line-height: 1.6;">The Department of Migrant Workers (DMW) is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). This Privacy Policy outlines how we collect, use, disclose, and protect your personal information when you use our services.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">By using this system, you consent to the collection, use, storage, and processing of your personal information for the following purposes:</p>
            
            <ul style="margin-bottom: 15px; padding-left: 20px;">
              <li style="margin-bottom: 5px;">Processing and evaluating applications for overseas employment</li>
              <li style="margin-bottom: 5px;">Verifying identities and qualifications</li>
              <li style="margin-bottom: 5px;">Matching applicants with suitable employment opportunities</li>
              <li style="margin-bottom: 5px;">Communicating regarding applications and employment</li>
              <li style="margin-bottom: 5px;">Providing pre-departure orientation and training</li>
              <li style="margin-bottom: 5px;">Facilitating travel arrangements and visa processing</li>
              <li style="margin-bottom: 5px;">Providing welfare services and assistance to workers abroad</li>
              <li style="margin-bottom: 5px;">Processing insurance and benefits</li>
              <li style="margin-bottom: 5px;">Complying with legal and regulatory requirements</li>
              <li style="margin-bottom: 5px;">Generating statistical data for policy development</li>
            </ul>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">We collect personal information including but not limited to: full name, contact details, identification documents, employment history, educational background, skills and qualifications, medical information, financial information, and family information.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">Your information may be shared with relevant government agencies, foreign government agencies and embassies, employers, recruitment agencies, medical facilities, insurance providers, financial institutions, service providers, and legal representatives as necessary for processing your application or as required by law.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">Under the Data Privacy Act, you have the right to be informed, access your data, object to processing, request erasure or blocking, rectify inaccuracies, data portability, be indemnified for damages, and file complaints.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;">For any questions or concerns regarding your privacy, please contact our Data Protection Officer at dpo@dmw.gov.ph or (02) 8722-1144.</p>
            
            <p style="margin-bottom: 15px; line-height: 1.6;"><a href="privacy_policy.php" target="_blank" style="color: #1976D2; text-decoration: underline;">Read the full Privacy Policy</a></p>
          </div>
          
          <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin: 10px 0;">
              <?php echo $error; ?>
            </div>
          <?php endif; ?>
          
          <form method="POST" action="privacy_consent.php">
            <div style="margin: 20px 0; display: flex; align-items: center;">
              <input type="checkbox" id="consent" name="consent" value="agree" required style="margin-right: 10px;">
              <label for="consent" style="font-weight: 500;">I have read and agree to the Data Privacy Policy</label>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
              <button type="submit" style="background-color: #1976D2; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: 500; cursor: pointer;">Continue</button>
              <a href="logout.php" style="background-color: #f5f5f5; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 5px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; text-align: center;">Decline and Logout</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

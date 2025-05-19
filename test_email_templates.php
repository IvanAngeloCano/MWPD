<?php
/**
 * MWPD Email Template Test
 * 
 * This script demonstrates all the available email templates
 * and allows sending test emails using the fixed email sender.
 */

// Include required files
require_once 'email_templates.php';
require_once 'fixed_email_sender.php';

// Get base URL for links
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://" . $_SERVER['HTTP_HOST'];

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>MWPD Email Templates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #1a5276;
            border-bottom: 2px solid #1a5276;
            padding-bottom: 10px;
        }
        h2 {
            color: #2980b9;
            margin-top: 30px;
        }
        .container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .template-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }
        .template-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .template-preview {
            padding: 15px;
            max-height: 300px;
            overflow: auto;
            border-bottom: 1px solid #ddd;
        }
        .template-actions {
            padding: 15px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
        }
        iframe {
            border: 1px solid #ddd;
            width: 100%;
            height: 500px;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
        }
        .tab.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #1a5276;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #154360;
        }
        .success {
            color: green;
            font-weight: bold;
            padding: 15px;
            background-color: #d4edda;
            border-radius: 4px;
            margin: 15px 0;
        }
        .error {
            color: #721c24;
            font-weight: bold;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <h1>MWPD Email Template System</h1>
    
    <div class="container">
        <p>This tool allows you to preview and send test emails using the beautifully designed MWPD email templates.</p>
        <p>All templates are professionally designed, mobile-responsive, and integrate seamlessly with the MWPD Filing System.</p>
    </div>
    
    <div class="tabs">
        <div class="tab active" onclick="openTab(\'preview\')">Preview Templates</div>
        <div class="tab" onclick="openTab(\'send\')">Send Test Email</div>
        <div class="tab" onclick="openTab(\'integrate\')">Integration Guide</div>
    </div>
    
    <div id="preview" class="tab-content active">
        <h2>Available Email Templates</h2>';

// Generate examples of each template type
$templates = [
    [
        'name' => 'Password Reset Notification',
        'function' => 'create_password_reset_email',
        'args' => ['John Doe', 'john.doe', 'Temp123!', $base_url . '/login.php']
    ],
    [
        'name' => 'Account Approval',
        'function' => 'create_account_approval_email',
        'args' => ['Maria Garcia', 'maria.garcia', 'Secure456!', $base_url . '/login.php']
    ],
    [
        'name' => 'Account Rejection',
        'function' => 'create_account_rejection_email',
        'args' => ['Robert Smith', 'robert.smith', 'Insufficient documentation provided.']
    ],
    [
        'name' => 'Record Submission Notification',
        'function' => 'create_record_submission_email',
        'args' => ['Sarah Johnson', 'Direct Hire', 'DH-2025-001', 'Juan Santos - Household Worker', 'Michael Wilson', $base_url . '/direct_hire_view.php?id=DH-2025-001']
    ],
    [
        'name' => 'Record Approved',
        'function' => 'create_record_status_email',
        'args' => ['Carlos Rodriguez', 'Direct Hire', 'DH-2025-002', 'Ana Reyes - Professional Worker', 'Approved', 'All documents are in order.', $base_url . '/direct_hire_view.php?id=DH-2025-002']
    ],
    [
        'name' => 'Record Rejected',
        'function' => 'create_record_status_email',
        'args' => ['David Lee', 'Balik Manggagawa', 'BM-2025-003', 'Maria Santos', 'Rejected', 'Missing required documentation. Please resubmit with complete files.', $base_url . '/balik_manggagawa_view.php?id=BM-2025-003']
    ],
    [
        'name' => 'General Notification',
        'function' => 'create_notification_email',
        'args' => ['Emma Thompson', 'System Maintenance Notice', '<p>The MWPD Filing System will be undergoing maintenance on June 1, 2025 from 10:00 PM to 2:00 AM.</p><p>During this time, the system will be unavailable. Please plan your work accordingly.</p>', 'Learn More', $base_url . '/maintenance.php']
    ]
];

// Display template previews
foreach ($templates as $index => $template) {
    $html = call_user_func_array($template['function'], $template['args']);
    
    echo '
        <div class="template-container">
            <div class="template-header">' . htmlspecialchars($template['name']) . '</div>
            <div class="template-preview">
                <iframe id="preview-frame-' . $index . '" srcdoc="' . htmlspecialchars($html) . '"></iframe>
            </div>
            <div class="template-actions">
                <button onclick="sendTestEmail(' . $index . ')">Send Test Email</button>
                <button onclick="viewFullTemplate(' . $index . ')">View Full Template</button>
            </div>
        </div>';
}

echo '
    </div>
    
    <div id="send" class="tab-content">
        <h2>Send Test Email</h2>
        <div class="container">
            <form method="post" action="" id="test-email-form">
                <input type="hidden" name="action" value="send_test">
                
                <label for="recipient_email">Recipient Email:</label>
                <input type="email" id="recipient_email" name="recipient_email" required>
                
                <label for="template_type">Email Template:</label>
                <select id="template_type" name="template_type" required>
                    <option value="">Select a template</option>';

// Add options for each template
foreach ($templates as $index => $template) {
    echo '<option value="' . $index . '">' . htmlspecialchars($template['name']) . '</option>';
}

echo '            </select>
                
                <button type="submit">Send Test Email</button>
            </form>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    $recipient_email = isset($_POST['recipient_email']) ? $_POST['recipient_email'] : '';
    $template_index = isset($_POST['template_type']) ? (int)$_POST['template_type'] : -1;
    
    if (!empty($recipient_email) && isset($templates[$template_index])) {
        $template = $templates[$template_index];
        $html = call_user_func_array($template['function'], $template['args']);
        
        // Send email using our fixed sender
        $result = send_gmail_email(
            $recipient_email, 
            'MWPD Test: ' . $template['name'], 
            $html
        );
        
        if ($result) {
            echo '<div class="success">Email sent successfully to ' . htmlspecialchars($recipient_email) . '</div>';
        } else {
            echo '<div class="error">Failed to send email. Please check the server logs for details.</div>';
        }
    }
}

echo '
        </div>
    </div>
    
    <div id="integrate" class="tab-content">
        <h2>Integrating Templates with MWPD</h2>
        <div class="container">
            <p>To integrate these professional email templates with the MWPD system, follow these steps:</p>
            
            <h3>Step 1: Include the Template Library</h3>
            <p>Add the following line at the top of your PHP files that send emails:</p>
            <pre>require_once \'email_templates.php\';</pre>
            
            <h3>Step 2: Update the sendPasswordResetEmail Function</h3>
            <p>In email_notifications.php, update the sendPasswordResetEmail function:</p>
            <pre>
function sendPasswordResetEmail($to, $full_name, $username, $temp_password) {
    // Generate the professional email
    $html_content = create_password_reset_email($full_name, $username, $temp_password);
    
    // Send using the system\'s email function
    return sendEmail($to, "MWPD System - Password Reset", $html_content, "password_reset");
}</pre>
            
            <h3>Step 3: Update Other Email Functions</h3>
            <p>Follow the same pattern for each email type in your system:</p>
            <ul>
                <li>Account approval emails</li>
                <li>Record submission notifications</li>
                <li>Approval status updates</li>
                <li>General notifications</li>
            </ul>
            
            <h3>Step 4: Test All Email Types</h3>
            <p>After integration, use this tool to test each email type to ensure they are working correctly.</p>
        </div>
    </div>
    
    <script>
        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content
            document.getElementById(tabName).classList.add("active");
            
            // Add active class to the clicked tab
            const activeTabElements = document.querySelectorAll(`.tab[onclick="openTab(\'${tabName}\')"]`);
            for (let i = 0; i < activeTabElements.length; i++) {
                activeTabElements[i].classList.add("active");
            }
        }
        
        function sendTestEmail(templateIndex) {
            document.getElementById("template_type").value = templateIndex;
            openTab("send");
            window.scrollTo(0, document.getElementById("test-email-form").offsetTop);
        }
        
        function viewFullTemplate(templateIndex) {
            const iframe = document.getElementById("preview-frame-" + templateIndex);
            const win = window.open("", "TemplatePreview", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=800,height=600");
            win.document.body.innerHTML = iframe.srcdoc;
        }
    </script>
</body>
</html>';
?>

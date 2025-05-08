<?php
require_once 'connection.php';
require_once 'session.php';

// Get the memo ID or reference from the URL
$memo_reference = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (empty($memo_reference)) {
    echo "<p>Error: No memo reference provided</p>";
    exit;
}

try {
    // Get the endorsed records for this memo
    $stmt = $pdo->prepare("
        SELECT g.g2g, g.last_name, g.first_name, g.middle_name, g.passport_number, 
               g.remarks, g.employer, g.memo_reference, g.endorsement_date
        FROM gov_to_gov g
        WHERE g.memo_reference = ? AND g.remarks = 'Endorsed'
        ORDER BY g.last_name, g.first_name
    ");
    $stmt->execute([$memo_reference]);
    $endorsed_workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($endorsed_workers)) {
        echo "<p>No endorsed workers found for memo reference: $memo_reference</p>";
        exit;
    }
    
    // Get sample record for employer info
    $employer = $endorsed_workers[0]['employer'];
    $endorsement_date = !empty($endorsed_workers[0]['endorsement_date']) ? 
                       date('F d, Y', strtotime($endorsed_workers[0]['endorsement_date'])) : 
                       date('F d, Y');
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gov-to-Gov Memo: <?php echo htmlspecialchars($memo_reference); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .memo-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .memo-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .memo-header h1 {
            margin-bottom: 5px;
            color: #333;
        }
        .memo-header h2 {
            margin-top: 0;
            color: #555;
            font-weight: normal;
        }
        .memo-content {
            line-height: 1.6;
        }
        .memo-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .memo-table th, .memo-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .memo-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .memo-footer {
            margin-top: 50px;
            text-align: center;
        }
        .memo-signature {
            margin-top: 70px;
            border-top: 1px solid #999;
            width: 300px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }
        .memo-buttons {
            margin-top: 30px;
            text-align: center;
        }
        .memo-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            margin: 0 10px;
            border-radius: 4px;
        }
        .memo-button.print {
            background-color: #28a745;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .memo-container {
                box-shadow: none;
                padding: 0;
            }
            .memo-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="memo-container">
        <div class="memo-header">
            <h1>MEMORANDUM OF ENDORSEMENT</h1>
            <h2>Reference: <?php echo htmlspecialchars($memo_reference); ?></h2>
        </div>
        
        <div class="memo-content">
            <p><strong>Date:</strong> <?php echo $endorsement_date; ?></p>
            <p><strong>Subject:</strong> Endorsement of Overseas Filipino Workers</p>
            <p><strong>Employer:</strong> <?php echo htmlspecialchars($employer); ?></p>
            
            <p>This is to certify that the following Gov-to-Gov workers have been endorsed:</p>
            
            <table class="memo-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Passport Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($endorsed_workers as $index => $worker): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($worker['last_name'] . ', ' . $worker['first_name'] . ' ' . $worker['middle_name']); ?></td>
                        <td><?php echo htmlspecialchars($worker['passport_number']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>This endorsement is valid as of the date indicated above.</p>
        </div>
        
        <div class="memo-footer">
            <div class="memo-signature">
                <p>Authorized Signatory</p>
                <p>MWPD Office</p>
            </div>
        </div>
        
        <div class="memo-buttons">
            <a href="javascript:window.print()" class="memo-button print">Print Memo</a>
            <a href="gov_to_gov.php?tab=endorsed" class="memo-button">Return to Gov-to-Gov</a>
        </div>
    </div>
</body>
</html>

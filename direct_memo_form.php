<?php
include 'session.php';
require_once 'connection.php';

// Check if IDs were passed
if (!isset($_POST['selected_ids']) || empty($_POST['selected_ids'])) {
    echo "<p>Error: No applicants selected. Please go back and select at least one applicant.</p>";
    echo "<p><a href='gov_to_gov.php'>Return to Gov to Gov</a></p>";
    exit;
}

$selected_ids = $_POST['selected_ids'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Memorandum</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .selected-list {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generate Memorandum</h1>
        
        <div class="selected-list">
            <h3>Selected Applicants:</h3>
            <ul>
                <?php
                // Display selected applicants
                try {
                    // Create placeholders for the query
                    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                    
                    // Fetch the selected applicants
                    $stmt = $pdo->prepare("SELECT g2g, last_name, first_name FROM gov_to_gov WHERE g2g IN ($placeholders)");
                    $stmt->execute($selected_ids);
                    $applicants = $stmt->fetchAll();
                    
                    foreach ($applicants as $applicant) {
                        echo "<li>" . htmlspecialchars($applicant['last_name'] . ', ' . $applicant['first_name']) . "</li>";
                    }
                } catch (PDOException $e) {
                    echo "<li>Error loading applicant details: " . htmlspecialchars($e->getMessage()) . "</li>";
                }
                ?>
            </ul>
        </div>
        
        <form action="generate_memo.php" method="POST">
            <!-- Pass the selected IDs as hidden inputs -->
            <?php foreach ($selected_ids as $id): ?>
                <input type="hidden" name="selected_ids[]" value="<?= htmlspecialchars($id) ?>">
            <?php endforeach; ?>
            
            <div class="form-group">
                <label for="employer">Employer:</label>
                <input type="text" id="employer" name="employer" required>
            </div>
            
            <div class="form-group">
                <label for="memo_date">Memo Date:</label>
                <input type="date" id="memo_date" name="memo_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Generate Memo</button>
                <a href="gov_to_gov.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

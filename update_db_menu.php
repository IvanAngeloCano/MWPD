<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update Menu</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 { color: #246EE9; }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            color: #333;
        }
        .btn {
            display: inline-block;
            background-color: #246EE9;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #1a54b8;
        }
        .description {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>MWPD Database Update Menu</h1>
    
    <div class="card">
        <h2>Balik Manggagawa Database Update</h2>
        <div class="description">
            <p>This update will standardize the status values in the Balik Manggagawa database to use:</p>
            <ul>
                <li><strong>Pending</strong> - For records awaiting approval</li>
                <li><strong>Approved</strong> - For approved records</li>
                <li><strong>Declined</strong> - For declined records</li>
            </ul>
            <p>It will also ensure the remarks column exists and set default remarks for approved/declined records.</p>
        </div>
        <a href="update_bm_database.php" class="btn">Run Balik Manggagawa Update</a>
    </div>
    
    <p><a href="index.php">Return to Dashboard</a></p>
</body>
</html>

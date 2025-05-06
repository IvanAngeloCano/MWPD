# Files to delete
$filesToDelete = @(
    # Debug/Log Files
    "approval_debug_log.txt",
    "approval_detail_debug.txt",
    "approval_error_log.txt", 
    "debug_log.txt",
    "clearance_approval_debug.txt",
    "clearance_generation_debug.txt",
    "bm_debug_log.txt",
    "email_detailed_log.txt",
    "email_error_log.txt",
    "email_log.txt",
    "email_test_log.txt",
    "system_log.txt",
    
    # Temporary/Test Files
    "test_email.php",
    "test_email_template.php",
    "test_foolproof_email.php",
    "test_gmail.php",
    "email_test.php",
    "foolproof_mailer.php",
    "direct_mailer.php",
    "check_bm_table.php",
    "temp_check_bm_table.php",
    
    # Database Creation/Update Scripts
    "add_created_at_column.php",
    "add_email_column.php",
    "add_status_column.php",
    "add_status_to_bm.php",
    "create_account_approval_table.php",
    "create_account_approvals_table.php",
    "create_blacklist_db.php",
    "create_blacklist_table.php",
    "fix_all_columns.php",
    "fix_bm_table.php",
    "fix_database.php",
    "update_bm_database.php",
    "update_g2g_database.php",
    "sql_commands.txt"
)

# Count deleted files
$deletedCount = 0

# Delete each file if it exists
foreach ($file in $filesToDelete) {
    if (Test-Path $file) {
        Write-Host "Deleting $file..."
        Remove-Item $file -Force
        $deletedCount++
    }
}

Write-Host "Cleanup complete. Deleted $deletedCount files."

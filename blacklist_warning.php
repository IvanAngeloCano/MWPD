<?php
/**
 * Include this file in all forms where a person's information is being entered 
 * to display a warning if they are blacklisted.
 */

// Make sure the blacklist_check.php is included
require_once 'blacklist_check.php';

// Display the blacklist warning if it exists
if (!empty($blacklist_warning)) {
    echo $blacklist_warning;
    
    // Also include a JavaScript alert for extra emphasis
    if (!empty($blacklist_record)) {
        echo generateBlacklistAlert($blacklist_record);
    }
}
?>

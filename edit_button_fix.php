<?php
// This is a code snippet showing how to update the edit button in balik_manggagawa.php
// Replace the existing edit button code with this:

// Original code:
// <a href='javascript:void(0)' onclick='openUpdateModal(" . $row['bmid'] . ")' title='Edit Record'>
//   <i class='fa fa-edit'></i>
// </a>

// New code to use:
// <a href='balik_manggagawa_edit.php?bmid=" . $row['bmid'] . "' class='edit-button' title='Edit Record'>
//   <i class='fa fa-edit'></i>
// </a>

// This change removes the JavaScript modal function and directly links to the edit page
?>

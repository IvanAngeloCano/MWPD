<?php
// =====================================================================
// COPY THIS CODE BLOCK into your balik_manggagawa.php file
// Replace the existing table rows code with this updated version
// =====================================================================

try {
  $stmt = $pdo->query("SELECT bmid, last_name, given_name, middle_name, sex, address, destination, remarks FROM BM ORDER BY bmid");
  if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch()) {
      echo "<tr>";
      
      echo "<td>
              <div class='action-icons'>
                <a href='javascript:void(0)' onclick='openGenerateModal(" . $row['bmid'] . ")' title='Generate Documents'>
                  <i class='fa fa-file-export'></i> Generate
                </a>
              </div>
            </td>
            <td>
              <a href='balik_manggagawa_edit.php?bmid=" . $row['bmid'] . "' class='edit-button' title='Edit Record'>
                <i class='fa fa-edit'></i>
              </a>
              <button class='delete-button' onclick='deleteRecord(" . $row['bmid'] . ")' title='Delete Record'>
                <i class='fa fa-trash'></i>
              </button>
            </td>";

      echo "<td>" . htmlspecialchars($row['bmid']) . "</td>";
      echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
      echo "<td>" . htmlspecialchars($row['given_name']) . "</td>";
      echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
      echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
      echo "<td>" . htmlspecialchars($row['address']) . "</td>";
      echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
      echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
      echo "</tr>";
    }
  } else {
    echo "<tr><td colspan='9' class='text-center'>No data found.</td></tr>";
  }
} catch (PDOException $e) {
  echo "<tr><td colspan='9'>Error: " . $e->getMessage() . "</td></tr>";
}
?>

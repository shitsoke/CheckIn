<?php
// name_helper.php - helper to present display name when available
function display_name_from_row(array $row) {
  // prefer display_name from profiles if present
  if (array_key_exists('display_name', $row) && !empty($row['display_name'])) return $row['display_name'];
  // otherwise use first + (middle) + last
  $parts = [];
  if (!empty($row['first_name'])) $parts[] = $row['first_name'];
  if (!empty($row['middle_name'])) $parts[] = $row['middle_name'];
  if (!empty($row['last_name'])) $parts[] = $row['last_name'];
  return trim(implode(' ', $parts));
}

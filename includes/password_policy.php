<?php
// password_policy.php - simple password strength checks
function validate_password_strength(string $pw) {
  // minimum length
  if (strlen($pw) < 8) return 'Password must be at least 8 characters long.';
  // common weak passwords (small list)
  $weak = ['12345678','password','qwerty','abcdefgh','11111111','00000000','123456789'];
  foreach ($weak as $w) if (stripos($pw, $w) !== false) return 'That password is too common or easy to guess.';
  // repeated characters (aaaa1111)
  if (preg_match('/(.)\1{3,}/', $pw)) return 'Password has repeated characters, choose a less predictable one.';
  // simple ascending sequences (0123, 1234 etc)
  if (preg_match('/0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef/i', $pw)) return 'Avoid simple sequences like 1234 or abcd.';
  // require at least two character classes: lower, upper, digit, special
  $classes = 0;
  if (preg_match('/[a-z]/', $pw)) $classes++;
  if (preg_match('/[A-Z]/', $pw)) $classes++;
  if (preg_match('/[0-9]/', $pw)) $classes++;
  if (preg_match('/[^a-zA-Z0-9]/', $pw)) $classes++;
  if ($classes < 2) return 'Password should include at least two of: lowercase, uppercase, numbers, or symbols.';
  return true;
}

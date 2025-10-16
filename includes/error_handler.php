<?php
// Centralized error and exception handler
// Call this early on pages to catch fatal errors and show a friendly error page.
function check_and_render_fatal() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // log and display
        $msg = "[FATAL] " . ($err['message'] ?? '') . " in " . ($err['file'] ?? '') . " on line " . ($err['line'] ?? '');
        @file_put_contents(__DIR__ . '/../logs/errors.log', date('c') . " " . $msg . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        if (!headers_sent()) header('Location: /checkin/error.php?code=500');
        else echo "<p>Fatal error occurred. Please check the logs.</p>";
        exit;
    }
}

set_exception_handler(function($ex){
    @file_put_contents(__DIR__ . '/../logs/errors.log', date('c') . " Uncaught Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    if (!headers_sent()) header('Location: /checkin/error.php?code=500');
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline){
    // convert to exception for uniform handling
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

register_shutdown_function('check_and_render_fatal');

?>

<?php
// Redirect to the file's new location
header('Location: /tests/minimal_login_test.html');
// Fallback if headers already sent
echo '<script>window.location.href = "/tests/minimal_login_test.html";</script>';
echo 'This file has been moved to <a href="/tests/minimal_login_test.html">/tests/minimal_login_test.html</a>';
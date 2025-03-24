<?php
// Redirect to the file's new location
header('Location: /tests/test_login_form_final.html');
// Fallback if headers already sent
echo '<script>window.location.href = "/tests/test_login_form_final.html";</script>';
echo 'This file has been moved to <a href="/tests/test_login_form_final.html">/tests/test_login_form_final.html</a>';
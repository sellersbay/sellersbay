<?php
// Redirect to the file's new location
header('Location: /tests/dashboard_output.html');
// Fallback if headers already sent
echo '<script>window.location.href = "/tests/dashboard_output.html";</script>';
echo 'This file has been moved to <a href="/tests/dashboard_output.html">/tests/dashboard_output.html</a>';
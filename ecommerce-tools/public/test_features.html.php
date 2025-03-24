<?php
// Redirect to the file's new location
header('Location: /tests/test_features.html');
// Fallback if headers already sent
echo '<script>window.location.href = "/tests/test_features.html";</script>';
echo 'This file has been moved to <a href="/tests/test_features.html">/tests/test_features.html</a>';
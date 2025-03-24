<?php
// This file has been moved to the dev_tools directory
// This redirect ensures backward compatibility

// Add a log entry to track usage (optional)
error_log('simple_debug.php accessed directly - using redirect: ' . date('Y-m-d H:i:s'));

// Include the actual file from its new location
require_once __DIR__ . '/dev_tools/simple_debug.php';
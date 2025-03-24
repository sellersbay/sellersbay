<?php
// Simple login form that submits directly to the Symfony login endpoint
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RoboSEO Test Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding-top: 40px; 
            padding-bottom: 40px;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .card { 
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">RoboSEO Test Login</h3>
                </div>
                <div class="card-body">
                    <!-- Use method="post" to match Symfony's security expectations -->
                    <form action="/login" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" value="sellersbay@gmail.com" name="_username" id="email" class="form-control" required>
                            <div class="form-text">Default test email: sellersbay@gmail.com</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" value="powder04" name="_password" id="password" class="form-control" required>
                            <div class="form-text">Default test password: powder04</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="_remember_me" id="remember_me" class="form-check-input">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                        
                        <!-- CSRF Token -->
                        <?php
                        // Generate a simple CSRF token if needed
                        $csrfToken = bin2hex(random_bytes(32));
                        $_SESSION['csrf_token'] = $csrfToken;
                        ?>
                        <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Sign in</button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <strong>Note:</strong> This form will attempt to log in to the Symfony application.
                            If successful, you should be redirected to the dashboard.
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/" class="btn btn-outline-secondary btn-sm">Home</a>
                        <a href="/test_dashboard.php" class="btn btn-outline-primary btn-sm">View Demo Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
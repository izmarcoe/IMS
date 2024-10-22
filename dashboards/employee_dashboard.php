<?php
// Modified employee_dashboard.php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Strengthen session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Employee Dashboard</title> <!-- Change title for other dashboards -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script>
        // Replace the current history state
        window.history.replaceState({ page: 'dashboard' }, '', '');
        
        // Add a new history entry
        window.history.pushState({ page: 'dashboard' }, '', '');
        
        // Handle back button
        window.addEventListener('popstate', function(event) {
            // If trying to go back, push forward again
            window.history.pushState({ page: 'dashboard' }, '', '');
            
            // Refresh the page to ensure latest state
            window.location.reload();
        });

        // Ensure page is fresh on load
        if (performance.navigation.type === 2) {
            location.reload(true);
        }

        // Periodic session check
        async function checkSession() {
            try {
                const response = await fetch('../endpoint/check_session.php');
                const data = await response.json();
                if (!data.logged_in || data.role !== 'employee') {
                    window.location.href = 'http://localhost/IMS/';
                }
            } catch (error) {
                console.error('Session check failed:', error);
            }
        }

        // Check session every 30 seconds
        setInterval(checkSession, 30000);

        // Check session on page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                checkSession();
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Welcome to the Employee Dashboard</h1> <!-- Change for other dashboards -->
        <p>Hello, <?php echo htmlspecialchars($user_id); ?>!</p>
        <p>Your role: <?php echo htmlspecialchars($user_role); ?></p> <!-- This should now display the correct role -->
        
        <!-- Add role-specific content here -->

        <a class="btn btn-dark" href="../endpoint/logout.php">Logout</a>
    </div>
</body>
    <script>
        // Add this to your dashboard pages
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</html>

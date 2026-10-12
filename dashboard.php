<?php

session_start();

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Get the logged-in user's information
$fullname = $_SESSION["fullname"];
$email = $_SESSION["email"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Starian Hotel</title>

    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <header>
        <h1>Starian <span>Hotel</span></h1>

        <a href="logout.php">Logout</a>
    </header>

    <main>

        <section class="welcome">

            <h2>
                Welcome back,
                <?php echo htmlspecialchars($fullname); ?>!
            </h2>

            <p>
                Your comfort, our priority.
                Enjoy your stay with us.
            </p>

        </section>

        <section class="customer-info">

            <h2>Your Account</h2>

            <p>
                <strong>Full Name:</strong>
                <?php echo htmlspecialchars($fullname); ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo htmlspecialchars($email); ?>
            </p>

        </section>

    </main>

</body>

</html>
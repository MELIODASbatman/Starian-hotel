<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirmpassword = $_POST["confirmpassword"];

    if ($password !== $confirmpassword) {
        die("Passwords do not match.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters.");
    }

    $checkEmail = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();

    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        die("This email is already registered.");
    }

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $sql = $conn->prepare(
        "INSERT INTO users (fullname, email, phone, password)
         VALUES (?, ?, ?, ?)"
    );

    $sql->bind_param(
        "ssss",
        $fullname,
        $email,
        $phone,
        $hashedPassword
    );

    if ($sql->execute()) {

        echo "Account created successfully!";

    } else {

        echo "Something went wrong. Please try again.";

    }

    $sql->close();
    $checkEmail->close();
    $conn->close();

} else {

    echo "Invalid request.";

}

?>
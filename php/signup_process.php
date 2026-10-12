<?php

require_once "db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmpassword = $_POST["confirmpassword"] ?? "";

    // check that all fields are filled
    if (
        empty($fullname) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirmpassword)
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Please fill in all fields."
        ]);
        exit;
    }

    // check password

    if ($password !== $confirmpassword) {
        echo json_encode([
            "success" => false,
            "message" => "Passwords do not match."
        ]);
        exit;
    }


    // check password length
    if (strlen($password) < 8) {
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least 8 characters."
        ]);
        exit;
    }

    // check if email is already registered
    $checkEmail = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();

    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email is already registered."
        ]);
        exit;
    }


// hash the password
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // insert user into database
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

    // save the user and return a success message
    if ($sql->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Account created successfully!"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Something went wrong. Please try again."
        ]);

    }

    $sql->close();
    $checkEmail->close();
    $conn->close();
?>
<?php
    require_once 'db_connect.php';
    session_start();
    function sessionCheckLog(){
        if(isset($_SESSION['user'])){
            header('location: index.html');
        }
    }
    sessionCheckLog();

    function loginCheck(){
        if(isset($_POST['username'])){
            $user = sanitizeString($_POST['username']);
            $inputPass = sanitizeString($_POST['password']);
            $result = queryMySQL("SELECT username, passwrd, id FROM Users WHERE username='$user'");
            if ($result->num_rows == 0) {
                echo "<p class='errormsg'>användaren finns inte i databasen</p>";
            }
            else {
                $row = $result->fetch_assoc();
                $hashedPass = $row['passwrd'];
                if (password_verify($inputPass, $hashedPass)) {
                    $_SESSION['user'] = $user;
                    $_SESSION['userID'] = $row['id'];
                    header('location: index.html');
                }
                else{
                    echo "<p class='errorMsg'>lösenordet är skrivet fel</p>";
                }
            }
        }
    }
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logga in</title>
    <link rel="stylesheet" href="https://melab.lnu.se/~hh223ji/uppgift/public/css/style.css">
    <link rel="stylesheet" href="https://melab.lnu.se/~hh223ji/uppgift/public/css/responsive.css">
</head>
<body id="loginpage">
    <main>
        <form method="POST">
            <label for="user">Användarnamn: </label>
            <input type="text" id="user" name="username" required><br>
            <label for="pass">Lösenord: </label>
            <input type="password" id="pass" name="password" required>
            <input type="submit" value="Logga in">
        </form>
        <a href="register.php">Registrera Ny Användare</a>
        <?php loginCheck();?>
    </main>
</body>

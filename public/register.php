<?php
    require_once "db_connect.php";
    session_start();
    function sessionCheckLog(){
        if(isset($_SESSION['user'])){
            header("location: index.html");
        }
    }
    sessionCheckLog();

    function checkInfo(){
        if(isset($_POST['username'])){
            $user = sanitizeString($_POST['username']);
            $pass = sanitizeString($_POST["password"]);
            $result = queryMySQL("SELECT username, passwrd, id FROM Users WHERE username='$user'");
            if ($result->num_rows == 0) {
                $hashedpass = password_hash($pass, PASSWORD_DEFAULT);
                queryMySQL("INSERT INTO Users (username, passwrd) VALUES('$user', '$hashedpass')");
                
                $userID = queryMySQL("SELECT LAST_INSERT_ID() AS id");
                $row = $userID->fetch_assoc();
                
                $_SESSION['user'] = $user;
                $_SESSION['userID'] = $row['id'];
                
                header("location: index.html");
            }
                else {
                    echo "<p class='errormsg'>Användaren finns redan i databasen.</p>";
            }
        }
    }
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrera Ny Användare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body id="loginpage">
    <main>
        <form method="POST">
            <label for="user">Användarnamn: </label>
            <input type="text" id="user" name="username" required><br>
            <label for="pass">Lösenord: </label>
            <input type="password" id="pass" name="password" required>
            <input type="submit" value="Registrera">
        </form>
        <a href="login.php">Har redan en användare?</a>
        <?php checkInfo();?>
    </main>
</body>
<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

require '../vendor/autoload.php';
require "db_connect.php";
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

session_start();
$app = new \Slim\App();

//Hämta alla listor
$app->get('/lists', function ($request, $response) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $id = $_SESSION['userID'];    $lists = [];
    $result = queryMySQL("SELECT Lists.*, Users.username FROM Lists JOIN Users ON Lists.ownerid = Users.id JOIN ListMembers ON Lists.id = ListMembers.listId WHERE ListMembers.userId = $id ORDER BY Lists.creation_date DESC");

    while ($row = $result->fetch_assoc()) {
        $lists[] = $row;
    }
    return $response->withJson($lists);
});

// Skapa en ny lista
$app->post('/lists', function ($request, $response) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    global $conn;
    $data = $request->getParsedBody();
    $listName = sanitizeString($data['name']);
    $id = $_SESSION['userID'];
    $token = bin2hex(random_bytes(16));

    $insertQuery = "INSERT INTO Lists (ownerid, name, token) VALUES ('$id', '$listName', '$token')";
    $result = queryMySQL($insertQuery);

    if ($result) {
        $listID = $conn->insert_id;
        queryMySQL("INSERT INTO ListMembers (listId, userId) VALUES ('$listID', '$id')");
        return $response->withJson(["listId" => $listID]);
    }
    return $response->withJson(["error" => "Failed to insert list"], 500);
});

// Ta bort en lista
$app->post('/lists/{id}', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    $result = queryMySQL("SELECT * FROM Lists WHERE id = $listId AND ownerid = $userId");
    if (!$result || $result->num_rows === 0) {
        return $response->withJson(["error" => "You do not have permission to delete this list"], 403);
    }
    $deleteResult = queryMySQL("DELETE FROM Lists WHERE id = $listId");
    if ($deleteResult) {
        return $response->withJson(["success" => true]);
    } else {
        return $response->withJson(["error" => "Failed to delete list"], 500);
    }
});

//Rendera list.html filen
$app->get('/lists/{id}', function (Request $request, Response $response, $args) {
    $htmlFilePath = __DIR__ . '/html/list.html';
    if (file_exists($htmlFilePath)) {
        $htmlContent = file_get_contents($htmlFilePath);
        $response->getBody()->write($htmlContent);
        return $response;
    } else {
        return $response->withJson(["error" => "HTML file not found"], 404);
    }
});

//Hämta information om specifik lista
$app->get('/lists/{id}/data', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if($result->num_rows > 0){
        $result = queryMySQL("SELECT Lists.*, Users.username FROM Lists JOIN Users ON Lists.ownerid = Users.id WHERE Lists.id = '$listId'");
        if ($result->num_rows > 0) {
            $listData = $result->fetch_assoc();
            return $response->withJson($listData);
        } else {
            return $response->withJson(["error" => "List not found"], 404);
        }
    } else{
        return $response->withJson(["error" => "User is not a member"], 403);
    }
});

// Hämta alla uppgifter för en specifik lista
$app->get('/lists/{id}/tasks', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if($result->num_rows > 0){
        $tasks = [];
        $result = queryMySQL("SELECT Tasks.*, creator.username AS creatorUsername, completer.username AS completeUsername FROM Tasks LEFT JOIN Users AS creator ON Tasks.userId = creator.id LEFT JOIN Users AS completer ON Tasks.completeUser = completer.id WHERE Tasks.listId = '$listId' ORDER BY Tasks.created_at DESC");
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        return $response->withJson($tasks);
    } else{
        return $response->withJson(["error" => "User is not a member"], 403);
    }
});

// Skapa en ny uppgift för en specifik lista
$app->post('/lists/{id}/tasks', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if($result->num_rows > 0){
        global $conn;
        $data = $request->getParsedBody();
        $listId = $data['listId'];
        $taskName = sanitizeString($data['name']);
        $desc = sanitizeString($data['description']);
        $points = sanitizeString(intval($data['points']));
        $insertQuery = "INSERT INTO Tasks (name, listId, userId, description, points) VALUES('$taskName', '$listId', '$userId', '$desc', '$points')";
        $result = queryMySQL($insertQuery);
        if ($result) {
            return $response->withJson(["message" => "Created Task"]);
        }
        return $response->withJson(["error" => "Failed to insert list"], 500);
    } else{
        return $response->withJson(["error" => "User is not a member"], 403);
    }
});

// Uppdatera status av en specifik uppgift
$app->post('/tasks/{id}/status', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }

    $userId = $_SESSION['userID'];
    $taskId = intval($args['id']);
    $data = $request->getParsedBody();
    if (!isset($data['listId'])) {
        return $response->withJson(["error" => "List ID is missing"], 400);
    }
    $listId = intval($data['listId']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if ($result->num_rows === 0) {
        return $response->withJson(["error" => "User is not a member"], 403);
    }
    $taskStatus = intval($data['taskStatus']);
    $taskResult = queryMySQL("SELECT completeUser FROM Tasks WHERE id = $taskId");
    if ($taskResult->num_rows === 0) {
        return $response->withJson(["error" => "Task not found"], 404);
    }
    $task = $taskResult->fetch_assoc();
    if ($taskStatus === 0 && $task['completeUser'] != $userId) {
        return $response->withJson(["error" => "Only the user who completed the task can uncheck it"], 403);
    }
    $query = $taskStatus === 1
        ? "UPDATE Tasks SET status = $taskStatus, completeUser = $userId WHERE id = $taskId"
        : "UPDATE Tasks SET status = $taskStatus, completeUser = NULL WHERE id = $taskId";

    $updateResult = queryMySQL($query);
    if ($updateResult) {
        return $response->withJson(["success" => true]);
    }

    return $response->withJson(["error" => "Failed to update task status"], 500);
});


// Ta bort en specifik uppgift
$app->post('/tasks/{id}', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $data = $request->getParsedBody();
    if (!isset($data['listId'])) {
        return $response->withJson(["error" => "List ID is missing"], 400);
    }
    $listId = intval($data['listId']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if ($result->num_rows === 0) {
        return $response->withJson(["error" => "User is not a member"], 403);
    }
    $taskId = intval($args['id']);
    $taskResult = queryMySQL("SELECT * FROM Tasks WHERE id = $taskId");
    if ($taskResult->num_rows > 0) {
        $task = $taskResult->fetch_assoc();
        if ($task['userId'] === $userId || $task['completeUser'] === $userId) {
            $deleteResult = queryMySQL("DELETE FROM Tasks WHERE id = '$taskId'");
            if ($deleteResult) {
                return $response->withJson(["success" => true]);
            }
            return $response->withJson(["error" => "Failed to delete task"], 500);
        } else {
            return $response->withJson(["error" => "Only the user who completed the task or created it can delete it"], 403);
        }
    } else {
        return $response->withJson(["error" => "Task not found"], 404);
    }
});

// Hämta alla användare och deras poäng i en specifik lista
$app->get('/lists/{id}/users', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    if ($listId <= 0) {
        return $response->withJson(["error" => "Invalid list ID"], 400);
    }
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if($result->num_rows > 0){
        $users = [];
        $result = queryMySQL("SELECT Users.username, COALESCE(SUM(Tasks.points), 0) AS total_points FROM ListMembers JOIN Users ON ListMembers.userId = Users.id LEFT JOIN Tasks ON Tasks.completeUser = Users.id AND Tasks.listId = ListMembers.listId WHERE ListMembers.listId = '$listId' GROUP BY Users.id, Users.username");
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $response->withJson($users);
    } else{
        return $response->withJson(["error" => "User is not a member"], 403);
    }
});

// Hämta länk för att dela
$app->get('/lists/{id}/share', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['id']);
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");
    if ($result->num_rows > 0) {
        $result = queryMySQL("SELECT token FROM Lists WHERE id = '$listId'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $response->withJson(["token" => $row['token']]);
        }
        return $response->withJson(["error" => "Share token not found"], 404);
    } else {
        return $response->withJson(["error" => "User is not a member of the list"], 403);
    }
});

// Dela specifik lista
$app->get('/share/{token}', function ($request, $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withRedirect("https://melab.lnu.se/~hh223ji/uppgift/public/login.php");
    }
    $token = $args['token'];
    $userId = $_SESSION['userID'];
    $result = queryMySQL("SELECT id FROM Lists WHERE token = '$token'");
    if ($result && $result->num_rows > 0) {
        $listData = $result->fetch_assoc();
        $listId = $listData['id'];        
        $checkMember = queryMySQL("SELECT * FROM ListMembers WHERE listId = '$listId' AND userId = '$userId'");
        if ($checkMember && $checkMember->num_rows > 0) {
            return $response->withRedirect("https://melab.lnu.se/~hh223ji/uppgift/public/lists/$listId");
        } else {
            queryMySQL("INSERT INTO ListMembers (listId, userId) VALUES ('$listId', '$userId')");
            return $response->withRedirect("https://melab.lnu.se/~hh223ji/uppgift/public/lists/$listId");
        }
    } else {
        return $response->withJson(["error" => "Invalid token"], 404);
    }
});

// Kolla om användaren är inloggad
$app->get('/user', function (Request $request, Response $response) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "User not logged in"], 401);
    }
    $responseData = [
        "id" => $_SESSION['userID'],
        "username" => $_SESSION['user']
    ];
    return $response->withJson($responseData);
});

// Logga ut användaren
$app->post('/logout', function (Request $request, Response $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withJson(["success" => true, "message" => "Logged out successfully"]);
});

// Kolla om användaren är del av specifik lista
$app->get('/lists/{listId}/members', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['userID'])) {
        return $response->withJson(["error" => "Sessionid not set"], 401);
    }
    $userId = $_SESSION['userID'];
    $listId = intval($args['listId']);
    $result = queryMySQL("SELECT * FROM ListMembers WHERE listId = $listId AND userId = $userId");

    if ($result && $result->num_rows > 0) {
        return $response->withJson([
            "isMember" => true
        ]);
    } else {
        return $response->withJson([
            "isMember" => false
        ]);
    }
});

// Kolla om användaren är inloggad
$app->get('/checkLogin', function (Request $request, Response $response) {
    $loggedIn = isset($_SESSION['user']);

    $response->getBody()->write(json_encode(["loggedIn" => $loggedIn]));
    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus($loggedIn ? 200 : 401);
});
$app->run();

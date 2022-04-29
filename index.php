<?php
    require_once("./vendor/autoload.php");

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    function getDSN($dbms, $host, $port, $dbname) {
        return "{$dbms}:host={$host};port={$port};dbname={$dbname}";
    }

    try {
        $pdo = new PDO(
            getDSN($_ENV["DBMS"], $_ENV["HOST"], $_ENV["PORT"], $_ENV["DBNAME"]),
            $_ENV["USERNAME"], $_ENV["PASSWORD"]
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (\Throwable $th) {
        echo var_dump($th);
        die("Cannot connect to the database");
    }

    $errors = [];
    $failed = null;
    $users = [];

    if(isset($_REQUEST["submit"])) {
        $user = $_REQUEST["user"];

        if(!isset($user["email"])) array_push($errors, "Email is required.");

        if(!isset($user["family_name"])) array_push($errors, "Family Name is required.");

        if(!isset($user["given_name"])) array_push($errors, "Given Name is required.");

        if($user["email"]) {
            $query = $pdo->prepare("select id from users where email=? limit 1");
            $query->execute([
                $user["email"]
            ]);
            if($query->rowCount() > 0) {
                array_push($errors, "Email already exists.");
            }
        }

        if(count($errors) > 0) $failed = true;
        else $failed = false;

        if(!$failed) {
            $query = $pdo->prepare("
                insert into users (email, family_name, given_name, middle_name)
                values (:email, :family_name, :given_name, :middle_name)
            ");
            $failed = !$query->execute($user);
        }
    }

    $query = $pdo->prepare("select * from users");
    $query->execute();
    if($query->rowCount() > 0) $users = $query->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">
    <title>BSIT 4-3 | ITEC 106 | Laboratory Activity 5 | PHP Log In and Registration</title>
</head>
<body>
    <div id="main-content">
        <?php if($failed != null) { ?>
            <?php if(!$failed) { ?>
                <h1>The User has been successfully registered in the Database.</h1>
            <?php } else { ?>
                <h1>Something went wrong while registering the User. Make sure that you filled up the necessary details and that the email is unique.</h1>
            <?php } ?>
        <?php } ?>
        <?php if(count($users) != 0) { ?>
            <ol id="list">
                <?php foreach($users as $user) { ?>
                    <li>
                        <b><?= $user["email"] ?></b>
                        <div><?= $user["family_name"] . ", " . $user["given_name"] . (
                            !empty($user["middle_name"]) ? " " . $user["middle_name"] : ""
                        ) ?></div>
                    </li>
                <?php } ?>
            </ol>
        <?php } ?>
        <?php if($failed != null || count($users) != 0) { ?><br><?php } ?>
        <form action="./" method="post">
            <div class="field">
                <label for="user_email">Email</label>
                <input type="email" name="user[email]" id="user_email" required>
            </div>
            <div class="field">
                <label for="user_given_name">Given Name</label>
                <input type="text" name="user[given_name]" id="user_given_name" required>
            </div>
            <div class="field">
                <label for="user_middle_name">Middle Name</label>
                <input type="text" name="user[middle_name]" id="user_middle_name">
            </div>
            <div class="field">
                <label for="user_family_name">Family Name</label>
                <input type="text" name="user[family_name]" id="user_family_name" required>
            </div>
            <button type="submit" name="submit" value="1">Submit</button>
        </form>
    </div>
</body>
</html>
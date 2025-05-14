<?php
require_once "Menu.php";
require_once "util.php";
file_put_contents("debug.log", print_r($_POST, true), FILE_APPEND);

// Collect USSD inputs from POST
$sessionId = $_POST['sessionid'] ?? '';
$serviceCode = $_POST['servicecode'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$text = $_POST['text'] ?? '';

if (empty($phoneNumber)) {
    echo "END Registration failed: Phone number missing.\n";
    exit;
}
$textArray = explode("*", $text);
$menu = new Menu();

// Database connection
$pdo = util::getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
$stmt->execute([$phoneNumber]);
$row = $stmt->fetch();
$isRegistered = $row ? true : false;

if (!$isRegistered) {
    handleUnregisteredUser($text, $textArray, $menu, $phoneNumber);
    exit;
}

handleLoggedInUser($text, $textArray, $pdo, $menu, $phoneNumber);

// ==============================
// 🔹 Functions
// ==============================

function handleUnregisteredUser($text, $textArray, $menu, $phoneNumber)
{
    if ($text == "") {
        $menu->unregisteredmainmenu();
        return;
    }

    if (strpos($text, "1") === 0) {
        $menu->registerusermainmenu($textArray, $phoneNumber);
        return;
    }

    echo "END Invalid option. Please try again.";
}

function handleLoggedInUser($text, $textArray, $pdo, $menu, $phoneNumber)
{
    $textArrayCount = count($textArray);

    if ($text == "") {
        echo "CON Please Login\n";
        echo " Enter Username\n";
        return;
    }

    if ($textArrayCount == 1) {
        echo "CON Enter PIN\n";
        return;
    }

    // Step 3: Authenticate login
    if ($textArrayCount == 2) {
        if (isLogin($pdo, $textArray, $phoneNumber)) {
            echo "CON Main Menu\n";
            echo "1. Add New Note\n";
            echo "2. My Notes\n";
            echo "3. Shared With Me\n";
            echo "4. Sent Notes\n";
            echo "5. Change PIN\n";
            echo "6. Exit\n";
        } else {
            echo "END Invalid username or PIN\n";
        }
        return;
    }

    // Step 4: After login, process user action
    if (isLogin($pdo, $textArray, $phoneNumber)) {
        $nextTextArray = array_slice($textArray, 2); // Skip username and PIN
        handleMenuActions($nextTextArray, $menu, $phoneNumber);  // Pass $phoneNumber here
    } else {
        echo "END Session expired or invalid flow.";
    }
}

function handleMenuActions($textArray, $menu, $phoneNumber)
{
    if (count($textArray) == 0) {
        echo "END Invalid request.\n";
        return;
    }

    switch ($textArray[0]) {
        case "1":
            if (count($textArray) == 1) {
                echo "CON Enter your note title:";
            } else {
                $menu->AddNewNote($textArray);
            }
            break;

        case "2":
            $menu->myNotes($textArray, $phoneNumber);  // Use $phoneNumber here
            break;

        case "3":
            $menu->sharedWithMe($textArray);
            break;

        case "4":
            $menu->sentNotes($textArray);
            break;

        case "5":
            if (count($textArray) == 1) {
                echo "CON Enter your current PIN:";
            } else {
                $menu->changePin($textArray);
            }
            break;

        case "6":
            echo "END Thank you for using the service.";
            break;

        default:
            echo "CON Invalid option. Please select a valid option.\n";
            echo "1. Add New Note\n";
            echo "2. My Notes\n";
            echo "3. Shared With Me\n";
            echo "4. Sent Notes\n";
            echo "5. Change PIN\n";
            echo "6. Exit\n";
            break;
    }
}

function isLogin($pdo, $textArray, $phoneNumber)
{
    if (count($textArray) < 2)
        return false;

    $username = $textArray[0];
    $pin = $textArray[1];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ? AND phone = ?");
    $stmt->execute([$username, $phoneNumber]);
    $user = $stmt->fetch();

    return $user && password_verify($pin, $user['password']);
}
?>
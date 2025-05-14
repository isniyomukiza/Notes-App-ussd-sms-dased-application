<?php
require_once "util.php";
require_once 'sms.php';

class Menu
{
    protected $sessionid;
    protected $phonenumber;
    protected $text;

    public function __construct()
    {
        // Constructor kept empty as no initialization is currently needed
    }
    public function unregisteredmainmenu()
    {
        $response = "CON Welcome to SmartNotes!\n";
        $response .= "1. Register\n";
        echo $response;
    }


    public function registeredmainmenu()
    {
        $response = "CON Main Menu\n";
        $response .= "1. Add New Note\n";
        $response .= "2. My Notes\n";
        $response .= "3. Shared With Me\n";
        $response .= "4. Sent Notes\n";
        $response .= "5. Change PIN\n";
        $response .= "6. Exit\n";
        echo $response;
    }


    public function registerusermainmenu($textarray, $phone)
    {
        $level = count($textarray);
        $response = "";

        if ($level == 1) {
            $response = "CON Enter Username\n";
        } elseif ($level == 2) {
            $response = "CON Enter PIN\n";
        } elseif ($level == 3) {
            $response = "CON Re-enter PIN\n";
        } elseif ($level == 4) {
            if ($textarray[2] !== $textarray[3]) {
                echo "END PINs do not match. Please try again.\n";
                return;
            }

            $username = trim($textarray[1]);
            $pinHash = password_hash($textarray[2], PASSWORD_DEFAULT);

            if (empty($phone)) {
                echo "END Registration failed: Phone number missing.\n";
                return;
            }

            try {
                $pdo = util::getDbConnection();

                // Check if phone already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$phone]);

                if ($stmt->fetch()) {
                    echo "END This phone number is already registered.\n";
                    return;
                }

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$username, $phone, $pinHash]);

                echo "END Dear {$username}, you have successfully registered.\n";
                $sms = new Sms($this->getUserPhone());
                $result = $sms->sendSMS("Welcome {$username}, you have successfully registered on Notes App!", $this->getUserPhone());
                file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
                return;

            } catch (PDOException $e) {
                echo "END Registration failed: " . $e->getMessage() . "\n";
                return;
            }
        }

        if (!empty($response)) {
            $response .= util::$goback . ". Back\n";
            $response .= util::$goToMainMenu . ". Main menu\n";
            echo $response;
        }
    }



    public function registeredLoginMainMenu($textarray)
    {
        $level = count($textarray);
        if ($level == 1) {
            // If the first input is empty, prompt for username
            if (empty($textarray[0])) {
                echo "CON Please Login\n";
                echo "CON Enter Username\n";
            } else {
                // If username is provided, prompt for PIN
                $response = "CON Enter PIN\n";
                $response .= util::$goback . ". Back\n";
                $response .= util::$goToMainMenu . ". Main menu\n";
                echo $response;
            }
        } else if ($level == 2) {
            // Now, username and PIN are provided, check credentials
            $pdo = util::getDbConnection();
            $stmt = $pdo->prepare('SELECT password FROM users WHERE username = ?');
            $stmt->execute([$textarray[0]]);
            $user = $stmt->fetch();
            if ($user && password_verify($textarray[1], $user['password'])) {
                $this->registeredmainmenu();
            } else {
                echo "END Invalid username or Password\n";
            }
        } else {
            echo "END Invalid username or Password\n";
        }
    }

    public function AddNewNote($textarray)
    {
        $level = count($textarray);
        $response = "";
        $pdo = util::getDbConnection();
        $phone = $_POST["phonenumber"] ?? '';

        // Get user info
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user) {
            echo "END User not found.\n";
            return;
        }

        $userId = $user['id'];

        if ($level == 1) {
            $response = "CON Enter Title\n";

        } else if ($level == 2) {
            $response = "CON Enter Contents\n";

        } else if ($level == 3) {
            $response = "CON Your note is: {$textarray[2]}\n";
            $response .= "1. Share\n";
            $response .= "2. Exit\n";

        } else if ($level == 4) {
            if ($textarray[3] == "1") {
                // Show recipient list
                $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id != ?');
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll();

                if (!$recipients) {
                    echo "END No other users found.\n";
                    return;
                }

                $response = "CON Select recipient:\n";
                foreach ($recipients as $i => $r) {
                    $response .= ($i + 1) . ". " . $r['username'] . "\n";
                }

            } elseif ($textarray[3] == "2") {
                echo "END Thank you. Note saved without sharing.\n";
                $sms = new Sms($this->getUserPhone());
                $result = $sms->sendSMS("Your note has been saved successfully in Notes App!", $this->getUserPhone());
                file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
                return;

            } else {
                echo "END Invalid option.\n";
                return;
            }

        } else if ($level == 5) {
            $selectedRecipientIndex = intval($textarray[4]) - 1;

            // Fetch all recipients again to match index
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id != ?');
            $stmt->execute([$userId]);
            $recipients = $stmt->fetchAll();

            if (!isset($recipients[$selectedRecipientIndex])) {
                echo "END Invalid recipient selection.\n";
                return;
            }

            $recipientId = $recipients[$selectedRecipientIndex]['id'];

            // Save note first if not already saved
            $title = $textarray[1];
            $content = $textarray[2];

            $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$userId, $title, $content]);
            $noteId = $pdo->lastInsertId();

            // Share the note
            $stmt = $pdo->prepare('INSERT IGNORE INTO shared_notes (note_id, owner_id, shared_with_id, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$noteId, $userId, $recipientId]);

            echo "END You have shared the note successfully.\n";
            $sms = new Sms($this->getUserPhone());
            $result = $sms->sendSMS("You have shared a note successfully using Notes App!", $this->getUserPhone());
            file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
            return;
        }

        if (isset($response)) {
            $response .= util::$goback . ". Back\n";
            $response .= util::$goToMainMenu . ". Main menu\n";
            echo $response;
        }
    }


    public function myNotes($textarray, $phone)
    {
        $level = count($textarray);
        $response = "";
        $pdo = util::getDbConnection();

        // Get user ID based on phone number
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user) {
            return "END User not found.";
        }
        $userId = $user['id'];

        // Step 1: Show user's notes (Level 1)
        if ($level == 1) {
            $stmt = $pdo->prepare("SELECT id, title FROM notes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $notes = $stmt->fetchAll();

            if ($notes) {
                $response = "CON Your Notes:\n";
                foreach ($notes as $i => $note) {
                    $response .= ($i + 1) . ". " . $note['title'] . "\n";
                }
                $response .= "98. Back\n";
                $response .= "99. Main menu\n";
            } else {
                $response = "END You have no notes.";
            }
        }

        // Step 2: Show actions for selected note (Level 2)
        elseif ($level == 2) {
            $noteIndex = intval($textarray[1]) - 1;
            $stmt = $pdo->prepare("SELECT id, title FROM notes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $notes = $stmt->fetchAll();

            if (!isset($notes[$noteIndex])) {
                return "END Invalid note selection.";
            }

            $noteTitle = $notes[$noteIndex]['title'];
            $response = "CON Selected: '$noteTitle'\n";
            $response .= "1. View\n";
            $response .= "2. Update\n";
            $response .= "3. Delete\n";
            $response .= "95. Share\n";
            $response .= "98. Back";
        }

        // Step 3: Perform action
        elseif ($level == 3) {
            $action = $textarray[2];
            $noteIndex = intval($textarray[1]) - 1;
            $stmt = $pdo->prepare("SELECT id, title, content FROM notes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $notes = $stmt->fetchAll();

            if (!isset($notes[$noteIndex])) {
                return "END Invalid note.";
            }

            $note = $notes[$noteIndex];

            if ($action == "1") {
                $response = "END Note Content:\n" . $note['content'];
            } elseif ($action == "2") {
                $response = "CON Enter new content:";
            } elseif ($action == "3") {
                $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
                $stmt->execute([$note['id']]);
                $response = "END Note deleted successfully.";
                $sms = new Sms($this->getUserPhone());
                $result = $sms->sendSMS("You have deleted a note from Notes App.", $this->getUserPhone());
                file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
            } elseif ($action == "95") {
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll();

                if ($recipients) {
                    $response = "CON Select recipient:\n";
                    foreach ($recipients as $i => $r) {
                        $response .= ($i + 1) . ". " . $r['username'] . "\n";
                    }
                } else {
                    $response = "END No recipients available to share with.";
                }
            } else {
                $response = "END Invalid option.";
            }
        }

        // Step 4: Update or Share
        elseif ($level == 4) {
            $action = $textarray[2];
            $noteIndex = intval($textarray[1]) - 1;

            $stmt = $pdo->prepare("SELECT id FROM notes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $notes = $stmt->fetchAll();

            if (!isset($notes[$noteIndex])) {
                return "END Invalid note.";
            }

            $noteId = $notes[$noteIndex]['id'];

            if ($action == "2") {
                $newContent = $textarray[3];
                $stmt = $pdo->prepare("UPDATE notes SET content = ? WHERE id = ?");
                $stmt->execute([$newContent, $noteId]);
                $response = "END Note updated.";
            } elseif ($action == "95") {
                $recipientIndex = intval($textarray[3]) - 1;

                $stmt = $pdo->prepare("SELECT id FROM users WHERE id != ?");
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll();

                if (!isset($recipients[$recipientIndex])) {
                    return "END Invalid recipient.";
                }

                $recipientId = $recipients[$recipientIndex]['id'];

                $stmt = $pdo->prepare("INSERT IGNORE INTO shared_notes (note_id, owner_id, shared_with_id, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$noteId, $userId, $recipientId]);

                $response = "END Note shared successfully.";
            } else {
                $response = "END Invalid action.";
            }
        }

        // Unknown level
        else {
            $response = "END Invalid request.";
        }

        echo $response;
    }




    public function sharedWithMe($textarray)
    {
        $level = count($textarray);

        if ($level == 1) {
            // Fetch notes shared with the user from the database
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            $response = "CON Notes shared with you:\n";
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT n.id, n.title FROM shared_notes s JOIN notes n ON s.note_id = n.id WHERE s.shared_with_id
= ?');
                $stmt->execute([$userId]);
                $notes = $stmt->fetchAll();
                if ($notes) {
                    foreach ($notes as $i => $note) {
                        $response .= ($i + 1) . ". " . $note['title'] . "\n";
                    }
                } else {
                    $response .= "No shared notes found.\n";
                }
            } else {
                $response .= "No shared notes found.\n";
            }
        } else if ($level == 2) {
            // Fetch and show the content of the selected shared note
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT n.id, n.title, n.content FROM shared_notes s JOIN notes n ON s.note_id = n.id WHERE
s.shared_with_id = ?');
                $stmt->execute([$userId]);
                $notes = $stmt->fetchAll();
                $selectedIndex = intval($textarray[1]) - 1;
                if (isset($notes[$selectedIndex])) {
                    $note = $notes[$selectedIndex];
                    $noteTitle = $note['title'];
                    $noteContent = $note['content'];
                    $response = "CON You selected: $noteTitle\n";
                    $response .= "Note Content: $noteContent\n";
                    $response .= "95. Share\n";
                    $response .= "97. Delete\n";
                } else {
                    $response = "END Invalid note selection.";
                    echo $response;
                    return;
                }
            } else {
                $response = "END No shared notes found.";
                echo $response;
                return;
            }
        } else if ($level == 3 && $textarray[2] == "95") {
            // Dynamic share logic: fetch all users except current
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            $response = "CON Select recipient:\n";
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT id, username, phone FROM users WHERE id != ?');
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll();
                if ($recipients) {
                    foreach ($recipients as $i => $recipient) {
                        $response .= ($i + 1) . ". " . $recipient['username'] . "\n";
                    }
                    // Store recipients in session for later use
                    $_SESSION['share_recipients'] = $recipients;
                } else {
                    $response .= "No other users found.\n";
                }
            } else {
                $response .= "No other users found.\n";
            }
        } else if ($level == 4 && $textarray[3]) {
            // Final level: confirm sharing to the selected recipient
            $recipient = $textarray[3]; // 1, 2, or 3 for Aimable, Ismael, or Rosette
            $recipientName = $recipient == "1" ? "Aimable" : ($recipient == "2" ? "Ismael" : "Rosette");

            echo "END You have shared the note to $recipientName successfully\n";
            $sms = new Sms($this->getUserPhone());
            $result = $sms->sendSMS("You have shared a note successfully using Notes App!", $this->getUserPhone());
            file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
            return;
        }
        // Common options for all levels
        if (isset($response)) {
            $response .= util::$goback . ". Back\n";
            $response .= util::$goToMainMenu . ". Main menu\n";
            echo $response;
        }
    }
    public function sentNotes($textarray)
    {
        $level = count($textarray);

        if ($level == 1) {
            // Fetch sent notes from the database
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            $response = "CON Sent notes:\n";
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT n.id, n.title FROM shared_notes s JOIN notes n ON s.note_id = n.id WHERE s.owner_id = ?');
                $stmt->execute([$userId]);
                $notes = $stmt->fetchAll();
                if ($notes) {
                    foreach ($notes as $i => $note) {
                        $response .= ($i + 1) . ". " . $note['title'] . "\n";
                    }
                } else {
                    $response .= "No sent notes found.\n";
                }
            } else {
                $response .= "No sent notes found.\n";
            }
        } else if ($level == 2) {
            // Fetch and show the content of the selected sent note
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT n.id, n.title, n.content FROM shared_notes s JOIN notes n ON s.note_id = n.id WHERE
s.owner_id = ?');
                $stmt->execute([$userId]);
                $notes = $stmt->fetchAll();
                $selectedIndex = intval($textarray[1]) - 1;
                if (isset($notes[$selectedIndex])) {
                    $note = $notes[$selectedIndex];
                    $noteTitle = $note['title'];
                    $noteContent = $note['content'];
                    $response = "CON You selected: $noteTitle\n";
                    $response .= "Note Content: $noteContent\n";
                    $response .= "95. Share\n";
                    $response .= "97. Delete\n";
                } else {
                    $response = "END Invalid note selection.";
                    echo $response;
                    return;
                }
            } else {
                $response = "END No sent notes found.";
                echo $response;
                return;
            }
        } else if ($level == 3 && $textarray[2] == "95") {
            // Dynamic share logic: fetch all users except current
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            $response = "CON Select recipient:\n";
            if ($user) {
                $userId = $user['id'];
                $stmt = $pdo->prepare('SELECT id, username, phone FROM users WHERE id != ?');
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll();
                if ($recipients) {
                    foreach ($recipients as $i => $recipient) {
                        $response .= ($i + 1) . ". " . $recipient['username'] . "\n";
                    }
                    $_SESSION['share_recipients'] = $recipients;
                } else {
                    $response .= "No other users found.\n";
                }
            } else {
                $response .= "No other users found.\n";
            }
        } else if ($level == 4 && $textarray[2] == "95") {
            // Share sent note with selected recipient (dynamic)
            session_start();
            $recipients = $_SESSION['share_recipients'] ?? [];
            $recipientIndex = intval($textarray[3]) - 1;
            if (empty($recipients)) {
                echo "END No recipients available to share with.\n";
                return;
            }
            if (!isset($recipients[$recipientIndex])) {
                echo "END Invalid recipient selection.\n";
                return;
            }
            $recipientId = $recipients[$recipientIndex]['id'];
            $pdo = util::getDbConnection();
            $phone = $_POST["phonenumber"] ?? '';
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if (!$user) {
                echo "END User not found.\n";
                return;
            }
            $userId = $user['id'];
            $stmt = $pdo->prepare('SELECT n.id FROM shared_notes s JOIN notes n ON s.note_id = n.id WHERE s.owner_id = ?');
            $stmt->execute([$userId]);
            $notes = $stmt->fetchAll();
            $selectedIndex = intval($textarray[1]) - 1;
            if (!isset($notes[$selectedIndex])) {
                echo "END Note not found for sharing.\n";
                return;
            }
            $noteId = $notes[$selectedIndex]['id'];
            $stmt = $pdo->prepare('INSERT IGNORE INTO shared_notes (note_id, owner_id, shared_with_id, created_at) VALUES (?, ?, ?,
NOW())');
            $success = $stmt->execute([$noteId, $userId, $recipientId]);
            if ($success && $stmt->rowCount() > 0) {
                echo "END You have shared the note successfully.\n";
                $sms = new Sms($this->getUserPhone());
                $result = $sms->sendSMS("You have shared a note successfully using Notes App!", $this->getUserPhone());
                file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
                return;
            } else {
                echo "END Failed to share the note. It may have already been shared with this user.\n";
                return;
            }
        }

        // Common options for all levels
        if (isset($response)) {
            $response .= util::$goback . ". Back\n";
            $response .= util::$goToMainMenu . ". Main menu\n";
            echo $response;
        }
    }

    public function changePin($textarray)
    {
        $level = count($textarray);
        if ($level == 1) {
            $response = "CON Enter Old PIN\n";
        } else if ($level == 2) {
            $response = "CON Enter New PIN\n";
        } else if ($level == 3) {
            $response = "CON Re-Enter New PIN\n";
        } else if ($level == 4 && $textarray[2] == $textarray[3]) {
            $response = "CON Are you sure you want to change your PIN?\n";
            $response .= "1. Confirm\n";
            $response .= "2. Cancel\n";
        } else if ($level == 5 && $textarray[4] == "1") {
            // Update the user's password in the database
            $newPin = $textarray[3];
            $hashedPin = password_hash($newPin, PASSWORD_DEFAULT);
            $phone = $this->getUserPhone();
            $pdo = util::getDbConnection();
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE phone = ?");
            $stmt->execute([$hashedPin, $phone]);
            echo "END Your PIN has been successfully changed. You will receive an SMS shortly.\n";
            $sms = new Sms($phone);
            $result = $sms->sendSMS("Your PIN has been changed successfully in Notes App!", $phone);
            file_put_contents("debug.log", print_r($result, true), FILE_APPEND);
            return;
        } else if ($level == 6) {
            echo "END Thank you for using " . util::$COMPANY_NAME . " services.\n";
            return;
        }

        if (isset($response)) {
            $response .= util::$goback . ". Back\n";
            $response .= util::$goToMainMenu . ". Main menu\n";
            echo $response;
        }
    }

    // GoBack Logic
    public function goback($text)
    {
        $explodeText = explode("*", $text);
        while (($index = array_search(util::$goback, $explodeText)) !== false) {
            array_splice($explodeText, $index - 1, 2);
        }
        return join("*", $explodeText);
    }

    public function goToMainMenu($text)
    {
        $explodeText = explode("*", $text);
        while (($index = array_search(util::$goToMainMenu, $explodeText)) !== false) {
            $explodeText = array_slice($explodeText, $index + 1);
        }
        return join("*", $explodeText);
    }
    public function middleWare($text)
    {
        return $this->goback($this->goToMainMenu($text));
    }

    public function isUserRegistered($phoneNumber)
    {
        $pdo = util::getDbConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
        $stmt->execute([$phoneNumber]);
        return $stmt->fetch() !== false;
    }

    // Helper to enforce registration before accessing menus
    public function requireRegistration($phoneNumber)
    {
        if (!$this->isUserRegistered($phoneNumber)) {
            $this->unregisteredmainmenu();
            exit;
        }
    }

    // Helper to get phone number from POST reliably
    private function getUserPhone()
    {
        if (!empty($_POST['phonenumber']))
            return $_POST['phonenumber'];
        if (!empty($_POST['phoneNumber']))
            return $_POST['phoneNumber'];
        return '';
    }
}
?>
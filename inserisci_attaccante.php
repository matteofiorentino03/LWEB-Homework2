<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header("Location: entering.html");
    exit();
}

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}

$id_giocatore = $_GET['id'] ?? null;
$errore = "";
$successo = "";
$ruolo = $_SESSION['Ruolo'];
$homepage_link = ($ruolo === 'admin') ? 'homepage_admin.php' : 'homepage_user.php';

if (!$id_giocatore) {
    die("ID giocatore mancante.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gol_fatti = (int)$_POST['Gol_Fatti'];
    $assist = (int)$_POST['Assist'];
    $ammonizioni = (int)$_POST['Ammonizioni'];
    $espulsioni = (int)$_POST['Espulsioni'];
    $ruolo_attaccante = $_POST['RuoloAttaccante'];

    foreach ([$gol_fatti, $assist, $ammonizioni, $espulsioni] as $val) {
        if ($val < 0) $errore = "I valori numerici non possono essere negativi.";
    }

    if ($errore === "") {
        $stmt = $conn->prepare("INSERT INTO Attaccanti (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiis", $id_giocatore, $gol_fatti, $assist, $ammonizioni, $espulsioni, $ruolo_attaccante);

        if ($stmt->execute()) {
            $successo = "Dati dell'attaccante inseriti correttamente.";
        } else {
            $errore = "Errore durante l'inserimento: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserimento Attaccante</title>
    <link rel="stylesheet" href="styles/style_inserimenti_g.css">
</head>
<body>
    <header>
        <a href="<?php echo $homepage_link; ?>" class="header-link">
            <div class="logo-container">
                <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo" />
            </div>
        </a>
        <h1><a href="<?php echo $homepage_link; ?>" style="color: inherit; text-decoration: none;">PLAYERBASE</a></h1>        
        <div class="utente-container">
            <div class="logout">
                <a href="?logout=true">Logout</a>
            </div>
        </div>
    </header>

<div class="main-container">
    <div class="table">
        <h2>Completamento dati per il ruolo: Attaccante</h2>
        <?php if ($errore) echo "<p style='color:red;'>$errore</p>"; ?>
        <?php if ($successo) echo "<p style='color:green;'>$successo</p>"; ?>
        <form method="post">
            <input type="number" name="Gol_Fatti" placeholder="Gol Fatti" required><br><br>
            <input type="number" name="Assist" placeholder="Assist" required><br><br>
            <input type="number" name="Ammonizioni" placeholder="Ammonizioni" required><br><br>
            <input type="number" name="Espulsioni" placeholder="Espulsioni" required><br><br>
            <label for="RuoloAttaccante" style="font-weight: bold;">Ruolo Attaccante:</label><br>
            <select name="RuoloAttaccante" required>
                <option value="punta">Punta</option>
                <option value="seconda punta">Seconda Punta</option>
                <option value="ala">Ala</option>
                <option value="falso 9">Falso 9</option>
            </select><br><br>
            <button type="submit">Conferma</button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
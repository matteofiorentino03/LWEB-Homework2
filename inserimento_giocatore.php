<?php
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entering.html");
    exit();
}

if (!isset($_SESSION['Username'])) {
    header("Location: entering.html");
    exit();
}

$username = $_SESSION['Username'];
$ruolo = $_SESSION['Ruolo'];
$homepage_link = ($ruolo === 'admin') ? 'homepage_admin.php' : 'homepage_user.php';

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}

$errore = "";
$successo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cf = trim($_POST['CF']);
    $nome = trim($_POST['Nome']);
    $cognome = trim($_POST['Cognome']);
    $altezza = (float)$_POST['Altezza'];
    $nazionalita = trim($_POST['Nazionalita']);
    $num_maglia = (int)$_POST['Num_Maglia'];
    $data_nascita = $_POST['DataNascita'];
    $market_value = (float)$_POST['Market_Value'];
    $presenze_stagionali = (int)$_POST['PresenzeStagionali'];
    $data_inizio = $_POST['Data_Inizio'];
    $tipo_contratto = $_POST['Tipo_Contratto'];
    $scadenza = $_POST['Scadenza'];
    $stipendio = (float)$_POST['Stipendio'];
    $id_utente = $_SESSION['ID_Utente'] ?? null;
    $ruolo = $_POST['Ruolo'];

    if (!$id_utente) {
        $errore = "Utente non identificato. Eseguire nuovamente il login.";
    }

    $oggi = date('Y-m-d');
    if ($data_nascita >= $oggi) $errore .= "La data di nascita deve essere precedente a oggi.<br>";
    if ($altezza < 1.31) $errore .= "Altezza minima 1.31m.<br>";
    if ($num_maglia < 1 || $num_maglia > 99) $errore .= "Numero maglia tra 1 e 99.<br>";

    // Controllo numero maglia già presente
    $stmt = $conn->prepare("SELECT ID FROM Giocatori WHERE num_maglia = ?");
    $stmt->bind_param("i", $num_maglia);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errore .= "Numero di maglia già utilizzato.<br>";
    $stmt->close();

    if ($market_value < 0) $errore .= "Market value non può essere negativo.<br>";
    if ($presenze_stagionali < 0) $errore .= "Presenze stagionali non possono essere negative.<br>";
    if ($stipendio < 0) $errore .= "Stipendio non può essere negativo.<br>";

    // Genera codice contratto
    $iniziale_nome = strtoupper(substr($nome, 0, 1));
    $iniziale_cognome = strtoupper(substr($cognome, 0, 1));
    $meseanno = date('my', strtotime($data_inizio));
    $cod_contratto = $iniziale_cognome . $iniziale_nome . $meseanno;

    if ($errore === "") {
        $query = "INSERT INTO Giocatori (cf, nome, cognome, nazionalita, datanascita, num_maglia,
                    altezza, market_value, presenze, cod_contratto, Tipo_Contratto,
                    stipendio, Data_inizio, Data_scadenza, ID_utenti)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssididssdssi", $cf, $nome, $cognome, $nazionalita, $data_nascita,
                          $num_maglia, $altezza, $market_value, $presenze_stagionali,
                          $cod_contratto, $tipo_contratto, $stipendio, $data_inizio,
                          $scadenza, $id_utente);

        if ($stmt->execute()) {
            $id_giocatore = $stmt->insert_id;
            $stmt->close();

            // Inserisci record in Agisce
            $query_agisce = "INSERT INTO Agisce (ID_utenti, ID_giocatore, data_inserimento)
                             VALUES (?, ?, CURDATE())";
            $stmt_agisce = $conn->prepare($query_agisce);
            $stmt_agisce->bind_param("ii", $id_utente, $id_giocatore);
            $stmt_agisce->execute();
            $stmt_agisce->close();

            $successo = "Giocatore inserito correttamente. Verrai reindirizzato alla schermata per completare i dati del ruolo.";
            echo "<script>
                    alert('$successo');
                    window.location.href = 'inserisci_" . strtolower($ruolo) . ".php?id=$id_giocatore';
                  </script>";
            exit();
        } else {
            $errore = "Errore durante l'inserimento del giocatore.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserimento Giocatore</title>
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
        <h2>Inserisci un nuovo Giocatore</h2>
        <?php if ($errore !== "") echo "<p style='color:red;'>$errore</p>"; ?>
        <form method="post">
            <input type="text" name="CF" placeholder="Codice Fiscale" required><br><br>
            <input type="text" name="Nome" placeholder="Nome" required><br><br>
            <input type="text" name="Cognome" placeholder="Cognome" required><br><br>
            <input type="number" step="0.01" name="Altezza" placeholder="Altezza in metri" required><br><br>
            <input type="text" name="Nazionalita" placeholder="Nazionalità" required><br><br>
            <input type="number" name="Num_Maglia" placeholder="Numero Maglia" required><br><br>
            <label for="DataNascita" style="font-weight: bold;">Data di Nascita:</label><br>
            <input type="date" name="DataNascita" placeholder="Data di Nascita" required><br><br>
            <input type="number" step="0.01" name="Market_Value" placeholder="Valore di Mercato" required><br><br>
            <input type="number" name="PresenzeStagionali" placeholder="Presenze Stagionali" required><br><br>
            <label for="Data_Inizio" style="font-weight: bold;">Data di Inizio del Contratto:</label><br>
            <input type="date" name="Data_Inizio" placeholder="Data Inizio" required><br><br>
            <label for="Tipo_Contratto" style="font-weight: bold;">Tipo di Contratto:</label><br>
            <select name="Tipo_Contratto" required>
                <option value="TRASFERIMENTO TEMPORANEO">Trasferimento Temporaneo</option>
                <option value="TRASFERIMENTO DEFINITIVO">Trasferimento Definitivo</option>
                <option value="PROMOSSO DALLA PRIMAVERA">Promosso dalla Primavera</option>
                <option value="RINNOVATO">Rinnovato</option>
            </select><br><br>
            <label for="Scadenza" style="font-weight: bold;">Scadenza del Contratto:</label><br>
            <input type="date" name="Scadenza" placeholder="Scadenza" required><br><br>
            <input type="number" step="0.01" name="Stipendio" placeholder="Stipendio" required><br><br>
            <label for="Ruolo" style="font-weight: bold;">Ruolo:</label><br>
            <select name="Ruolo" required>
                <option value="Portiere">Portiere</option>
                <option value="Difensore">Difensore</option>
                <option value="Centrocampista">Centrocampista</option>
                <option value="Attaccante">Attaccante</option>
            </select><br><br>
            <button type="submit">Inserisci Giocatore</button>
        </form>
    </div>
</div>
    <footer>
        <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['Username']) || $_SESSION['Ruolo'] !== 'admin') {
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

$errore = "";
$successo = "";
$record = null;
$giocatori = [];
$homepage_link = 'homepage_admin.php';

// Recupera tutti i giocatori
$res = $conn->query("SELECT ID, nome, cognome, cf FROM Giocatori ORDER BY nome, cognome ASC");
while ($row = $res->fetch_assoc()) {
    $giocatori[] = $row;
}

// ==================== CARICAMENTO GIOCATORE ====================
if (isset($_POST['select_id'])) {
    $id_selezionato = intval($_POST['select_id']);
    $query = "SELECT * FROM Giocatori WHERE ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_selezionato);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $record = $result->fetch_assoc();

        // Controlla il ruolo
        if ($ru = $conn->query("SELECT * FROM Portieri WHERE ID_giocatore = $id_selezionato")->fetch_assoc()) {
            $record['Ruolo'] = 'Portiere';
            $record = array_merge($record, $ru);
        } elseif ($ru = $conn->query("SELECT * FROM Difensori WHERE ID_giocatore = $id_selezionato")->fetch_assoc()) {
            $record['Ruolo'] = 'Difensore';
            $record = array_merge($record, $ru);
        } elseif ($ru = $conn->query("SELECT * FROM Centrocampisti WHERE ID_giocatore = $id_selezionato")->fetch_assoc()) {
            $record['Ruolo'] = 'Centrocampista';
            $record = array_merge($record, $ru);
        } elseif ($ru = $conn->query("SELECT * FROM Attaccanti WHERE ID_giocatore = $id_selezionato")->fetch_assoc()) {
            $record['Ruolo'] = 'Attaccante';
            $record = array_merge($record, $ru);
        }
    } else {
        $errore = "Giocatore non trovato.";
    }
}

// ==================== AGGIORNAMENTO GIOCATORE ====================
if (isset($_POST['update']) && isset($_POST['ID'])) {
    $id = intval($_POST['ID']);
    $cf = trim($_POST['cf']);
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $nazionalita = trim($_POST['nazionalita']);
    $datanascita = $_POST['datanascita'];
    $num_maglia = intval($_POST['num_maglia']);
    $altezza = floatval($_POST['altezza']);
    $market_value = floatval($_POST['market_value']);
    $presenze = intval($_POST['presenze']);
    $cod_contratto = trim($_POST['cod_contratto']);
    $tipo_contratto = $_POST['Tipo_Contratto'];
    $stipendio = floatval($_POST['stipendio']);
    $data_inizio = $_POST['Data_inizio'];
    $data_scadenza = $_POST['Data_scadenza'];

    // Update Giocatori
    $stmt = $conn->prepare("UPDATE Giocatori SET 
        cf=?, nome=?, cognome=?, nazionalita=?, datanascita=?, num_maglia=?, altezza=?, market_value=?, presenze=?, 
        cod_contratto=?, Tipo_Contratto=?, stipendio=?, Data_inizio=?, Data_scadenza=?
        WHERE ID=?");
    $stmt->bind_param(
        "sssssiddissdssi",
        $cf, $nome, $cognome, $nazionalita, $datanascita, $num_maglia, $altezza, $market_value, $presenze,
        $cod_contratto, $tipo_contratto, $stipendio, $data_inizio, $data_scadenza, $id
    );
    $stmt->execute();

    // Elimina vecchi dati ruolo
    $conn->query("DELETE FROM Portieri WHERE ID_giocatore = $id");
    $conn->query("DELETE FROM Difensori WHERE ID_giocatore = $id");
    $conn->query("DELETE FROM Centrocampisti WHERE ID_giocatore = $id");
    $conn->query("DELETE FROM Attaccanti WHERE ID_giocatore = $id");

    $ruolo = $_POST['Ruolo'];

    // Inserisci nuovi dati ruolo
    switch ($ruolo) {
        case 'Portiere':
            $stmt = $conn->prepare("INSERT INTO Portieri (ID_giocatore, gol_subiti, gol_fatti, assist, clean_sheet, ammonizioni, espulsioni) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiiii", $id, $_POST['gol_subiti'], $_POST['gol_fatti'], $_POST['assist'], $_POST['clean_sheet'], $_POST['ammonizioni'], $_POST['espulsioni']);
            $stmt->execute();
            break;

        case 'Difensore':
            $stmt = $conn->prepare("INSERT INTO Difensori (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $id, $_POST['gol_fatti'], $_POST['assist'], $_POST['ammonizioni'], $_POST['espulsioni'], $_POST['ruolo_dif']);
            $stmt->execute();
            break;

        case 'Centrocampista':
            $stmt = $conn->prepare("INSERT INTO Centrocampisti (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $id, $_POST['gol_fatti'], $_POST['assist'], $_POST['ammonizioni'], $_POST['espulsioni'], $_POST['ruolo_cen']);
            $stmt->execute();
            break;

        case 'Attaccante':
            $stmt = $conn->prepare("INSERT INTO Attaccanti (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $id, $_POST['gol_fatti'], $_POST['assist'], $_POST['ammonizioni'], $_POST['espulsioni'], $_POST['ruolo_att']);
            $stmt->execute();
            break;
    }

    $successo = "Dati aggiornati correttamente.";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Giocatore</title>
    <link rel="stylesheet" href="styles/style_modifica_g.css">
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
            <div class="logout"><a href="?logout=true">Logout</a></div>
        </div>
    </header>

    <h2>Modifica Giocatore</h2>
    <?php if ($errore) echo "<p style='color:red;'>$errore</p>"; ?>
    <?php if ($successo) echo "<p style='color:green;'>$successo</p>"; ?>

    <form method="post">
        <label><strong>Seleziona Giocatore:</strong></label>
        <select name="select_id" onchange="this.form.submit()">
            <option value="">-- Seleziona --</option>
            <?php foreach ($giocatori as $g): ?>
                <option value="<?= $g['ID'] ?>" <?= (isset($record['ID']) && $record['ID'] == $g['ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['nome'] . ' ' . $g['cognome']) ?> (<?= htmlspecialchars($g['cf']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($record): ?>
    <form method="post">
        <input type="hidden" name="ID" value="<?= $record['ID'] ?>">

        <div class="form-grid">
            <label><strong>CF:</strong></label><input type="text" name="cf" value="<?= $record['cf'] ?>" required>
            <label><strong>Nome:</strong></label><input type="text" name="nome" value="<?= $record['nome'] ?>" required>
            <label><strong>Cognome:</strong></label><input type="text" name="cognome" value="<?= $record['cognome'] ?>" required>
            <label><strong>Nazionalità:</strong></label><input type="text" name="nazionalita" value="<?= $record['nazionalita'] ?>" required>
            <label><strong>Data Nascita:</strong></label><input type="date" name="datanascita" value="<?= $record['datanascita'] ?>" required>
            <label><strong>Numero Maglia:</strong></label><input type="number" name="num_maglia" value="<?= $record['num_maglia'] ?>" required>
            <label><strong>Altezza (m):</strong></label><input type="number" step="0.01" name="altezza" value="<?= $record['altezza'] ?>" required>
            <label><strong>Market Value (€):</strong></label><input type="number" step="0.01" name="market_value" value="<?= $record['market_value'] ?>" required>
            <label><strong>Presenze:</strong></label><input type="number" name="presenze" value="<?= $record['presenze'] ?>" required>
            <label><strong>Codice Contratto:</strong></label><input type="text" name="cod_contratto" value="<?= $record['cod_contratto'] ?>" required>
            <label><strong>Tipo Contratto:</strong></label>
            <select name="Tipo_Contratto" required>
                <option <?= $record['Tipo_Contratto']=='TRASFERIMENTO TEMPORANEO'?'selected':'' ?>>TRASFERIMENTO TEMPORANEO</option>
                <option <?= $record['Tipo_Contratto']=='TRASFERIMENTO DEFINITIVO'?'selected':'' ?>>TRASFERIMENTO DEFINITIVO</option>
                <option <?= $record['Tipo_Contratto']=='PROMOSSO DALLA PRIMAVERA'?'selected':'' ?>>PROMOSSO DALLA PRIMAVERA</option>
                <option <?= $record['Tipo_Contratto']=='RINNOVATO'?'selected':'' ?>>RINNOVATO</option>
            </select>
            <label><strong>Stipendio (€):</strong></label><input type="number" step="0.01" name="stipendio" value="<?= $record['stipendio'] ?>" required>
            <label><strong>Data Inizio:</strong></label><input type="date" name="Data_inizio" value="<?= $record['Data_inizio'] ?>" required>
            <label><strong>Data Scadenza:</strong></label><input type="date" name="Data_scadenza" value="<?= $record['Data_scadenza'] ?>" required>
        </div>

        <label><strong>Ruolo:</strong></label>
        <select name="Ruolo" id="ruolo" required>
            <option value="Portiere" <?= ($record['Ruolo'] ?? '') === 'Portiere' ? 'selected' : '' ?>>Portiere</option>
            <option value="Difensore" <?= ($record['Ruolo'] ?? '') === 'Difensore' ? 'selected' : '' ?>>Difensore</option>
            <option value="Centrocampista" <?= ($record['Ruolo'] ?? '') === 'Centrocampista' ? 'selected' : '' ?>>Centrocampista</option>
            <option value="Attaccante" <?= ($record['Ruolo'] ?? '') === 'Attaccante' ? 'selected' : '' ?>>Attaccante</option>
        </select>

        <!-- Blocchi ruolo -->
        <div id="Portiere" class="role-block hidden">
            <label><strong>Gol Subiti:</strong></label><input type="number" name="gol_subiti" value="<?= $record['gol_subiti'] ?? '' ?>">
            <label><strong>Gol Fatti:</strong></label><input type="number" name="gol_fatti" value="<?= $record['gol_fatti'] ?? '' ?>">
            <label><strong>Assist:</strong></label><input type="number" name="assist" value="<?= $record['assist'] ?? '' ?>">
            <label><strong>Clean Sheet:</strong></label><input type="number" name="clean_sheet" value="<?= $record['clean_sheet'] ?? '' ?>">
            <label><strong>Ammonizioni:</strong></label><input type="number" name="ammonizioni" value="<?= $record['ammonizioni'] ?? '' ?>">
            <label><strong>Espulsioni:</strong></label><input type="number" name="espulsioni" value="<?= $record['espulsioni'] ?? '' ?>">
        </div>

        <div id="Difensore" class="role-block hidden">
            <label><strong>Gol Fatti:</strong></label><input type="number" name="gol_fatti" value="<?= $record['gol_fatti'] ?? '' ?>">
            <label><strong>Assist:</strong></label><input type="number" name="assist" value="<?= $record['assist'] ?? '' ?>">
            <label><strong>Ammonizioni:</strong></label><input type="number" name="ammonizioni" value="<?= $record['ammonizioni'] ?? '' ?>">
            <label><strong>Espulsioni:</strong></label><input type="number" name="espulsioni" value="<?= $record['espulsioni'] ?? '' ?>">
            <label><strong>Ruolo:</strong></label>
            <select name="ruolo_dif">
                <option value="centrale" <?= ($record['ruolo'] ?? '') == 'centrale' ? 'selected':'' ?>>Centrale</option>
                <option value="terzino" <?= ($record['ruolo'] ?? '') == 'terzino' ? 'selected':'' ?>>Terzino</option>
                <option value="braccetto" <?= ($record['ruolo'] ?? '') == 'braccetto' ? 'selected':'' ?>>Braccetto</option>
            </select>
        </div>

        <div id="Centrocampista" class="role-block hidden">
            <label><strong>Gol Fatti:</strong></label><input type="number" name="gol_fatti" value="<?= $record['gol_fatti'] ?? '' ?>">
            <label><strong>Assist:</strong></label><input type="number" name="assist" value="<?= $record['assist'] ?? '' ?>">
            <label><strong>Ammonizioni:</strong></label><input type="number" name="ammonizioni" value="<?= $record['ammonizioni'] ?? '' ?>">
            <label><strong>Espulsioni:</strong></label><input type="number" name="espulsioni" value="<?= $record['espulsioni'] ?? '' ?>">
            <label><strong>Ruolo:</strong></label>
            <select name="ruolo_cen">
                <option value="mediano" <?= ($record['ruolo'] ?? '') == 'mediano' ? 'selected':'' ?>>Mediano</option>
                <option value="mezz ala" <?= ($record['ruolo'] ?? '') == 'mezz ala' ? 'selected':'' ?>>Mezz Ala</option>
                <option value="trequartista" <?= ($record['ruolo'] ?? '') == 'trequartista' ? 'selected':'' ?>>Trequartista</option>
                <option value="centrale" <?= ($record['ruolo'] ?? '') == 'centrale' ? 'selected':'' ?>>Centrale</option>
            </select>
        </div>

        <div id="Attaccante" class="role-block hidden">
            <label><strong>Gol Fatti:</strong></label><input type="number" name="gol_fatti" value="<?= $record['gol_fatti'] ?? '' ?>">
            <label><strong>Assist:</strong></label><input type="number" name="assist" value="<?= $record['assist'] ?? '' ?>">
            <label><strong>Ammonizioni:</strong></label><input type="number" name="ammonizioni" value="<?= $record['ammonizioni'] ?? '' ?>">
            <label><strong>Espulsioni:</strong></label><input type="number" name="espulsioni" value="<?= $record['espulsioni'] ?? '' ?>">
            <label><strong>Ruolo:</strong></label>
            <select name="ruolo_att">
                <option value="punta" <?= ($record['ruolo'] ?? '') == 'punta' ? 'selected':'' ?>>Punta</option>
                <option value="seconda punta" <?= ($record['ruolo'] ?? '') == 'seconda punta' ? 'selected':'' ?>>Seconda Punta</option>
                <option value="ala" <?= ($record['ruolo'] ?? '') == 'ala' ? 'selected':'' ?>>Ala</option>
                <option value="falso 9" <?= ($record['ruolo'] ?? '') == 'falso 9' ? 'selected':'' ?>>Falso 9</option>
            </select>
        </div>

        <button type="submit" name="update">Salva Modifiche</button>
    </form>
    <?php endif; ?>
    <footer>
        <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
    </footer>
    <script>
        const selectRuolo = document.getElementById("ruolo");
        const blocchi = {
            "Portiere": document.getElementById("Portiere"),
            "Difensore": document.getElementById("Difensore"),
            "Centrocampista": document.getElementById("Centrocampista"),
            "Attaccante": document.getElementById("Attaccante")
        };
        function mostraBloccoRuolo() {
            for (const blocco of Object.values(blocchi)) {
                blocco.classList.add("hidden");
            }
            if (blocchi[selectRuolo.value]) {
                blocchi[selectRuolo.value].classList.remove("hidden");
            }
        }
        selectRuolo.addEventListener("change", mostraBloccoRuolo);
        mostraBloccoRuolo();
    </script>
</body>
</html>
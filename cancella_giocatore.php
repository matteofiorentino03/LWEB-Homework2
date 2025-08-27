<?php
session_start();

if (!isset($_SESSION['Username']) || ($_SESSION['Ruolo'] ?? '') !== 'admin') {
    header("Location: entering.html");
    exit();
}

/* Logout coerente con le altre pagine */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entering.html");
    exit();
}

/* ===== Connessione DB ===== */
$mysqli = new mysqli("localhost", "root", "", "playerbase2");
if ($mysqli->connect_error) {
    die("Connessione fallita: " . $mysqli->connect_error);
}

$errore = "";
$successo = "";
$giocatori = [];

/* ===== Carica elenco giocatori (tabella: Giocatori) ===== */
$sqlList = "SELECT ID, nome, cognome, cf FROM Giocatori ORDER BY cognome ASC, nome ASC";
if ($res = $mysqli->query($sqlList)) {
    while ($row = $res->fetch_assoc()) {
        $giocatori[] = $row;
    }
    $res->free();
} else {
    $errore = "Errore nel caricamento elenco giocatori: " . $mysqli->error;
}

/* ===== Eliminazione ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ID']) && $_POST['ID'] !== "") {
    $id = (int)$_POST['ID'];

    // Usiamo una transazione per coerenza
    $mysqli->begin_transaction();

    try {
        // Elimina dalle tabelle ruolo-specifiche + eventuali record in Agisce
        $stmt = $mysqli->prepare("DELETE FROM Portieri        WHERE ID_giocatore = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $mysqli->prepare("DELETE FROM Difensori       WHERE ID_giocatore = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $mysqli->prepare("DELETE FROM Centrocampisti  WHERE ID_giocatore = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $mysqli->prepare("DELETE FROM Attaccanti      WHERE ID_giocatore = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Tabella Agisce (assumo chiave esterna ID_giocatore)
        $stmt = $mysqli->prepare("DELETE FROM Agisce          WHERE ID_giocatore = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Infine elimina dalla tabella principale
        $stmt = $mysqli->prepare("DELETE FROM Giocatori WHERE ID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Se vuoi essere pignolo: controlla che abbia eliminato 1 riga da Giocatori
        if ($stmt->affected_rows !== 1) {
            throw new Exception("Nessun giocatore eliminato (ID non trovato?).");
        }

        $mysqli->commit();
        $successo = "Giocatore eliminato con successo.";
    } catch (Exception $e) {
        $mysqli->rollback();
        $errore = "Eliminazione annullata: " . $e->getMessage();
    }

    // Ricarica elenco dopo l'eliminazione
    $giocatori = [];
    if ($res = $mysqli->query($sqlList)) {
        while ($row = $res->fetch_assoc()) {
            $giocatori[] = $row;
        }
        $res->free();
    }
}

$homepage_link = 'homepage_admin.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elimina Giocatore</title>
    <link rel="stylesheet" href="styles/style_elimina_g.css">
</head>
<body>
<header>
  <div class="header-left">
    <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
      <div class="logo-container">
        <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo">
      </div>
    </a>
  </div>

  <div class="header-center">
    <h1><a href="<?= htmlspecialchars($homepage_link) ?>" class="brand">PLAYERBASE</a></h1>
  </div>

  <div class="header-right utente-container">
    <div class="logout"><a href="?logout=true">Logout</a></div>
  </div>
</header>

<main class="page">
    <section class="card">
        <h2 class="page-title">Elimina un Giocatore</h2>

        <?php if ($errore):   ?><p class="alert alert-error"><?= $errore ?></p><?php endif; ?>
        <?php if ($successo): ?><p class="alert alert-success"><?= $successo ?></p><?php endif; ?>

        <form method="post" class="narrow" onsubmit="return confermaEliminazione();">
            <label class="label" for="ID">Seleziona Giocatore:</label>
            <select name="ID" id="ID" required>
                <option value="">-- Seleziona --</option>
                <?php foreach ($giocatori as $g): ?>
                    <option value="<?= (int)$g['ID'] ?>">
                        <?= htmlspecialchars($g['cognome'] . ' ' . $g['nome']) ?> (<?= htmlspecialchars($g['cf']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-danger">Elimina Giocatore</button>
        </form>
    </section>
</main>

<footer>
    <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>

<script>
function confermaEliminazione() {
    const sel = document.getElementById('ID');
    const txt = sel.options[sel.selectedIndex]?.text || 'questo giocatore';
    return confirm(`Confermi l'eliminazione di ${txt} e di tutti i relativi dati ruolo/azioni?`);
}
</script>
</body>
</html>
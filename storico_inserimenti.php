<?php
session_start();

/* Auth + logout */
if (!isset($_SESSION['Username'])) {
    header("Location: entering.html"); exit();
}
if (isset($_GET['logout'])) {
    session_unset(); session_destroy();
    header("Location: entering.html"); exit();
}

$ruolo = $_SESSION['Ruolo'] ?? 'utente';
$homepage_link = ($ruolo === 'admin') ? 'homepage_admin.php' : 'homepage_user.php';

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}

/* --- Storico GIOCATORI (Agisce + Utenti + Giocatori) --- */
$sqlGioc = "
    SELECT 
        a.data_inserimento AS data_ins,
        u.username         AS utente,
        g.cf               AS cf,
        CONCAT(g.nome, ' ', g.cognome) AS nome_cognome
    FROM Agisce a
    JOIN Utenti u     ON a.ID_utenti    = u.ID
    JOIN Giocatori g  ON a.ID_giocatore = g.ID
    ORDER BY a.data_inserimento DESC, g.cognome ASC, g.nome ASC";
$giocatori = $conn->query($sqlGioc);

/* --- Storico MAGLIE (solo tipo/taglia/stagione) --- */
$sqlMaglie = "SELECT ID, tipo, taglia, stagione FROM Maglie ORDER BY ID DESC";
$maglie = $conn->query($sqlMaglie);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Storico Inserimenti</title>
  <link rel="stylesheet" href="styles/style_storico.css">
</head>
<body>
<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container">
      <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo">
    </div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container">
    <div class="logout"><a href="?logout=true">Logout</a></div>
  </div>
</header>

<main class="main-container">

    <!-- ================= GIOCATORI ================= -->
    <h2>Storico Inserimenti (Giocatori)</h2>
    <div class="table-wrapper">
    <table>
        <thead>
        <tr>
            <th>CF Giocatore</th>
            <th>Nome e Cognome</th>
            <th>Utente</th>
            <th>Data inserimento</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $sqlGiocatori = "
        SELECT g.cf, CONCAT(g.nome, ' ', g.cognome) AS nome_cognome, u.username, a.data_inserimento
        FROM Agisce a
        JOIN Giocatori g ON a.ID_giocatore = g.ID
        JOIN Utenti u ON a.ID_utenti = u.ID
        ORDER BY a.data_inserimento DESC
        ";
        $resG = $conn->query($sqlGiocatori);

        if ($resG && $resG->num_rows):
        while ($row = $resG->fetch_assoc()):
        ?>
            <tr>
            <td><?= htmlspecialchars($row['cf']) ?></td>
            <td><?= htmlspecialchars($row['nome_cognome']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['data_inserimento']) ?></td>
            </tr>
        <?php
        endwhile;
        else:
        ?>
        <tr><td colspan="4" class="empty">Nessun inserimento trovato.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <br>
        <!-- ================= MAGLIE ================= -->
        <h2>Maglie inserite</h2>
        <div class="table-wrapper">
        <?php
        $sqlMaglie = "
            SELECT
                tipo,
                stagione,
                GROUP_CONCAT(DISTINCT taglia ORDER BY FIELD(taglia,'S','M','L','XL') SEPARATOR ', ') AS taglie,
                GROUP_CONCAT(DISTINCT Sponsor ORDER BY Sponsor ASC SEPARATOR ', ') AS sponsor,
                MIN(path_immagine) AS img
            FROM Maglie
            GROUP BY tipo, stagione
            ORDER BY stagione DESC, FIELD(tipo,'casa','fuori','terza','portiere')
        ";
        $resM = $conn->query($sqlMaglie);
        ?>
        <table>
        <thead>
            <tr>
            <th>Immagine</th>
            <th>Tipo</th>
            <th>Taglie</th>
            <th>Stagione</th>
            <th>Sponsor</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($resM && $resM->num_rows): ?>
            <?php while ($row = $resM->fetch_assoc()): ?>
            <tr>
                <td>
                <?php
                    $rel = $row['img'] ?? '';
                    $abs = $rel ? __DIR__ . '/' . str_replace('\\','/',$rel) : '';
                    if ($rel && is_file($abs)):
                ?>
                    <img src="<?= htmlspecialchars($rel) ?>" alt="Maglia" style="width:50px; height:auto;">
                <?php else: ?>
                    <span style="color:grey;">—</span>
                <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(ucfirst($row['tipo'])) ?></td>
                <td><?= htmlspecialchars($row['taglie']) ?></td>
                <td><?= htmlspecialchars($row['stagione']) ?></td>
                <td><?= $row['sponsor'] ? htmlspecialchars($row['sponsor']) : '—' ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="empty">Nessuna maglia trovata.</td></tr>
        <?php endif; ?>
        </tbody>
        </table>
        </div>

</main>

<footer>
  <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
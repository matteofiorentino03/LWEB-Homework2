<?php
session_start();

/* Utente deve essere loggato */
if (!isset($_SESSION['Username'])) {
    header("Location: entering.html");
    exit();
}

$loggedUser = $_SESSION['Username'];
$ruolo = isset($_SESSION['Ruolo']) ? strtolower($_SESSION['Ruolo']) : null;
$isAdmin = ($ruolo === 'admin');

/* DB */
$conn = new mysqli("localhost", "root", "", "playerbase2");
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

/* ID ordine */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Ordine non valido.");
}
$ordineId = (int)$_GET['id'];

/* Dati ordine + logo e prezzi normalizzati */
$sql = "
SELECT 
    c.ID AS ordine_id,
    u.username,
    m.tipo, m.stagione, m.taglia,
    COALESCE(mgj.Logo, mp.Logo)              AS logo,
    g.nome, g.cognome,
    mp.nome            AS pers_nome, 
    mp.num_maglia      AS pers_num,
    m.costo_fisso,
    COALESCE(mgj.Supplemento, mp.supplemento, 0) AS supplemento,
    c.pagamento_finale,
    c.indirizzo_consegna, 
    c.data_compra
FROM Compra c
JOIN Utenti u                      ON u.ID = c.ID_Utente
JOIN Maglie m                      ON m.ID = c.ID_Maglia
LEFT JOIN Maglie_Giocatore mgj     ON mgj.ID_Maglia = m.ID
LEFT JOIN Giocatori g              ON g.ID = mgj.ID_Giocatore
LEFT JOIN Maglie_Personalizzate mp ON mp.ID_Maglia = m.ID
WHERE c.ID = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ordineId);
$stmt->execute();
$res    = $stmt->get_result();
$ordine = $res->fetch_assoc();
$stmt->close();

if (!$ordine) {
    die("Ordine non trovato.");
}

/* Autorizzazione: l'ordine deve appartenere all'utente (oppure admin) */
if (!$isAdmin && strcasecmp($ordine['username'], $loggedUser) !== 0) {
    http_response_code(403);
    die("Non sei autorizzato a visualizzare questo ordine.");
}

/* Dettaglio maglia (logo mostrato per entrambe le casistiche quando presente) */
$logo = $ordine['logo'] ?? '';
$base = sprintf("%s • %s • %s", $ordine['tipo'], $ordine['stagione'], $ordine['taglia']);

if (!empty($ordine['pers_nome'])) {
    // Personalizzata
    $dettaglio = $base
               . ($logo ? " • ".$logo : "")
               . " • Personalizzata: {$ordine['pers_nome']} #{$ordine['pers_num']}";
} elseif (!empty($ordine['nome'])) {
    // Maglia giocatore
    $dettaglio = $base
               . ($logo ? " • ".$logo : "")
               . " • {$ordine['nome']} {$ordine['cognome']}";
} else {
    // Solo maglia
    $dettaglio = $base . ($logo ? " • ".$logo : "");
}

/* Totale: usa pagamento_finale se valorizzato, altrimenti calcola */
if (!empty($ordine['pagamento_finale']) && (float)$ordine['pagamento_finale'] > 0) {
    $totale = (float)$ordine['pagamento_finale'];
} else {
    $totale = (float)$ordine['costo_fisso'] + (float)$ordine['supplemento'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Stampa Ordine #<?= htmlspecialchars($ordineId) ?></title>
  <link rel="stylesheet" href="styles/style_stampa_ordine.css">
</head>
<body>
  <!-- HEADER (senza logout) -->
  <header>
    <a href="<?= $isAdmin ? 'homepage_admin.php' : 'homepage_user.php' ?>" class="header-link">
      <div class="logo-container">
        <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo">
      </div>
    </a>
    <h1><a href="<?= $isAdmin ? 'homepage_admin.php' : 'homepage_user.php' ?>" style="color: inherit; text-decoration: none;">PLAYERBASE</a></h1>
  </header>

  <!-- CONTENUTO PRINCIPALE -->
  <div class="invoice">
    <h2 class="order-title">Riepilogo Ordine #<?= htmlspecialchars($ordineId) ?></h2>

    <table class="table">
      <tr>
        <th>Utente</th>
        <td><?= htmlspecialchars($ordine['username']) ?></td>
      </tr>
      <tr>
        <th>Dettaglio Maglia</th>
        <td><?= htmlspecialchars($dettaglio) ?></td>
      </tr>
      <tr>
        <th>Pagamento</th>
        <td class="money"><?= number_format($totale, 2, ',', '.') ?> €</td>
      </tr>
      <tr>
        <th>Indirizzo di consegna</th>
        <td><?= htmlspecialchars($ordine['indirizzo_consegna']) ?></td>
      </tr>
      <tr>
        <th>Data acquisto</th>
        <td><?= htmlspecialchars($ordine['data_compra']) ?></td>
      </tr>
    </table>

    <div class="actions no-print">
      <button class="btn" onclick="window.print()">Stampa PDF</button>
    </div>
  </div>

  <footer class="no-print">
    <p>&copy; 2025 Playerbase - Tutti i diritti riservati</p>
  </footer>
</body>
</html>
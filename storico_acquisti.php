<?php
session_start();

/* Solo admin loggato può accedere */
if (!isset($_SESSION['Username']) || strtolower($_SESSION['Ruolo']) !== 'admin') {
    header("Location: entering.html");
    exit();
}

/* --- Logout opzionale --- */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: homepage_user.php");
    exit();
}
$homepage_link = "homepage_admin.php";

/* Connessione DB */
$conn = new mysqli("localhost", "root", "", "playerbase2");
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

/* Query: includo logo e supplemento da entrambe le tabelle; porto anche pagamento_finale */
$sql = "
SELECT 
    u.username,
    m.tipo, m.stagione, m.taglia,
    COALESCE(mgj.Logo, mp.Logo)                     AS logo,
    g.nome, g.cognome,
    mp.nome                                         AS pers_nome,
    mp.num_maglia                                   AS pers_num,
    m.costo_fisso,
    COALESCE(mgj.Supplemento, mp.supplemento, 0)    AS supplemento,
    c.pagamento_finale,
    c.indirizzo_consegna,
    c.data_compra
FROM Compra c
JOIN Utenti u                      ON u.ID = c.ID_Utente
JOIN Maglie m                      ON m.ID = c.ID_Maglia
LEFT JOIN Maglie_Giocatore mgj     ON mgj.ID_Maglia = m.ID
LEFT JOIN Giocatori g              ON g.ID = mgj.ID_Giocatore
LEFT JOIN Maglie_Personalizzate mp ON mp.ID_Maglia = m.ID
ORDER BY c.data_compra DESC, c.ID DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Storico Acquisti</title>
  <link rel="stylesheet" href="styles/style_storico_acquisti.css">
</head>
<body>
<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container"><img src="img/AS_Roma_Logo_2017.svg.png" class="logo" alt="Logo"></div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container">
    <div class="logout"><a href="?logout=true">Logout</a></div>
  </div>
</header>

<div class="main-container">
  <h2>Storico acquisti</h2>
  <br>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>Dettaglio Maglia</th>
          <th>Pagamento (€)</th>
          <th>Indirizzo</th>
          <th>Data acquisto</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            // Dettaglio con logo dove disponibile
            $logo = $row['logo'] ?? '';
            $base = sprintf('%s • %s • %s', $row['tipo'], $row['stagione'], $row['taglia']);

            if (!empty($row['pers_nome'])) {
                // Personalizzata
                $dettaglio = $base
                           . ($logo ? ' • '.$logo : '')
                           . ' • Personalizzata: ' . $row['pers_nome'] . ' #' . $row['pers_num'];
            } elseif (!empty($row['nome'])) {
                // Maglia giocatore
                $dettaglio = $base
                           . ($logo ? ' • '.$logo : '')
                           . ' • ' . $row['nome'] . ' ' . $row['cognome'];
            } else {
                // Solo maglia
                $dettaglio = $base . ($logo ? ' • '.$logo : '');
            }

            // Totale: usa pagamento_finale se presente, altrimenti costo_fisso + supplemento
            if (!empty($row['pagamento_finale']) && (float)$row['pagamento_finale'] > 0) {
                $totale = (float)$row['pagamento_finale'];
            } else {
                $totale = (float)$row['costo_fisso'] + (float)$row['supplemento'];
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($dettaglio) ?></td>
            <td><?= number_format($totale, 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($row['indirizzo_consegna']) ?></td>
            <td><?= htmlspecialchars($row['data_compra']) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center;">Nessun acquisto registrato.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer>
  <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
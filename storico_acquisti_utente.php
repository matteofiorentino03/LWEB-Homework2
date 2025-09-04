<?php
session_start();

/* Solo utente loggato */
if (!isset($_SESSION['Username'])) {
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
$homepage_link = (isset($_SESSION['Ruolo']) && strtolower($_SESSION['Ruolo']) === 'admin')
    ? 'homepage_admin.php'
    : 'homepage_user.php';

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}
/* Ricavo ID dell'utente loggato */
$sqlUser = "SELECT ID, username FROM Utenti WHERE username = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("s", $_SESSION['Username']);
$stmtUser->execute();
$userRes = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$userRes) die("Utente non trovato.");
$userId   = (int)$userRes['ID'];
$username = $userRes['username'];

/* Query acquisti dell'utente
   - logo_any: prende il logo da Maglie_Giocatore oppure da Maglie_Personalizzate
   - totale: se Compra.pagamento_finale è nullo/0, calcola base + supplemento
*/
$sql = "
SELECT 
    c.ID AS ordine_id,
    m.tipo, m.stagione, m.taglia,
    COALESCE(mgj.Logo, mp.Logo)            AS logo_any,
    g.nome, g.cognome,                     -- valorizzati se maglia giocatore
    mp.nome  AS pers_nome,                 -- valorizzati se personalizzata
    mp.num_maglia AS pers_num,
    c.pagamento_finale,
    m.costo_fisso,
    COALESCE(mgj.Supplemento, mp.supplemento, 0) AS supplemento,
    -- totale visualizzato robusto
    CASE 
      WHEN c.pagamento_finale IS NULL OR c.pagamento_finale = 0 
        THEN (m.costo_fisso + COALESCE(mgj.Supplemento, mp.supplemento, 0))
      ELSE c.pagamento_finale
    END AS totale,
    c.indirizzo_consegna, 
    c.data_compra
FROM Compra c
JOIN Maglie m                   ON m.ID = c.ID_Maglia
LEFT JOIN Maglie_Giocatore mgj  ON mgj.ID_Maglia = m.ID
LEFT JOIN Giocatori g           ON g.ID = mgj.ID_Giocatore
LEFT JOIN Maglie_Personalizzate mp ON mp.ID_Maglia = m.ID
WHERE c.ID_Utente = ?
ORDER BY c.data_compra DESC, c.ID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$acquisti = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>I miei acquisti</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- usa il tuo CSS già creato per questa pagina -->
  <link rel="stylesheet" href="styles/style_storico_acquisti_u.css">
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
      <div class="logout"><a href="?logout=true"><p>Logout</p></a></div>
  </div>
</header>

<div class="main-container">
  <h2>I miei acquisti</h2>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Dettaglio Maglia</th>
          <th>Pagamento (€)</th>
          <th>Indirizzo</th>
          <th>Data acquisto</th>
          <th>Azioni</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($acquisti && $acquisti->num_rows): ?>
        <?php while ($row = $acquisti->fetch_assoc()): ?>
          <tr>
            <td>
              <?php
                // Costruzione descrizione con logo quando disponibile in entrambi i casi
                $base = sprintf("%s • %s • %s",
                        $row['tipo'], $row['stagione'], $row['taglia']);

                if (!empty($row['pers_nome'])) {
                    // Personalizzata (logo opzionale mostrato se presente)
                    $dett = $base
                          . (!empty($row['logo_any']) ? " • ".$row['logo_any'] : "")
                          . " • Personalizzata: ".$row['pers_nome']." #".$row['pers_num'];
                } elseif (!empty($row['nome'])) {
                    // Maglia giocatore
                    $dett = $base
                          . (!empty($row['logo_any']) ? " • ".$row['logo_any'] : "")
                          . " • ".$row['nome']." ".$row['cognome'];
                } else {
                    // Solo maglia
                    $dett = $base;
                }
                echo htmlspecialchars($dett);
              ?>
            </td>
            <td>
              <?= number_format((float)$row['totale'], 2, ',', '.') ?>
            </td>
            <td><?= htmlspecialchars($row['indirizzo_consegna']) ?></td>
            <td><?= htmlspecialchars($row['data_compra']) ?></td>
            <td>
              <a class="btn-print" 
                 href="stampa_ordine.php?id=<?= (int)$row['ordine_id'] ?>" 
                 target="_blank" rel="noopener">
                Stampa PDF
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center;color:#666;font-style:italic;">Nessun acquisto effettuato.</td></tr>
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
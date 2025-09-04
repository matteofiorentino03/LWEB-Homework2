<?php
session_start();

/* ================== Auth & logout ================== */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entering.html");
    exit();
}

if (!isset($_SESSION['Username']) || strtolower($_SESSION['Ruolo'] ?? '') !== 'admin') {
    header("Location: entering.html");
    exit();
}

$homepage_link = 'homepage_admin.php';

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}

$msg_ok = $_SESSION['flash_ok'] ?? '';
$msg_err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

/* ================== Azioni (POST) ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_richiesta'])) {
    $id_richiesta = (int)$_POST['id_richiesta'];

    // Carica richiesta + utente
    $q = $conn->prepare("
        SELECT r.importo, r.stato, r.user_id,
               u.username, u.status, COALESCE(u.crediti,0) AS crediti
        FROM crediti_richieste r
        JOIN utenti u ON u.ID = r.user_id
        WHERE r.id = ?");
    $q->bind_param("i", $id_richiesta);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();

    // Stato normalizzato
    $stato_norm = strtolower(trim(str_replace('_',' ', $row['stato'] ?? '')));

    if (!$row || $stato_norm !== 'in attesa') {
        $_SESSION['flash_err'] = "Richiesta non trovata o già processata.";
        header("Location: accettazione_crediti.php");
        exit();
    }

    if (isset($_POST['accetta'])) {
        $conn->begin_transaction();
        try {
            // Somma i crediti all'utente
            $upd1 = $conn->prepare("UPDATE utenti SET crediti = COALESCE(crediti,0) + ? WHERE ID = ?");
            $upd1->bind_param("di", $row['importo'], $row['user_id']);
            if (!$upd1->execute()) throw new Exception($upd1->error);
            $upd1->close();

            // Segna la richiesta come approvata
            $upd2 = $conn->prepare("UPDATE crediti_richieste SET stato='approvata' WHERE id=?");
            $upd2->bind_param("i", $id_richiesta);
            if (!$upd2->execute()) throw new Exception($upd2->error);
            $upd2->close();

            $conn->commit();
            $_SESSION['flash_ok'] = "Richiesta #{$id_richiesta} approvata. Crediti aggiornati.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_err'] = "Errore durante l'accettazione: " . $e->getMessage();
        }
    } elseif (isset($_POST['rifiuta'])) {
        $upd = $conn->prepare("UPDATE crediti_richieste SET stato='rifiutata' WHERE id=?");
        $upd->bind_param("i", $id_richiesta);
        if ($upd->execute()) {
            $_SESSION['flash_ok'] = "Richiesta #{$id_richiesta} rifiutata.";
        } else {
            $_SESSION['flash_err'] = "Errore durante il rifiuto: " . $upd->error;
        }
        $upd->close();
    }

    // Post/Redirect/Get
    header("Location: accettazione_crediti.php");
    exit();
}

/* ================== Elenco richieste in attesa ================== */
/* Filtro robusto: 'in attesa', 'In attesa', 'in_attesa', ecc. */
$sql = "
SELECT 
  r.importo, r.created_at, r.id,
  u.username, u.status, COALESCE(u.crediti,0) AS crediti
FROM crediti_richieste r
JOIN utenti u ON u.ID = r.user_id
WHERE TRIM(LOWER(REPLACE(r.stato, '_', ' '))) = 'in attesa'
ORDER BY r.created_at DESC, r.id DESC";
$rows = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Accettazione Crediti</title>
  <link rel="stylesheet" href="styles/style_acc_crediti.css">
</head>
<body>
<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container"><img src="img/AS_Roma_Logo_2017.svg.png" class="logo" alt="Logo AS Roma"></div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container">
    <div class="logout"><a href="?logout=true">Logout</a></div>
  </div>
</header>

<main class="main-container">
  <h2>Richieste crediti in attesa</h2>

  <?php if ($msg_err): ?><div class="alert error"><?= htmlspecialchars($msg_err) ?></div><?php endif; ?>
  <?php if ($msg_ok):  ?><div class="alert ok"><?= htmlspecialchars($msg_ok)  ?></div><?php endif; ?>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>Status Account</th>
          <th>Crediti attuali</th>
          <th>Crediti richiesti</th>
          <th>Data richiesta</th>
          <th>Azione</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['username']) ?></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td><?= number_format((float)$r['crediti'], 2, ',', '.') ?></td>
          <td><strong><?= number_format((float)$r['importo'], 2, ',', '.') ?></strong></td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
          <td class="actions">
            <form method="post" class="inline">
              <input type="hidden" name="id_richiesta" value="<?= (int)$r['id'] ?>">
              <button type="submit" name="accetta" class="btn-accept">Accetta</button>
              <button type="submit" name="rifiuta"  class="btn-reject">Rifiuta</button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7" style="text-align:center;">Nessuna richiesta in attesa.</td></tr>
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

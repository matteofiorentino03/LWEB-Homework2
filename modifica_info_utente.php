<?php
session_start();

// Controllo login utente normale
if (!isset($_SESSION['Username']) || strtolower($_SESSION['Ruolo']) !== 'utente') {
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
$homepage_link = 'homepage_user.php';

/* ================= DB ================= */
require_once __DIR__ . '/connect.php';

try {
    $conn = db();   // usa la funzione definita in connect.php
} catch (Throwable $e) {
    die("Errore DB: " . $e->getMessage());
}
$user_id = $_SESSION['ID_Utente'];

// Messaggi di esito
$msg_ok = $msg_err = "";

// Salvataggio modifiche info utente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_modifiche'])) {
    $cf       = trim($_POST['cf']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $upd = $conn->prepare("UPDATE utenti SET cf=?, username=?, Password_Utente=? WHERE ID=?");
    $upd->bind_param("sssi", $cf, $username, $password, $user_id);

    if ($upd->execute()) {
        $msg_ok = "Dati aggiornati con successo.";
        $_SESSION['Username'] = $username;
    } else {
        $msg_err = "Errore durante l'aggiornamento.";
    }
    $upd->close();
}

// Inserimento nuova richiesta crediti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invia_richiesta'])) {
    $importo = floatval($_POST['importo']);
    if ($importo > 0 && $importo <= 9999.99) {
        $ins = $conn->prepare("INSERT INTO crediti_richieste (user_id, importo, stato, created_at) VALUES (?, ?, 'In attesa', NOW())");
        $ins->bind_param("id", $user_id, $importo);
        if ($ins->execute()) {
            $msg_ok = "Richiesta di crediti inviata.";
        } else {
            $msg_err = "Errore durante l'invio della richiesta.";
        }
        $ins->close();
    } else {
        $msg_err = "Importo non valido.";
    }
}

// Dati utente
$sql_user = $conn->prepare("SELECT cf, username, Password_Utente, crediti FROM utenti WHERE ID=?");
$sql_user->bind_param("i", $user_id);
$sql_user->execute();
$res_user = $sql_user->get_result();
$utente = $res_user->fetch_assoc();
$sql_user->close();

// Richieste utente
$sql_rich = $conn->prepare("SELECT importo, created_at, stato FROM crediti_richieste WHERE user_id=? ORDER BY created_at DESC");
$sql_rich->bind_param("i", $user_id);
$sql_rich->execute();
$result_richieste = $sql_rich->get_result();
$sql_rich->close();

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Modifica Info Utente</title>
<link rel="stylesheet" href="styles/style_modifica_utente.css">
<style>
.stato-approvata {
  color: #0a6b2c;
  font-weight: bold;
}
.stato-rifiutata {
  color: #a30000;
  font-weight: bold;
}
.stato-in-attesa {
  color: #b8860b;
  font-weight: bold;
}
</style>
</head>
<body>

<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container"><img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo" class="logo"></div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container">
    <div class="logout"><a href="?logout=true">Logout</a></div>
  </div>
</header>

<div class="page">
    <h2 class="page-title">Modifica informazioni utente</h2>

    <?php if ($msg_err): ?><div class="alert alert-error"><?= $msg_err ?></div><?php endif; ?>
    <?php if ($msg_ok): ?><div class="alert alert-success"><?= $msg_ok ?></div><?php endif; ?>

    <form method="post" class="card narrow">
        <div class="grid">
            <div class="col">
                <label class="label">Codice Fiscale</label>
                <input type="text" name="cf" value="<?= htmlspecialchars($utente['cf']) ?>" class="input">
            </div>
            <div class="col">
                <label class="label">Crediti attuali</label>
                <input type="text" value="<?= number_format((float)$utente['crediti'], 2, ',', '.') ?>" class="input" readonly>
            </div>
            <div class="col">
                <label class="label">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($utente['username']) ?>" class="input">
            </div>
            <div class="col">
                <label class="label">Password</label>
                <input type="text" name="password" value="<?= htmlspecialchars($utente['Password_Utente']) ?>" class="input">
            </div>
        </div>
        <button type="submit" name="salva_modifiche" class="btn-submit">Salva modifiche</button>
    </form>

    <h2 class="page-title">Richiedi crediti</h2>
    <form method="post" class="card narrow">
        <label class="label">Importo da richiedere (max 9.999,99)</label>
        <input type="number" name="importo" step="0.01" max="9999.99" placeholder="Es. 25.00" class="input">
        <button type="submit" name="invia_richiesta" class="btn-submit">Invia richiesta</button>
    </form>

    <h2 class="page-title">Le mie richieste di crediti</h2>
    <div class="crediti-table-wrapper">
        <table class="crediti-table">
            <thead>
                <tr>
                    <th>Importo (€)</th>
                    <th>Data creazione</th>
                    <th>Stato richiesta</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result_richieste->num_rows > 0): ?>
                <?php while ($row = $result_richieste->fetch_assoc()): ?>
                    <tr>
                        <td><?= number_format((float)$row['importo'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="<?php
                            switch (strtolower($row['stato'])) {
                                case 'approvata':
                                    echo 'stato-approvata';
                                    break;
                                case 'rifiutata':
                                    echo 'stato-rifiutata';
                                    break;
                                default:
                                    echo 'stato-in-attesa';
                                    break;
                            }
                        ?>">
                            <?= htmlspecialchars($row['stato']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">Nessuna richiesta trovata</td></tr>
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

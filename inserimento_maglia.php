<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header("Location: entering.html");
    exit();
}

/* Logout */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
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
$ruolo = $_SESSION['Ruolo'] ?? 'utente';
$homepage_link = ($ruolo === 'admin') ? 'homepage_admin.php' : 'homepage_user.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['Tipo']        ?? null;
    $taglia      = $_POST['Taglia']      ?? null;
    $sponsor     = trim($_POST['Sponsor'] ?? '');       // opzionale
    $descrizione = trim($_POST['Descrizione'] ?? '');
    $stagione    = trim($_POST['Stagione']    ?? '');
    $costo       = isset($_POST['Costo']) ? (int)$_POST['Costo'] : 0;
    $immagine_path = null;

    // Validazioni
    if (!in_array($tipo, ['casa','fuori','terza', 'portiere'], true)) $errore .= "Tipo non valido.<br>";
    if (!in_array($taglia, ['S','M','L','XL'], true))     $errore .= "Taglia non valida.<br>";
    if ($descrizione === '' || $stagione === '')          $errore .= "Descrizione e stagione sono obbligatorie.<br>";
    if ($costo < 0)                                       $errore .= "Il costo non può essere negativo.<br>";
    if (mb_strlen($sponsor) > 40)                         $errore .= "Sponsor troppo lungo (max 40 caratteri).<br>";

    // Upload immagine (name='immagine')
    if (isset($_FILES['immagine'])) {
        $f = $_FILES['immagine'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $cartella = __DIR__ . "/img/maglie/";
            if (!is_dir($cartella) && !mkdir($cartella, 0777, true)) {
                $errore .= "Impossibile creare la cartella di upload.<br>";
            }
            if ($errore === "") {
                $est = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                $ok_ext = ['jpg','jpeg','png','webp','gif'];
                if (!in_array($est, $ok_ext, true)) {
                    $errore .= "Formato immagine non supportato (jpg, png, webp, gif).<br>";
                } else {
                    $nome_sicuro = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($f['name']));
                    $dest_rel = "img/maglie/" . time() . "_" . $nome_sicuro;
                    $dest_abs = __DIR__ . "/" . $dest_rel;
                    if (!move_uploaded_file($f['tmp_name'], $dest_abs)) {
                        $errore .= "Errore nel salvataggio dell'immagine.<br>";
                    } else {
                        $immagine_path = $dest_rel;
                    }
                }
            }
        } elseif ($f['error'] === UPLOAD_ERR_NO_FILE) {
            $errore .= "Nessuna immagine caricata.<br>";
        } else {
            $map = [
                UPLOAD_ERR_INI_SIZE   => "Il file supera il limite del server.",
                UPLOAD_ERR_FORM_SIZE  => "Il file supera il limite del form.",
                UPLOAD_ERR_PARTIAL    => "Upload parziale: riprova.",
                UPLOAD_ERR_NO_TMP_DIR => "Manca la cartella temporanea.",
                UPLOAD_ERR_CANT_WRITE => "Impossibile scrivere su disco.",
                UPLOAD_ERR_EXTENSION  => "Upload bloccato da un'estensione."
            ];
            $errore .= ($map[$f['error']] ?? "Errore sconosciuto durante l'upload.") . "<br>";
        }
    } else {
        $errore .= "Nessuna immagine ricevuta.<br>";
    }

    if ($errore === "") {
        // Sponsor è nullable: se vuoto passo NULL
        $sponsor_param = ($sponsor === '') ? null : $sponsor;

        $stmt = $conn->prepare("
            INSERT INTO Maglie (tipo, taglia, Sponsor, stagione, descrizione_maglia, costo_fisso, path_immagine)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssis",
            $tipo,
            $taglia,
            $sponsor_param,
            $stagione,
            $descrizione,
            $costo,
            $immagine_path
        );

        if ($stmt->execute()) $successo = "Maglia inserita con successo.";
        else                  $errore   = "Errore DB: " . $stmt->error;

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Inserisci Maglia</title>
  <link rel="stylesheet" href="styles/style_inserimenti_g.css">
</head>
<body>
<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container">
      <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo" />
    </div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container"><div class="logout"><a href="?logout=true">Logout</a></div></div>
</header>

<div class="main-container">
  <div class="table">
    <h2>Inserisci una Maglia</h2>
    <?php if ($errore)   echo "<p style='color:red;'>$errore</p>"; ?>
    <?php if ($successo) echo "<p style='color:green;'>$successo</p>"; ?>

    <form method="post" enctype="multipart/form-data">
      <label><strong>Tipo:</strong></label><br>
      <select name="Tipo" required>
        <option value="casa">Casa</option>
        <option value="fuori">Fuori</option>
        <option value="terza">Terza</option>
        <option value="portiere">Portiere</option>
      </select><br><br>

      <label><strong>Taglia:</strong></label><br>
      <select name="Taglia" required>
        <option value="S">S</option>
        <option value="M">M</option>
        <option value="L">L</option>
        <option value="XL">XL</option>
      </select><br><br>

      <input type="text" name="Sponsor" placeholder="Sponsor (opzionale, max 40)" maxlength="40"><br><br>
      <input type="text" name="Stagione" placeholder="Stagione (es: 2025/26)" required><br><br>
      <input type="text" name="Descrizione" placeholder="Descrizione Maglia" required><br><br>
      <input type="number" name="Costo" placeholder="Costo Fisso (€)" min="0" required><br><br>

      <label for="upload_immagine" class="custom-file-upload"><strong>Carica Immagine:</strong></label>
      <input id="upload_immagine" type="file" name="immagine" accept="image/*" required><br><br>

      <button type="submit">Salva Maglia</button>
    </form>
  </div>
</div>

<footer><p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p></footer>
</body>
</html>
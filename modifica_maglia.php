<?php
session_start();

/* Solo admin può modificare */
if (!isset($_SESSION['Username']) || $_SESSION['Ruolo'] !== 'admin') {
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

$errore = "";
$successo = "";
$record = null;

/* Elenco maglie per select */
$maglie = [];
$res = $conn->query("SELECT ID, tipo, taglia, stagione FROM Maglie ORDER BY stagione DESC, tipo ASC, taglia ASC");
while ($row = $res->fetch_assoc()) $maglie[] = $row;

/* Caricamento record scelto */
if (isset($_POST['select_id']) && $_POST['select_id'] !== "") {
    $id_sel = (int)$_POST['select_id'];
    $stmt = $conn->prepare("SELECT * FROM Maglie WHERE ID = ?");
    $stmt->bind_param("i", $id_sel);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res->num_rows ? $res->fetch_assoc() : null;
    if (!$record) $errore = "Maglia non trovata.";
}

/* Aggiornamento */
if (isset($_POST['update']) && isset($_POST['ID'])) {
    $id = (int)$_POST['ID'];

    $tipi_validi   = ['casa','fuori','terza','portiere'];
    $taglie_valide = ['S','M','L','XL'];

    $tipo        = $_POST['tipo'] ?? '';
    $taglia      = $_POST['taglia'] ?? '';
    $sponsor     = trim($_POST['sponsor'] ?? '');                 // <— NUOVO (opzionale)
    $descrizione = trim($_POST['descrizione_maglia'] ?? '');
    $stagione    = trim($_POST['stagione'] ?? '');
    $costo       = $_POST['costo_fisso'] ?? '';

    if (!in_array($tipo, $tipi_validi, true))       $errore .= "Tipo non valido.<br>";
    if (!in_array($taglia, $taglie_valide, true))   $errore .= "Taglia non valida.<br>";
    if (mb_strlen($sponsor) > 40)                   $errore .= "Sponsor troppo lungo (max 40 caratteri).<br>"; // <— NUOVO
    if ($descrizione === '')                        $errore .= "La descrizione è obbligatoria.<br>";
    if ($stagione === '')                           $errore .= "La stagione è obbligatoria (es: 2025/26).<br>";
    if (!is_numeric($costo) || (int)$costo < 0)     $errore .= "Il costo fisso deve essere un intero ≥ 0.<br>";

    /* Upload immagine (opzionale) */
    $nuovo_path = null;
    if ($errore === "" && isset($_FILES['immagine']) && $_FILES['immagine']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['immagine']['error'] !== UPLOAD_ERR_OK) {
            $errore .= "Errore durante l'upload dell'immagine (codice {$_FILES['immagine']['error']}).<br>";
        } else {
            if ($_FILES['immagine']['size'] > 5 * 1024 * 1024) {
                $errore .= "Immagine troppo grande (max 5MB).<br>";
            } else {
                $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                $tmp  = $_FILES['immagine']['tmp_name'];
                $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : $_FILES['immagine']['type'];
                if (!isset($allowed[$mime])) {
                    $errore .= "Formato immagine non supportato (JPG, PNG, WEBP).<br>";
                } else {
                    $ext = $allowed[$mime];
                    $uploadDirFs  = __DIR__ . '/img/maglie/';
                    $uploadDirWeb = 'img/maglie/';
                    if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0777, true);

                    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destFs   = $uploadDirFs . $filename;
                    $destWeb  = $uploadDirWeb . $filename;

                    if (!move_uploaded_file($tmp, $destFs)) {
                        $errore .= "Impossibile spostare il file caricato.<br>";
                    } else {
                        $nuovo_path = $destWeb;

                        /* Elimina vecchio file se nella stessa cartella */
                        if (!empty($_POST['old_path'])) {
                            $oldFs = realpath(__DIR__ . '/' . str_replace(['\\'], '/', $_POST['old_path']));
                            $rootUpload = realpath($uploadDirFs);
                            if ($oldFs && $rootUpload && strpos($oldFs, $rootUpload) === 0 && is_file($oldFs)) {
                                @unlink($oldFs);
                            }
                        }
                    }
                }
            }
        }
    }

    if ($errore === "") {
        $costo_int = (int)$costo;
        $sponsor_or_null = ($sponsor === '') ? null : $sponsor;   // <— NUOVO

        if ($nuovo_path !== null) {
            $stmt = $conn->prepare("
                UPDATE Maglie
                   SET tipo=?, taglia=?, Sponsor=?, descrizione_maglia=?, stagione=?, costo_fisso=?, path_immagine=?
                 WHERE ID=?
            ");
            $stmt->bind_param("sssssss i",
                $tipo, $taglia, $sponsor_or_null, $descrizione, $stagione, $costo_int, $nuovo_path, $id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE Maglie
                   SET tipo=?, taglia=?, Sponsor=?, descrizione_maglia=?, stagione=?, costo_fisso=?
                 WHERE ID=?
            ");
            $stmt->bind_param("ssssssi",
                $tipo, $taglia, $sponsor_or_null, $descrizione, $stagione, $costo_int, $id
            );
        }

        if ($stmt->execute()) {
            $successo = "Maglia aggiornata con successo.";
            // ricarico record
            $stmt = $conn->prepare("SELECT * FROM Maglie WHERE ID=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
        } else {
            $errore = "Errore durante l'aggiornamento: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Maglia</title>
    <link rel="stylesheet" href="styles/style_modifica_m.css">
</head>
<body>
<header>
    <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
        <div class='logo-container'>
            <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo">
        </div>
    </a>
    <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color: inherit; text-decoration: none;">PLAYERBASE</a></h1>
    <div class="utente-container">
        <div class="logout"><a href="?logout=true"><p>Logout</p></a></div>
    </div>
</header>

<main class="page">
    <h2 class="page-title">Modifica Maglia</h2>

    <?php if ($errore)   echo '<div class="alert alert-error">'.$errore.'</div>'; ?>
    <?php if ($successo) echo '<div class="alert alert-success">'.$successo.'</div>'; ?>

    <!-- Selezione Maglia -->
    <form method="post" class="card narrow">
        <label for="select_id" class="label"><strong>Seleziona Maglia</strong></label>
        <select name="select_id" id="select_id" class="input" onchange="this.form.submit()">
            <option value="">-- Seleziona --</option>
            <?php foreach ($maglie as $m): ?>
                <option value="<?= (int)$m['ID']; ?>"
                    <?= (isset($record['ID']) && (int)$record['ID'] === (int)$m['ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($m['tipo'])) ?> • <?= htmlspecialchars($m['taglia']) ?> • <?= htmlspecialchars($m['stagione']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($record): ?>
    <form method="post" enctype="multipart/form-data" class="card" id="formMaglia">
        <input type="hidden" name="ID" value="<?= (int)$record['ID']; ?>">
        <input type="hidden" name="old_path" value="<?= htmlspecialchars($record['path_immagine'] ?? ''); ?>">

        <div class="grid">
            <div class="col">
                <label class="label" for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="input" required>
                    <option value="casa"  <?= $record['tipo']==='casa'  ? 'selected' : '' ?>>Casa</option>
                    <option value="fuori" <?= $record['tipo']==='fuori' ? 'selected' : '' ?>>Fuori</option>
                    <option value="terza" <?= $record['tipo']==='terza' ? 'selected' : '' ?>>Terza</option>
                    <option value="portiere" <?= $record['tipo']==='portiere' ? 'selected' : '' ?>>Portiere</option>
                </select>
            </div>

            <div class="col">
                <label class="label" for="taglia">Taglia</label>
                <select id="taglia" name="taglia" class="input" required>
                    <?php foreach (['S','M','L','XL'] as $t): ?>
                        <option value="<?= $t ?>" <?= $record['taglia']===$t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col">
                <label class="label" for="sponsor">Sponsor (opzionale)</label>
                <input type="text" id="sponsor" name="sponsor" maxlength="40"
                       class="input" value="<?= htmlspecialchars($record['Sponsor'] ?? ''); ?>" />
            </div>

            <div class="col">
                <label class="label" for="descrizione_maglia">Descrizione</label>
                <input type="text" id="descrizione_maglia" name="descrizione_maglia"
                       class="input" value="<?= htmlspecialchars($record['descrizione_maglia']); ?>" required />
            </div>

            <div class="col">
                <label class="label" for="stagione">Stagione (es: 2025/26)</label>
                <input type="text" id="stagione" name="stagione"
                       class="input" value="<?= htmlspecialchars($record['stagione']); ?>" required />
            </div>

            <div class="col">
                <label class="label" for="costo_fisso">Costo fisso (€)</label>
                <input type="number" id="costo_fisso" name="costo_fisso" min="0" step="1"
                       class="input" value="<?= (int)$record['costo_fisso']; ?>" required />
            </div>

            <div class="col col-image">
                <span class="label">Immagine attuale</span>
                <figure class="img-preview-wrap">
                    <img class="img-preview" id="preview" src="<?= htmlspecialchars($record['path_immagine'] ?? ''); ?>" alt="Immagine maglia">
                    <figcaption class="img-caption">
                        <?= !empty($record['path_immagine']) ? htmlspecialchars($record['path_immagine']) : 'Nessuna immagine salvata' ?>
                    </figcaption>
                </figure>

                <div class="file-control">
                    <label for="immagine" class="btn-file">Sostituisci immagine</label>
                    <input type="file" id="immagine" name="immagine" accept="image/*" class="file-hidden">
                    <small class="hint">Lascia vuoto per mantenere l’attuale. JPG/PNG/WEBP, max 5MB.</small>
                </div>
            </div>
        </div>

        <button type="submit" name="update" class="btn-submit">Salva Modifiche</button>
    </form>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>

<script>
document.getElementById('immagine')?.addEventListener('change', (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  const img = document.getElementById('preview');
  img.src = url;
});
</script>
</body>
</html>
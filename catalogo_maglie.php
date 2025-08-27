<?php
session_start();

/* ===== Header logic ===== */
$is_logged  = isset($_SESSION['Username']);
$ruolo      = $is_logged ? strtolower($_SESSION['Ruolo']) : '';
$is_admin   = ($ruolo === 'admin');
$homepage_link = $is_admin ? 'homepage_admin.php' : 'homepage_user.php';

/* Optional logout */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entering.html");
    exit();
}

/* ===== DB ===== */
$conn = new mysqli("localhost", "root", "", "playerbase2");
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

/* ===== Query: gruppi tipo+stagione con taglie e 1 immagine =====
   NB: se il costo_fisso può variare per taglia, mostriamo il MIN e il MAX.
*/
$sql = "
SELECT
  tipo,
  stagione,
  GROUP_CONCAT(DISTINCT taglia ORDER BY FIELD(taglia,'XS','S','M','L','XL','XXL','XXXL') SEPARATOR ', ') AS taglie,
  MIN(costo_fisso) AS prezzo_min,
  MAX(costo_fisso) AS prezzo_max,
  MIN(COALESCE(NULLIF(path_immagine,''), '')) AS img_any
FROM Maglie
GROUP BY tipo, stagione
ORDER BY stagione DESC, FIELD(tipo,'casa','fuori','terza','portiere'), tipo ASC
";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <title>Catalogo Maglie</title>
  <link rel="stylesheet" href="styles/style_catalogo_maglia.css" />
</head>
<body>
<header>
  <a href="<?= htmlspecialchars($homepage_link) ?>" class="header-link">
    <div class="logo-container">
      <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo" />
    </div>
  </a>
  <h1><a href="<?= htmlspecialchars($homepage_link) ?>" style="color:inherit;text-decoration:none;">PLAYERBASE</a></h1>
  <div class="utente-container">
    <?php if ($is_logged): ?>
      <div class="logout"><a href="?logout=true">Logout</a></div>
    <?php else: ?>
      <div class="logout"><a href="entering.html">Login/Registrati</a></div>
    <?php endif; ?>
  </div>
</header>

<main class="page">
  <h2 class="page-title">Catalogo maglie</h2>

  <?php if ($res && $res->num_rows > 0): ?>
    <section class="cards">
      <?php while ($row = $res->fetch_assoc()): ?>
        <?php
          // Immagine: verifica file sul disco per evitare <img> rotte
          $rel = $row['img_any'] ?? '';
          $abs = $rel ? (__DIR__ . '/' . str_replace('\\','/',$rel)) : '';
          $hasImg = $rel && is_file($abs);

          // Tipo con maiuscola iniziale
          $tipoLabel = ucfirst($row['tipo']);

          // Prezzo: mostra singolo valore o range
          $pmin = (float)$row['prezzo_min'];
          $pmax = (float)$row['prezzo_max'];
          $prezzo = ($pmin === $pmax)
            ? number_format($pmin, 2, ',', '.').' €'
            : number_format($pmin, 2, ',', '.')."–".number_format($pmax, 2, ',', '.')." €";
        ?>
        <article class="card">
          <div class="card__media">
            <?php if ($hasImg): ?>
            <form action="compra_maglia.php" method="post" style="display:inline;">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($row['tipo']) ?>">
                <input type="hidden" name="stagione" value="<?= htmlspecialchars($row['stagione']) ?>">
                <button type="submit" style="border:none; background:none; padding:0;">
                  <img src="<?= htmlspecialchars($rel) ?>" alt="Maglia <?= htmlspecialchars($tipoLabel) ?> <?= htmlspecialchars($row['stagione']) ?>">
                </button>
              </form>
            <?php else: ?>
              <div class="placeholder">
                <span>Immagine non disponibile</span>
              </div>
            <?php endif; ?>
            <div class="badge badge--tipo"><?= htmlspecialchars($tipoLabel) ?></div>
            <div class="badge badge--stagione"><?= htmlspecialchars($row['stagione']) ?></div>
          </div>

          <div class="card__body">
            <h3 class="card__title"><?= htmlspecialchars($tipoLabel) ?> • <?= htmlspecialchars($row['stagione']) ?></h3>

            <div class="field">
              <span class="field__label">Taglie:</span>
              <span class="chips"><?= htmlspecialchars($row['taglie'] ?: '-') ?></span>
            </div>

            <div class="field">
              <span class="field__label">Prezzo:</span>
              <span class="price"><?= $prezzo ?></span>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    </section>
  <?php else: ?>
    <p class="empty">Nessuna maglia presente a catalogo.</p>
  <?php endif; ?>
</main>

<footer>
  <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>
</body>
</html>

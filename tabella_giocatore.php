<?php
session_start();

/* Stato sessione */
$is_logged = isset($_SESSION['Username']);
$ruolo     = $is_logged && isset($_SESSION['Ruolo']) ? strtolower($_SESSION['Ruolo']) : null;
$is_admin  = ($ruolo === 'admin');

/* L’admin DEVE essere loggato per vedere la pagina */
if ($ruolo === 'admin' && !$is_logged) {
    header("Location: entering.html");
    exit();
}

/* Link home:
   - admin loggato -> homepage_admin.php
   - altrimenti (guest o utente loggato) -> homepage_user.php
*/
$homepage_link = ($is_admin ? 'homepage_admin.php' : 'homepage_user.php');

/* Logout */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: entering.html");
    exit();
}

/* DB playerbase2 (lettura) */
$conn = new mysqli("localhost", "root", "", "playerbase2");
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

/* Query giocatori + ruolo */
$sql = "
SELECT 
    g.ID,
    g.cf,
    g.nome,
    g.cognome,
    g.nazionalita,
    g.datanascita,
    g.num_maglia,
    g.altezza,
    g.market_value,
    g.presenze,
    g.cod_contratto,
    g.Data_inizio,
    g.Tipo_Contratto,
    g.Data_scadenza,
    g.stipendio,

    -- Portieri
    p.gol_subiti  AS P_gol_subiti,
    p.gol_fatti   AS P_gol_fatti,
    p.assist      AS P_assist,
    p.clean_sheet AS P_clean_sheet,
    p.ammonizioni AS P_ammonizioni,
    p.espulsioni  AS P_espulsioni,

    -- Difensori
    d.gol_fatti   AS D_gol_fatti,
    d.assist      AS D_assist,
    d.ruolo       AS D_ruolo,
    d.ammonizioni AS D_Ammonizioni,
    d.espulsioni  AS D_Espulsioni,

    -- Centrocampisti
    c.gol_fatti   AS C_gol_fatti,
    c.assist      AS C_assist,
    c.ruolo       AS C_ruolo,
    c.ammonizioni AS C_Ammonizioni,
    c.espulsioni  AS C_Espulsioni,

    -- Attaccanti
    a.gol_fatti   AS A_gol_fatti,
    a.assist      AS A_assist,
    a.ruolo       AS A_ruolo,
    a.ammonizioni AS A_Ammonizioni,
    a.espulsioni  AS A_Espulsioni,

    CASE
        WHEN p.ID_giocatore IS NOT NULL THEN 'Portiere'
        WHEN d.ID_giocatore IS NOT NULL THEN 'Difensore'
        WHEN c.ID_giocatore IS NOT NULL THEN 'Centrocampista'
        WHEN a.ID_giocatore IS NOT NULL THEN 'Attaccante'
        ELSE 'Ruolo non specificato'
    END AS Ruolo
FROM Giocatori g
LEFT JOIN Portieri       p ON g.ID = p.ID_giocatore
LEFT JOIN Difensori      d ON g.ID = d.ID_giocatore
LEFT JOIN Centrocampisti c ON g.ID = c.ID_giocatore
LEFT JOIN Attaccanti     a ON g.ID = a.ID_giocatore
ORDER BY g.ID ASC;
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <title>Tutti i Giocatori</title>
  <link rel="stylesheet" href="styles/style_visualizzazione_g.css" />
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

<div class="main-container">
  <h1>Tutti i Giocatori</h1>
  <div class="table-wrapper">
  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Nome Cognome</th>
          <th>CF</th>
          <th>Altezza</th>
          <th>Numero Maglia</th>
          <th>Data Nascita</th>
          <th>Nazionalità</th>
          <th>Valore di Mercato</th>
          <th>Presenze</th>
          <th>Codice Contratto</th>
          <th>Data Inizio</th>
          <th>Tipo Contratto</th>
          <th>Scadenza</th>
          <th>Stipendio</th>
          <th>Ruolo</th>
          <th>Statistiche</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['nome'] . ' ' . $row['cognome']) ?></td>
          <td><?= htmlspecialchars($row['cf']) ?></td>
          <td><?= htmlspecialchars($row['altezza']) ?></td>
          <td><?= htmlspecialchars($row['num_maglia']) ?></td>
          <td><?= htmlspecialchars($row['datanascita']) ?></td>
          <td><?= htmlspecialchars($row['nazionalita']) ?></td>
          <td><?= htmlspecialchars(number_format((float)$row['market_value'], 2, ',', '.')) ?></td>
          <td><?= htmlspecialchars($row['presenze']) ?></td>
          <td><?= htmlspecialchars($row['cod_contratto']) ?></td>
          <td><?= htmlspecialchars($row['Data_inizio']) ?></td>
          <td><?= htmlspecialchars($row['Tipo_Contratto']) ?></td>
          <td><?= htmlspecialchars($row['Data_scadenza']) ?></td>
          <td><?= htmlspecialchars(number_format((float)$row['stipendio'], 2, ',', '.')) ?></td>
          <td>
            <?php
              switch ($row['Ruolo']) {
                case 'Portiere':       echo 'Portiere'; break;
                case 'Difensore':      echo htmlspecialchars($row['D_ruolo'] ?? 'Difensore'); break;
                case 'Centrocampista': echo htmlspecialchars($row['C_ruolo'] ?? 'Centrocampista'); break;
                case 'Attaccante':     echo htmlspecialchars($row['A_ruolo'] ?? 'Attaccante'); break;
                default:               echo '-';
              }
            ?>
          </td>
          <td class="role-data">
            <?php
              switch ($row['Ruolo']) {
                case 'Portiere':
                  echo "<strong>Gol subiti:</strong> " . ($row['P_gol_subiti'] ?? '-') . "<br>";
                  echo "<strong>Gol fatti:</strong> "  . ($row['P_gol_fatti']  ?? '-') . "<br>";
                  echo "<strong>Assist:</strong> "     . ($row['P_assist']     ?? '-') . "<br>";
                  echo "<strong>Clean sheet:</strong> ". ($row['P_clean_sheet']?? '-') . "<br>";
                  echo "<strong>Ammonizioni:</strong> ". ($row['P_ammonizioni'] ?? '-') . "<br>";
                  echo "<strong>Espulsioni:</strong> " . ($row['P_espulsioni']  ?? '-') . "<br>";
                  break;

                case 'Difensore':
                  echo "<strong>Gol fatti:</strong> "  . ($row['D_gol_fatti']  ?? '-') . "<br>";
                  echo "<strong>Assist:</strong> "     . ($row['D_assist']     ?? '-') . "<br>";
                  echo "<strong>Ammonizioni:</strong> ". ($row['D_Ammonizioni'] ?? '-') . "<br>";
                  echo "<strong>Espulsioni:</strong> " . ($row['D_Espulsioni']  ?? '-') . "<br>";
                  break;

                case 'Centrocampista':
                  echo "<strong>Gol fatti:</strong> "  . ($row['C_gol_fatti']  ?? '-') . "<br>";
                  echo "<strong>Assist:</strong> "     . ($row['C_assist']     ?? '-') . "<br>";
                  echo "<strong>Ammonizioni:</strong> ". ($row['C_Ammonizioni'] ?? '-') . "<br>";
                  echo "<strong>Espulsioni:</strong> " . ($row['C_Espulsioni']  ?? '-') . "<br>";
                  break;

                case 'Attaccante':
                  echo "<strong>Gol fatti:</strong> "  . ($row['A_gol_fatti']  ?? '-') . "<br>";
                  echo "<strong>Assist:</strong> "     . ($row['A_assist']     ?? '-') . "<br>";
                  echo "<strong>Ammonizioni:</strong> ". ($row['A_Ammonizioni'] ?? '-') . "<br>";
                  echo "<strong>Espulsioni:</strong> " . ($row['A_Espulsioni']  ?? '-') . "<br>";
                  break;

                default:
                  echo "Ruolo non specificato";
              }
            ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center;">Nessun giocatore trovato.</p>
  <?php endif; ?>
  </div>
</div>

<footer>
  <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.querySelector('.table-wrapper');
  if (!wrapper) return;
  wrapper.addEventListener('wheel', function(e){
    if (e.deltaY !== 0) {
      e.preventDefault();
      wrapper.scrollLeft += e.deltaX + e.deltaY;
    }
  }, { passive: false });
});
</script>
</body>
</html>
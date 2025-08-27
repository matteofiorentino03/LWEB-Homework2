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

// Funzione per ottenere la query della classifica
function getMarcatoriQuery() {
    return "
    SELECT 
        g.nome AS Nome,
        g.cognome AS Cognome,
        g.num_maglia AS NumMaglia,
        g.presenze AS Presenze,
        -- Calcolo Gol fatti considerando la tabella corretta
        COALESCE(p.gol_fatti, d.gol_fatti, c.gol_fatti, a.gol_fatti, 0) AS GolFatti,
        CASE 
            WHEN p.ID_giocatore IS NOT NULL THEN 'Portiere'
            ELSE COALESCE(d.ruolo, c.ruolo, a.ruolo, '-')
        END AS Ruolo
    FROM Giocatori g
    LEFT JOIN Portieri p ON g.ID = p.ID_giocatore
    LEFT JOIN Difensori d ON g.ID = d.ID_giocatore
    LEFT JOIN Centrocampisti c ON g.ID = c.ID_giocatore
    LEFT JOIN Attaccanti a ON g.ID = a.ID_giocatore
    ORDER BY GolFatti DESC, g.cognome ASC
    ";
}

// --- EXPORT CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=classifica_marcatori.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nome', 'Cognome', 'Numero Maglia', 'Presenze', 'Ruolo', 'Gol Fatti']);

    $res = $conn->query(getMarcatoriQuery());
    while ($r = $res->fetch_assoc()) {
        fputcsv($output, [
            $r['Nome'],
            $r['Cognome'],
            $r['NumMaglia'],
            $r['Presenze'],
            $r['Ruolo'],
            $r['GolFatti']
        ]);
    }

    fclose($output);
    exit();
}

// --- VISUALIZZAZIONE STANDARD ---
$result = $conn->query(getMarcatoriQuery());
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Classifica Marcatori</title>
    <link rel="stylesheet" href="styles/style_visualizzazione_cm.css" />
</head>
<body>
<header>
    <a href="<?php echo $homepage_link; ?>" class="header-link">
        <div class="logo-container">
            <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo" />
        </div>
    </a>
    <h1><a href="<?php echo $homepage_link; ?>" style="color: inherit; text-decoration: none;">PLAYERBASE</a></h1>
    <div class="utente-container">
        <div class="logout">
            <a href="?logout=true">Logout</a>
        </div>
    </div>
</header>

<div class="main-container">
    <h2>Classifica Marcatori</h2>

    <div class="controls no-print">
        <button onclick="window.print()" class="btn red">Stampa in PDF</button>
        <a href="?export=csv" class="btn orange">Esporta CSV</a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Numero Maglia</th>
                    <th>Presenze</th>
                    <th>Ruolo</th>
                    <th>Gol Fatti</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Nome']) ?></td>
                    <td><?= htmlspecialchars($row['Cognome']) ?></td>
                    <td><?= htmlspecialchars($row['NumMaglia']) ?></td>
                    <td><?= htmlspecialchars($row['Presenze']) ?></td>
                    <td><?= htmlspecialchars($row['Ruolo']) ?></td>
                    <td><?= htmlspecialchars($row['GolFatti']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="no-print">
    <p>&copy; 2025 Playerbase. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
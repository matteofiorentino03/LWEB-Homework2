<?php
session_start();

$conn = new mysqli("localhost", "root", "", "playerbase2");
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$data_attuale = date("Y-m-d"); 


if (!isset($_SESSION['Username'])) {
    header("Location: entering.html");
    exit();
}

$ID_Maglia = $_POST['ID'] ?? null;
$credito = $_POST['credito'] ?? null;
$costo = $_POST['costo'] ?? null;

if ($costo > $credito) {
    $_SESSION['errore_acquisto'] = "Credito insufficiente per completare l'acquisto.";
    $_SESSION['tipo'] = $_POST['tipo'];
    $_SESSION['stagione'] = $_POST['stagione'];
    header("Location: compra_maglia.php");
    exit();
} else {
    $stmt = $conn->prepare("UPDATE utenti SET crediti = ? WHERE Username = ?");
    if($stmt) {
        $credito -= $costo;
        $stmt->bind_param("is", $credito, $_SESSION['Username']);
        $stmt->execute();
        $stmt->close();
    }else{
        echo "<p>Errore: impossibile aggiornare il credito.</p>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO compra (ID_Utente, ID_Maglia, Pagamento_finale, indirizzo_consegna, data_compra) VALUES (?, ?, ?, ?, ?)");
    if($stmt) {
        $stmt->bind_param("iidss", $_SESSION['ID_Utente'], $ID_Maglia, $costo, $Indirizzo_di_consegna, $data_attuale);
        $stmt->execute();
        $stmt->close();
    }else{
        echo "<p>Errore: impossibile registrare l'acquisto.</p>";
        exit();
    }

}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Conferma Acquisto</title>
  <link rel="stylesheet" href="styles/style_conferma_acquisto.css">
</head>
<body>
    <header>
        <a href="homepage_user.php" class="header-link">
            <div class='logo-container'>
                <img src="img/AS_Roma_Logo_2017.svg.png" alt="Logo AS Roma" class="logo">
            </div>
        </a>
        <h1><a href="homepage_user.php">PLAYERBASE</a></h1>
        <div class="utente-container">
            <div class="logout"><a href="?logout=true">Logout</a></div>        
        </div>
    </header>

    <main class="page">
    <h2 class="page-title">Inserisci indirizzo di Consegna</h2>

    <form method="post" class="card">
        <div class="grid">
            <label class="label" for="Indirizzo_di_consegna">Indirizzo di consegna:</label>
            <input class="input" type="text" id="Indirizzo_di_consegna" name="Indirizzo_di_consegna" required>
        </div>
        <div class="compra-btn-group">
            <button type="submit" name="azione" value="conferma_acquisto">Conferma Acquisto</button>
    </form>
    </main>

</body>
</html>

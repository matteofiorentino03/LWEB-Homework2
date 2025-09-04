<?php
/**
 * connect.php
 * Punto unico di accesso al DB.
 * - Se esiste config.php lo carica, altrimenti usa i default (root senza pwd).
 * - Fornisce db(): mysqli con charset utf8mb4.
 */

declare(strict_types=1);

// Default di sviluppo (XAMPP/MAMP ecc.)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'playerbase2';

// Se presente, sovrascrive con la configurazione salvata dall'installazione
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    /** @noinspection PhpIncludeInspection */
    $cfg = require $configPath;
    if (is_array($cfg)) {
        $DB_HOST = $cfg['host'] ?? $DB_HOST;
        $DB_USER = $cfg['user'] ?? $DB_USER;
        $DB_PASS = $cfg['pass'] ?? $DB_PASS;
        $DB_NAME = $cfg['name'] ?? $DB_NAME;
    }
}

// Costanti utili
define('APP_DB_HOST', $DB_HOST);
define('APP_DB_USER', $DB_USER);
define('APP_DB_PASS', $DB_PASS);
define('APP_DB_NAME', $DB_NAME);

// (Opzionale) alias per tabelle: centralizzi i nomi
define('TB_UTENTI',              'Utenti');
define('TB_CREDITI_RICHIESTE',   'Crediti_richieste');
define('TB_GIOCATORI',           'Giocatori');
define('TB_AGISCE',              'Agisce');
define('TB_PORTIERI',            'Portieri');
define('TB_DIFENSORI',           'Difensori');
define('TB_CENTROCAMPISTI',      'Centrocampisti');
define('TB_ATTACCANTI',          'Attaccanti');
define('TB_MAGLIE',              'Maglie');
define('TB_COMPRA',              'Compra');
define('TB_MAGLIE_GIOCATORE',    'Maglie_Giocatore');
define('TB_MAGLIE_PERSONALIZZATE','Maglie_Personalizzate');

/**
 * Restituisce una connessione mysqli aperta al DB applicativo.
 * Lancia eccezione in caso di errore (gestibile dal chiamante).
 */
function db(): mysqli {
    $conn = @new mysqli(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_NAME);
    if ($conn->connect_error) {
        throw new RuntimeException('Connessione fallita: ' . $conn->connect_error);
    }
    // Charset coerente per emoji e qualsiasi carattere
    if (!$conn->set_charset('utf8mb4')) {
        // Non blocchiamo ma segnaliamo in log
        error_log('Impossibile impostare charset utf8mb4: ' . $conn->error);
    }
    return $conn;
}

/**
 * Connessione al SOLO server (senza selezionare il DB).
 * Usa sempre root @ localhost, password vuota (come richiesto).
 */
function server(): mysqli {
    $conn = @new mysqli('localhost', 'root', '', null);
    if ($conn->connect_error) {
        throw new RuntimeException('Connessione server fallita: ' . $conn->connect_error);
    }
    return $conn;
}
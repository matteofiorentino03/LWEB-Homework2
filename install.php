<?php
require_once __DIR__ . '/connect.php';

/* Connessione al SERVER MySQL come root (senza password), senza selezionare DB */
try {
    $connServer = server();   // usa connect.php
    $connMsg    = "Connessione al server MySQL effettuata (root, senza password) tramite connect.php.";
} catch (Throwable $e) {
    die("Connessione al server MySQL fallita: " . $e->getMessage());
}

/* Se è stato premuto il pulsante di installazione, procedo a creare DB, tabelle e dati */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Crea DB e seleziona
    if (!$connServer->query("CREATE DATABASE IF NOT EXISTS playerbase2")) {
        die("Errore creazione database: " . $connServer->error);
    }
    if (!$connServer->select_db("playerbase2")) {
        die("Errore selezione database: " . $connServer->error);
    }

    // Disabilito i FK durante il drop
    $schemaSql = "
    SET FOREIGN_KEY_CHECKS=0;

    DROP TABLE IF EXISTS Compra,
        Maglie_Giocatore,
        Maglie_Personalizzate,
        Attaccanti,
        Centrocampisti,
        Difensori,
        Portieri,
        Agisce,
        Maglie,
        Giocatori,
        Utenti,
        Crediti_richieste;

    SET FOREIGN_KEY_CHECKS=1;

    CREATE TABLE Utenti (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        cf VARCHAR(16) NOT NULL,
        username VARCHAR(30) NOT NULL,
        ruolo SET('admin','utente'),
        status SET('attivo','bannato','disattivato'),
        Password_Utente VARCHAR(35) NOT NULL,
        crediti DECIMAL(6,2)
    );

    CREATE TABLE Crediti_richieste (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    importo DECIMAL(8,2) NOT NULL,
    stato ENUM('In attesa','Approvata','Rifiutata') NOT NULL DEFAULT 'In attesa',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Utenti(ID) ON DELETE CASCADE
    );

    CREATE TABLE Giocatori (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        cf VARCHAR(16) NOT NULL,
        nome VARCHAR(35) NOT NULL,
        cognome VARCHAR(35) NOT NULL,
        nazionalita VARCHAR(150) NOT NULL,
        datanascita DATE NOT NULL,
        num_maglia INT(11) NOT NULL,
        altezza DECIMAL(5,2) NOT NULL,
        market_value DECIMAL(10,2) NOT NULL,
        presenze INT(11) NOT NULL,
        cod_contratto VARCHAR(255) NOT NULL,
        Tipo_Contratto SET('TRASFERIMENTO TEMPORANEO','TRASFERIMENTO DEFINITIVO','PROMOSSO DALLA PRIMAVERA','RINNOVATO') NOT NULL,
        stipendio DECIMAL(10,2) NOT NULL,
        Data_inizio DATE NOT NULL,
        Data_scadenza DATE NOT NULL,
        ID_utenti INT(11) NOT NULL,
        FOREIGN KEY (ID_utenti) REFERENCES Utenti(ID)
    );

    CREATE TABLE Agisce (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ID_utenti INT,
        ID_giocatore INT,
        data_inserimento DATE NOT NULL,
        FOREIGN KEY (ID_utenti) REFERENCES Utenti(ID),
        FOREIGN KEY (ID_giocatore) REFERENCES Giocatori(ID)
    );

    CREATE TABLE Portieri (
        ID_giocatore INT PRIMARY KEY,
        gol_subiti INT NOT NULL,
        gol_fatti INT NOT NULL,
        assist INT NOT NULL,
        clean_sheet INT NOT NULL,
        ammonizioni INT NOT NULL,
        espulsioni INT NOT NULL,
        FOREIGN KEY (ID_giocatore) REFERENCES Giocatori(ID)
    );

    CREATE TABLE Difensori (
        ID_giocatore INT PRIMARY KEY,
        gol_fatti INT NOT NULL,
        assist INT NOT NULL,
        ammonizioni INT NOT NULL,
        espulsioni INT NOT NULL,
        ruolo ENUM('centrale','terzino','braccetto'),
        FOREIGN KEY (ID_giocatore) REFERENCES Giocatori(ID)
    );

    CREATE TABLE Centrocampisti (
        ID_giocatore INT PRIMARY KEY,
        gol_fatti INT NOT NULL,
        assist INT NOT NULL,
        ammonizioni INT NOT NULL,
        espulsioni INT NOT NULL,
        ruolo ENUM('mediano','mezz ala','trequartista','centrale'),
        FOREIGN KEY (ID_giocatore) REFERENCES Giocatori(ID)
    );

    CREATE TABLE Attaccanti (
        ID_giocatore INT PRIMARY KEY,
        gol_fatti INT NOT NULL,
        assist INT NOT NULL,
        ammonizioni INT NOT NULL,
        espulsioni INT NOT NULL,
        ruolo ENUM('punta','seconda punta','ala','falso 9'),
        FOREIGN KEY (ID_giocatore) REFERENCES Giocatori(ID)
    );

    CREATE TABLE Maglie (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('casa','fuori','terza','portiere') NOT NULL,
        taglia ENUM('S','M','L','XL') NOT NULL,
        Sponsor VARCHAR(40),
        stagione VARCHAR(25) NOT NULL,
        descrizione_maglia VARCHAR(1000) NOT NULL,
        costo_fisso INT NOT NULL,
        path_immagine VARCHAR(255)
    );

    CREATE TABLE Compra (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ID_Utente INT,
        ID_Maglia INT,
        pagamento_finale INT,
        indirizzo_consegna VARCHAR(255),
        data_compra DATE NOT NULL,
        FOREIGN KEY (ID_Utente) REFERENCES Utenti(ID),
        FOREIGN KEY (ID_Maglia) REFERENCES Maglie(ID)
    );

    CREATE TABLE Maglie_Giocatore (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        Supplemento INT,
        Logo ENUM('SERIE A','CHAMPIONS LEAGUE','EUROPA LEAGUE','COPPA ITALIA','CONFERENCE LEAGUE'),
        ID_Giocatore INT,
        ID_Maglia INT,
        FOREIGN KEY (ID_Giocatore) REFERENCES Giocatori(ID),
        FOREIGN KEY (ID_Maglia) REFERENCES Maglie(ID)
    );

    CREATE TABLE Maglie_Personalizzate (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ID_Maglia INT,
        Logo ENUM('SERIE A','CHAMPIONS LEAGUE','EUROPA LEAGUE','COPPA ITALIA','CONFERENCE LEAGUE'),
        supplemento INT,
        nome VARCHAR(50),
        num_maglia INT,
        FOREIGN KEY (ID_Maglia) REFERENCES Maglie(ID)
    );
    ";
    
    // 2) connessione al DB creato
    $conn = db(); // usa connect.php (root/localhost/playerbase2)
    
    // 3) BEGIN TRANSACTION
    $conn->begin_transaction();
    try {
        // Crea schema
        if (!$conn->multi_query($schemaSql)) {
            throw new Exception("Errore creazione schema: " . $conn->error);
        }
        // svuota buffer risultati multi_query
        while ($conn->more_results() && $conn->next_result()) {}

        // Utenti default (admin deve avere ID=1)
        $defaultUsers = "
        INSERT INTO Utenti (cf, username, ruolo, status, Password_Utente, crediti) VALUES
        ('ADM1N', 'admin', 'admin', 'attivo', 'cri1234!', NULL),
        ('US3R1', 'user1', 'utente', 'attivo', 'Us3Er1!', 9990.69),
        ('US3R2', 'user2', 'utente', 'attivo', 'Us3Er2!', 3333.69);
        ";
        if (!$conn->query($defaultUsers)) {
            throw new Exception("Errore inserimento utenti default: " . $conn->error);
        }

        // Giocatori (ID 1..25) – come richiesto da te
        $giocatori = "
        INSERT INTO Giocatori
            (ID, cf, nome, cognome, nazionalita, datanascita, num_maglia, altezza, market_value, presenze, cod_contratto, Tipo_Contratto, stipendio, Data_inizio, Data_scadenza, ID_utenti)
        VALUES
        (1, 'M1L3SV1L4R', 'Mile', 'Svilar', 'Serbia - Belgio', '1999-08-27', 99, 1.89, 25000000.00, 0, 'SM0725', 'RINNOVATO', 4360000.00, '2025-07-11', '2030-06-30', 1),
        (2, 'D3V4SQU3Z', 'Devis', 'Vasquez', 'Colombia', '1998-05-12', 32, 1.95, 2000000.00, 0, 'VD0725', 'TRASFERIMENTO DEFINITIVO', 500000.00, '2025-07-29', '2027-06-30', 1),
        (3, 'P13RG0LL0', 'Pierluigi', 'Gollini', 'Italia', '1995-03-18', 95, 1.94, 1000000.00, 0, 'GP0125', 'TRASFERIMENTO DEFINITIVO', 1480000.00, '2025-01-24', '2027-06-30', 1),
        (4, 'R4D0Z3LE3ZNY', 'Radoslaw', 'Zelezny', 'Polonia', '2006-09-06', 91, 1.93, 175000.00, 0, 'ZR0725', 'TRASFERIMENTO DEFINITIVO', 50000.00, '2025-07-23', '2029-06-30', 1),
        (5, 'ND1CK4', 'Evan', 'Ndicka', 'Costa d''Avorio - Francia', '1999-08-20', 5, 1.92, 30000000.00, 0, 'NE0723', 'TRASFERIMENTO DEFINITIVO', 5130000.00, '2023-07-01', '2028-06-30', 1),
        (6, 'M4NC10', 'Gianluca', 'Mancini', 'Italia', '1996-04-17', 23, 1.90, 15000000.00, 0, 'MG0920', 'RINNOVATO', 6480000.00, '2020-09-01', '2027-06-30', 1),
        (7, 'H3RM0S0N', 'Mario', 'Hermoso', 'Spagna', '1995-06-18', 22, 1.84, 7000000.00, 0, 'HM0924', 'TRASFERIMENTO DEFINITIVO', 6480000.00, '2024-09-02', '2027-06-30', 1),
        (8, 'GH1L4RD0N3', 'Daniele', 'Ghilardi', 'Italia', '2003-01-06', 87, 1.89, 5000000.00, 0, 'GD0825', 'TRASFERIMENTO DEFINITIVO', 1000000.00, '2025-08-02', '2026-06-30', 1),
        (9, 'ANG3L1N0', 'José Angel', 'Esmoris (Angelino)', 'Spagna', '1997-01-04', 3, 1.71, 20000000.00, 0, 'EJ0724', 'RINNOVATO', 3700000.00, '2024-07-01', '2028-06-30', 1),
        (10, 'S4L4H3DD1NE', 'Anass', 'Salah-Eddine', 'Olanda - Marocco', '2003-01-18', 34, 1.81, 8000000.00, 0, 'SA0225', 'TRASFERIMENTO DEFINITIVO', 930000.00, '2025-02-03', '2028-06-30', 1),
        (11, 'V1NW3SLEY', 'Wesley Vinicius', 'Franca Lima', 'Brasile', '2003-09-06', 43, 1.78, 20000000.00, 0, 'FW0725', 'TRASFERIMENTO DEFINITIVO', 3700000.00, '2025-07-28', '2030-06-30', 1),
        (12, 'R3N5CH', 'Devyne', 'Rensch', 'Olanda - Suriname', '2003-01-18', 2, 1.81, 10000000.00, 0, 'RD0125', 'TRASFERIMENTO DEFINITIVO', 2220000.00, '2025-01-23', '2029-06-30', 1),
        (13, 'C3L1K', 'Zeki', 'Celik', 'Turchia', '1997-02-17', 19, 1.80, 6000000.00, 0, 'CZ0722', 'TRASFERIMENTO DEFINITIVO', 2560000.00, '2022-07-05', '2026-06-30', 1),
        (14, 'BRY4N', 'Bryan', 'Cristante', 'Italia - Canada', '1995-03-03', 4, 1.86, 7000000.00, 0, 'CB0719', 'RINNOVATO', 5190000.00, '2019-07-01', '2027-06-30', 1),
        (15, 'K0N3', 'Manu', 'Kone', 'Francia - Costa d''Avorio', '2001-05-17', 17, 1.85, 40000000.00, 0, 'KM0824', 'TRASFERIMENTO DEFINITIVO', 5190000.00, '2024-08-30', '2029-06-30', 1),
        (16, 'N3IL0NE', 'Neil', 'El Aynaoui', 'Marocco - Francia', '2001-07-02', 8, 1.85, 15000000.00, 0, 'EN0725', 'TRASFERIMENTO DEFINITIVO', 2780000.00, '2025-07-20', '2030-06-30', 1),
        (17, 'P1S1LL1', 'Niccolò', 'Pisilli', 'Italia', '2004-09-23', 61, 1.80, 13000000.00, 0, 'PN0724', 'PROMOSSO DALLA PRIMAVERA', 3330000.00, '2024-07-01', '2029-06-30', 1),
        (18, 'B4LD4NZ1', 'Tommaso', 'Baldanzi', 'Italia', '2003-03-23', 35, 1.70, 12000000.00, 0, 'BT0224', 'TRASFERIMENTO DEFINITIVO', 2000000.00, '2024-02-01', '2028-06-30', 1),
        (19, 'P3LLEGR1N1', 'Lorenzo', 'Pellegrini', 'Italia', '1996-06-19', 7, 1.86, 9000000.00, 0, 'PL0717', 'RINNOVATO', 6480000.00, '2017-07-01', '2026-06-30', 1),
        (20, 'F4RA0N3', 'Stephan', 'El Shaarawy', 'Italia - Egitto', '1992-10-27', 92, 1.78, 3500000.00, 0, 'ES0121', 'RINNOVATO', 4630000.00, '2021-01-30', '2026-06-30', 1),
        (21, 'CH3RUB1N1', 'Luigi', 'Cherubini', 'Italia', '2004-01-15', 64, 1.75, 2000000.00, 0, 'CL0721', 'PROMOSSO DALLA PRIMAVERA', 280000.00, '2021-07-01', '2027-06-30', 1),
        (22, 'S0UL3', 'Matias', 'Soule', 'Argentina - Italia', '2003-04-15', 18, 1.82, 30000000.00, 0, 'SM0724', 'TRASFERIMENTO DEFINITIVO', 37000000.00, '2024-07-30', '2029-06-30', 1),
        (23, 'P4UL1N0', 'Paulo', 'Dybala', 'Argentina - Italia', '1993-11-15', 21, 1.77, 8000000.00, 0, 'DP0722', 'RINNOVATO', 12960000.00, '2022-07-20', '2026-06-30', 1),
        (24, 'D0VBYK9', 'Artem', 'Dovbyk', 'Ucraina', '1997-06-21', 9, 1.89, 30000000.00, 0, 'DA0824', 'TRASFERIMENTO DEFINITIVO', 5560000.00, '2024-08-02', '2029-06-30', 1),
        (25, 'F3RGUS0NE', 'Evan', 'Ferguson', 'Irlanda - Inghilterra', '2004-10-19', 11, 1.88, 25000000.00, 0, 'FE0725', 'TRASFERIMENTO TEMPORANEO', 3330000.00, '2025-07-23', '2026-06-30', 1);
        ";
        if (!$conn->query($giocatori)) {
            throw new Exception("Errore inserimento Giocatori: " . $conn->error);
        }

        // Agisce (1..25) – admin (ID_utenti=1)
        $agisce = "
        INSERT INTO Agisce (ID, ID_utenti, ID_giocatore, data_inserimento) VALUES
        (1, 1, 1, '2025-08-09'),
        (2, 1, 2, '2025-08-09'),
        (3, 1, 3, '2025-08-09'),
        (4, 1, 4, '2025-08-09'),
        (5, 1, 5, '2025-08-09'),
        (6, 1, 6, '2025-08-09'),
        (7, 1, 7, '2025-08-09'),
        (8, 1, 8, '2025-08-09'),
        (9, 1, 9, '2025-08-09'),
        (10, 1, 10, '2025-08-09'),
        (11, 1, 11, '2025-08-09'),
        (12, 1, 12, '2025-08-09'),
        (13, 1, 13, '2025-08-09'),
        (14, 1, 14, '2025-08-09'),
        (15, 1, 15, '2025-08-09'),
        (16, 1, 16, '2025-08-09'),
        (17, 1, 17, '2025-08-09'),
        (18, 1, 18, '2025-08-09'),
        (19, 1, 19, '2025-08-09'),
        (20, 1, 20, '2025-08-09'),
        (21, 1, 21, '2025-08-09'),
        (22, 1, 22, '2025-08-09'),
        (23, 1, 23, '2025-08-09'),
        (24, 1, 24, '2025-08-09'),
        (25, 1, 25, '2025-08-09');
        ";
        if (!$conn->query($agisce)) {
            throw new Exception("Errore inserimento Agisce: " . $conn->error);
        }

        // Portieri (ID 1..4)
        $portieri = "
        INSERT INTO Portieri (ID_giocatore, gol_subiti, gol_fatti, assist, clean_sheet, ammonizioni, espulsioni) VALUES
        (1, 0, 0, 0, 0, 0, 0),
        (2, 0, 0, 0, 0, 0, 0),
        (3, 0, 0, 0, 0, 0, 0),
        (4, 0, 0, 0, 0, 0, 0);
        ";
        if (!$conn->query($portieri)) {
            throw new Exception("Errore inserimento Portieri: " . $conn->error);
        }

        // Difensori (ID 5..13)
        $difensori = "
        INSERT INTO Difensori (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES
        (5, 0, 0, 0, 0, 'centrale'),
        (6, 0, 0, 0, 0, 'centrale'),
        (7, 0, 0, 0, 0, 'centrale'),
        (8, 0, 0, 0, 0, 'centrale'),
        (9, 0, 0, 0, 0, 'terzino'),
        (10, 0, 0, 0, 0, 'terzino'),
        (11, 0, 0, 0, 0, 'terzino'),
        (12, 0, 0, 0, 0, 'terzino'),
        (13, 0, 0, 0, 0, 'braccetto');
        ";
        if (!$conn->query($difensori)) {
            throw new Exception("Errore inserimento Difensori: " . $conn->error);
        }

        // Centrocampisti (ID 14..19)
        $centro = "
        INSERT INTO Centrocampisti (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES
        (14, 0, 0, 0, 0, 'mediano'),
        (15, 0, 0, 0, 0, 'centrale'),
        (16, 0, 0, 0, 0, 'centrale'),
        (17, 0, 0, 0, 0, 'mezz ala'),
        (18, 0, 0, 0, 0, 'trequartista'),
        (19, 0, 0, 0, 0, 'trequartista');
        ";
        if (!$conn->query($centro)) {
            throw new Exception("Errore inserimento Centrocampisti: " . $conn->error);
        }

        // Attaccanti (ID 20..25)
        $att = "
        INSERT INTO Attaccanti (ID_giocatore, gol_fatti, assist, ammonizioni, espulsioni, ruolo) VALUES
        (20, 0, 0, 0, 0, 'ala'),
        (21, 0, 0, 0, 0, 'ala'),
        (22, 0, 0, 0, 0, 'ala'),
        (23, 0, 0, 0, 0, 'seconda punta'),
        (24, 0, 0, 0, 0, 'punta'),
        (25, 0, 0, 0, 0, 'punta');
        ";
        if (!$conn->query($att)) {
            throw new Exception("Errore inserimento Attaccanti: " . $conn->error);
        }

        $maglie = "
        INSERT INTO `maglie` (`ID`, `tipo`, `taglia`, `Sponsor`, `stagione`, `descrizione_maglia`, `costo_fisso`, `path_immagine`) VALUES
        (1, 'casa', 'S', 'Adidas', '2025/26', 'La nuova maglia da calcio della A.S. Roma per la stagione 2025/26 è rossa scura con i loghi e i bordi laterali arancioni. Questi colori ricordano la maglia del 1993-94, fatta da Adidas. Ufficialmente, i colori sono \"Claret/Arancione\".', 150, 'img/maglie/1754815678_home25-26.jpg'),
        (2, 'casa', 'M', 'Adidas', '2025/26', 'La nuova maglia da calcio della A.S. Roma per la stagione 2025/26 è rossa scura con i loghi e i bordi laterali arancioni. Questi colori ricordano la maglia del 1993-94, fatta da Adidas. Ufficialmente, i colori sono \"Claret/Arancione\".', 150, 'img/maglie/1754816314_home25-26.jpg'),
        (3, 'casa', 'L', 'Adidas', '2025/26', 'La nuova maglia da calcio della A.S. Roma per la stagione 2025/26 è rossa scura con i loghi e i bordi laterali arancioni. Questi colori ricordano la maglia del 1993-94, fatta da Adidas. Ufficialmente, i colori sono \"Claret/Arancione\".', 150, 'img/maglie/1754816366_home25-26.jpg'),
        (4, 'casa', 'XL', 'Adidas', '2025/26', 'La nuova maglia da calcio della A.S. Roma per la stagione 2025/26 è rossa scura con i loghi e i bordi laterali arancioni. Questi colori ricordano la maglia del 1993-94, fatta da Adidas. Ufficialmente, i colori sono \"Claret/Arancione\".', 150, 'img/maglie/1754816382_home25-26.jpg'),
        (5, 'fuori', 'S', 'Adidas', '2025/26', '(Non c\'è il logo di Riyadh Season)La maglia da calcio Adidas A.S. Roma 25-26 Fuori è di colore prevalentemente arancione, combinato con accenti neri e giallo brillante. Il logo Adidas, lo stemma del Lupetto, il colletto e le tre strisce sono tutti neri. La caratteristica più evidente della maglia è il motivo a fulmini che va dalla spalla sinistra alla parte inferiore destra della maglia, creando un look unico. Il motivo è ispirato all\'antico mito di Giove.', 130, 'img/maglie/1754816926_fuori25-26.jpg'),
        (6, 'fuori', 'M', 'Adidas', '2025/26', '(Non c\'è il logo di Riyadh Season)La maglia da calcio Adidas A.S. Roma 25-26 Fuori è di colore prevalentemente arancione, combinato con accenti neri e giallo brillante. Il logo Adidas, lo stemma del Lupetto, il colletto e le tre strisce sono tutti neri. La caratteristica più evidente della maglia è il motivo a fulmini che va dalla spalla sinistra alla parte inferiore destra della maglia, creando un look unico. Il motivo è ispirato all\'antico mito di Giove.', 130, 'img/maglie/1754817782_fuori25-26.jpg'),
        (7, 'fuori', 'L', 'Adidas', '2025/26', '(Non c\'è il logo di Riyadh Season)La maglia da calcio Adidas A.S. Roma 25-26 Fuori è di colore prevalentemente arancione, combinato con accenti neri e giallo brillante. Il logo Adidas, lo stemma del Lupetto, il colletto e le tre strisce sono tutti neri. La caratteristica più evidente della maglia è il motivo a fulmini che va dalla spalla sinistra alla parte inferiore destra della maglia, creando un look unico. Il motivo è ispirato all\'antico mito di Giove.', 130, 'img/maglie/1754817799_fuori25-26.jpg'),
        (8, 'fuori', 'XL', 'Adidas', '2025/26', '(Non c\'è il logo di Riyadh Season)La maglia da calcio Adidas A.S. Roma 25-26 Fuori è di colore prevalentemente arancione, combinato con accenti neri e giallo brillante. Il logo Adidas, lo stemma del Lupetto, il colletto e le tre strisce sono tutti neri. La caratteristica più evidente della maglia è il motivo a fulmini che va dalla spalla sinistra alla parte inferiore destra della maglia, creando un look unico. Il motivo è ispirato all\'antico mito di Giove.', 130, 'img/maglie/1754817821_fuori25-26.jpg'),
        (9, 'terza', 'S', 'Adidas', '2025/26', 'Diventando una squadra d\'elite Adidas a partire dalla stagione 25/26, la terza maglia sarà caratterizzata dal logo Trefoil. Per la prima volta nella storia, la terza maglia è bianca, abbinata ad accenti verdi e gialli e a un colletto a polo per un look classico.', 150, 'img/maglie/1754818309_terza25-26.jpg'),
        (10, 'terza', 'M', 'Adidas', '2025/26', 'Diventando una squadra d\'elite Adidas a partire dalla stagione 25/26, la terza maglia sarà caratterizzata dal logo Trefoil. Per la prima volta nella storia, la terza maglia è bianca, abbinata ad accenti verdi e gialli e a un colletto a polo per un look classico.', 150, 'img/maglie/1754818374_terza25-26.jpg'),
        (11, 'terza', 'L', 'Adidas', '2025/26', 'Diventando una squadra d\'elite Adidas a partire dalla stagione 25/26, la terza maglia sarà caratterizzata dal logo Trefoil. Per la prima volta nella storia, la terza maglia è bianca, abbinata ad accenti verdi e gialli e a un colletto a polo per un look classico.', 150, 'img/maglie/1754818397_terza25-26.jpg'),
        (12, 'terza', 'XL', 'Adidas', '2025/26', 'Diventando una squadra d\'elite Adidas a partire dalla stagione 25/26, la terza maglia sarà caratterizzata dal logo Trefoil. Per la prima volta nella storia, la terza maglia è bianca, abbinata ad accenti verdi e gialli e a un colletto a polo per un look classico.', 150, 'img/maglie/1754818418_terza25-26.jpg'),
        (13, 'portiere', 'S', 'Adidas', '2025/26', 'Prima maglia da Portiere (non c\'è il logo di Riyadh Season).', 120, 'img/maglie/1754818790_por125-26.jpg'),
        (14, 'portiere', 'M', 'Adidas', '2025/26', 'Prima maglia da Portiere (non c\'è il logo di Riyadh Season).', 120, 'img/maglie/1754818810_por125-26.jpg'),
        (15, 'portiere', 'L', 'Adidas', '2025/26', 'Prima maglia da Portiere (non c\'è il logo di Riyadh Season).', 120, 'img/maglie/1754818835_por125-26.jpg'),
        (16, 'portiere', 'XL', 'Adidas', '2025/26', 'Prima maglia da Portiere (non c\'è il logo di Riyadh Season).', 120, 'img/maglie/1754818850_por125-26.jpg'),
        (17, 'casa', 'S', 'Adidas, Riyadh Season', '2024/25', 'La maglia home dell\'Adidas A.S. Roma 2024-25 presenta un\'elegante combinazione di colori, con il colore principale Claret abbinato a loghi e linee verticali dorate', 100, 'img/maglie/1754851303_home24-25.jpeg'),
        (18, 'casa', 'M', 'Adidas, Riyadh Season', '2024/25', 'La maglia home dell\'Adidas A.S. Roma 2024-25 presenta un\'elegante combinazione di colori, con il colore principale Claret abbinato a loghi e linee verticali dorate', 100, 'img/maglie/1754851350_home24-25.jpeg'),
        (19, 'casa', 'L', 'Adidas, Riyadh Season', '2024/25', 'La maglia home dell\'Adidas A.S. Roma 2024-25 presenta un\'elegante combinazione di colori, con il colore principale Claret abbinato a loghi e linee verticali dorate', 100, 'img/maglie/1754851375_home24-25.jpeg'),
        (20, 'casa', 'XL', 'Adidas, Riyadh Season', '2024/25', 'La maglia home dell\'Adidas A.S. Roma 2024-25 presenta un\'elegante combinazione di colori, con il colore principale Claret abbinato a loghi e linee verticali dorate', 100, 'img/maglie/1754851393_home24-25.jpeg'),
        (21, 'fuori', 'S', 'Adidas, Riyadh Season', '2024/25', 'La maglia da calcio Fuori dell\'Adidas A.S. Roma 24-25 è prevalentemente bianca con accenti rossi e arancioni fluorescenti, ispirati alla street art romana della città. I colori ufficiali sono bianco argentato, arancio fluorescente e rosso pompeiano', 110, 'img/maglie/1754851611_fuori24-25.jpeg'),
        (22, 'fuori', 'M', 'Adidas, Riyadh Season', '2024/25', 'La maglia da calcio Fuori dell\'Adidas A.S. Roma 24-25 è prevalentemente bianca con accenti rossi e arancioni fluorescenti, ispirati alla street art romana della città. I colori ufficiali sono bianco argentato, arancio fluorescente e rosso pompeiano', 110, 'img/maglie/1754851649_fuori24-25.jpeg'),
        (23, 'fuori', 'L', 'Adidas, Riyadh Season', '2024/25', 'La maglia da calcio Fuori dell\'Adidas A.S. Roma 24-25 è prevalentemente bianca con accenti rossi e arancioni fluorescenti, ispirati alla street art romana della città. I colori ufficiali sono bianco argentato, arancio fluorescente e rosso pompeiano', 110, 'img/maglie/1754851674_fuori24-25.jpeg'),
        (24, 'fuori', 'XL', 'Adidas, Riyadh Season', '2024/25', 'La maglia da calcio Fuori dell\'Adidas A.S. Roma 24-25 è prevalentemente bianca con accenti rossi e arancioni fluorescenti, ispirati alla street art romana della città. I colori ufficiali sono bianco argentato, arancio fluorescente e rosso pompeiano', 110, 'img/maglie/1754851698_fuori24-25.jpeg'),
        (25, 'terza', 'S', 'Adidas, Riyadh Season', '2024/25', 'La terza maglia Adidas A.S. Roma 2024-25 è la prima maglia navy del club dalla stagione 2020-21. Ufficialmente, il colore principale è il Tech-Indigo.', 100, 'img/maglie/1754851905_terza24-25.jpeg'),
        (26, 'terza', 'M', 'Adidas, Riyadh Season', '2024/25', 'La terza maglia Adidas A.S. Roma 2024-25 è la prima maglia navy del club dalla stagione 2020-21. Ufficialmente, il colore principale è il Tech-Indigo.', 100, 'img/maglie/1754851936_terza24-25.jpeg'),
        (27, 'terza', 'L', 'Adidas, Riyadh Season', '2024/25', 'La terza maglia Adidas A.S. Roma 2024-25 è la prima maglia navy del club dalla stagione 2020-21. Ufficialmente, il colore principale è il Tech-Indigo.', 100, 'img/maglie/1754851964_terza24-25.jpeg'),
        (28, 'terza', 'XL', 'Adidas, Riyadh Season', '2024/25', 'La terza maglia Adidas A.S. Roma 2024-25 è la prima maglia navy del club dalla stagione 2020-21. Ufficialmente, il colore principale è il Tech-Indigo.', 100, 'img/maglie/1754851983_terza24-25.jpeg'),
        (29, 'portiere', 'S', 'Adidas, Riyadh Season', '2024/25', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754852062_por124-25.jpg'),
        (30, 'portiere', 'M', 'Adidas, Riyadh Season', '2024/25', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754852081_por124-25.jpg'),
        (31, 'portiere', 'L', 'Adidas, Riyadh Season', '2024/25', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754852099_por124-25.jpg'),
        (32, 'portiere', 'XL', 'Adidas, Riyadh Season', '2024/25', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754852114_por124-25.jpg'),
        (33, 'casa', 'S', 'New Balance, DigitalBits', '2022/23', 'La maglia home New Balance A.S. Roma 2022-23 presenta un sottile design a metà tra il rosso e il giallo.', 110, 'img/maglie/1754852433_home22-23.jpg'),
        (34, 'casa', 'M', 'New Balance, DigitalBits', '2022/23', 'La maglia home New Balance A.S. Roma 2022-23 presenta un sottile design a metà tra il rosso e il giallo.', 110, 'img/maglie/1754852451_home22-23.jpg'),
        (35, 'casa', 'L', 'New Balance, DigitalBits', '2022/23', 'La maglia home New Balance A.S. Roma 2022-23 presenta un sottile design a metà tra il rosso e il giallo.', 110, 'img/maglie/1754852535_home22-23.jpg'),
        (36, 'casa', 'XL', 'New Balance, DigitalBits', '2022/23', 'La maglia home New Balance A.S. Roma 2022-23 presenta un sottile design a metà tra il rosso e il giallo.', 110, 'img/maglie/1754852558_home22-23.jpg'),
        (37, 'fuori', 'S', 'New Balance, DigitalBits', '2022/23', 'La maglia da trasferta New Balance A.S. Roma 2022-23 è prevalentemente bianca con un leggero motivo sul davanti', 100, 'img/maglie/1754852678_fuori22-23.jpg'),
        (38, 'fuori', 'M', 'New Balance, DigitalBits', '2022/23', 'La maglia da trasferta New Balance A.S. Roma 2022-23 è prevalentemente bianca con un leggero motivo sul davanti', 100, 'img/maglie/1754852720_fuori22-23.jpg'),
        (39, 'fuori', 'L', 'New Balance, DigitalBits', '2022/23', 'La maglia da trasferta New Balance A.S. Roma 2022-23 è prevalentemente bianca con un leggero motivo sul davanti', 100, 'img/maglie/1754852884_fuori22-23.jpg'),
        (40, 'fuori', 'XL', 'New Balance, DigitalBits', '2022/23', 'La maglia da trasferta New Balance A.S. Roma 2022-23 è prevalentemente bianca con un leggero motivo sul davanti', 100, 'img/maglie/1754852899_fuori22-23.jpg'),
        (41, 'terza', 'S', 'New Balance, DigitalBits', '2022/23', 'Con un look unico, la terza maglia New Balance A.S. Roma 2022-23 è principalmente nera con un motivo grafico rosa e antracite sul davanti e sulle maniche. Questo motivo raffigura una pletora di linee diagonali e verticali.', 105, 'img/maglie/1754853179_terza22-23.jpg'),
        (42, 'terza', 'M', 'New Balance, DigitalBits', '2022/23', 'Con un look unico, la terza maglia New Balance A.S. Roma 2022-23 è principalmente nera con un motivo grafico rosa e antracite sul davanti e sulle maniche. Questo motivo raffigura una pletora di linee diagonali e verticali.', 105, 'img/maglie/1754853206_terza22-23.jpg'),
        (43, 'terza', 'L', 'New Balance, DigitalBits', '2022/23', 'Con un look unico, la terza maglia New Balance A.S. Roma 2022-23 è principalmente nera con un motivo grafico rosa e antracite sul davanti e sulle maniche. Questo motivo raffigura una pletora di linee diagonali e verticali.', 105, 'img/maglie/1754853229_terza22-23.jpg'),
        (44, 'terza', 'XL', 'New Balance, DigitalBits', '2022/23', 'Con un look unico, la terza maglia New Balance A.S. Roma 2022-23 è principalmente nera con un motivo grafico rosa e antracite sul davanti e sulle maniche. Questo motivo raffigura una pletora di linee diagonali e verticali.', 105, 'img/maglie/1754853246_terza22-23.jpg'),
        (45, 'portiere', 'S', 'New Balance, DigitalBits', '2022/23', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754853348_por222-23.jpg'),
        (46, 'portiere', 'M', 'New Balance, DigitalBits', '2022/23', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754853373_por222-23.jpg'),
        (47, 'portiere', 'L', 'New Balance, DigitalBits', '2022/23', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754853396_por222-23.jpg'),
        (48, 'portiere', 'XL', 'New Balance, DigitalBits', '2022/23', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754853424_por222-23.jpg'),
        (49, 'casa', 'S', 'Nike, Qatar Airways', '2020/21', 'Proprio come la maglia della A.S. Roma 1979-80, che combinava una base rossa brillante con sfumature di arancione e giallo con blocchi intorno al colletto e sui polsini delle maniche, anche il kit home per la stagione 2020-21 ha un incredibile look multicolore. E\' uno dei look più riconoscibili della Roma.', 100, 'img/maglie/1754853624_home20-21.jpg'),
        (50, 'casa', 'M', 'Nike, Qatar Airways', '2020/21', 'Proprio come la maglia della A.S. Roma 1979-80, che combinava una base rossa brillante con sfumature di arancione e giallo con blocchi intorno al colletto e sui polsini delle maniche, anche il kit home per la stagione 2020-21 ha un incredibile look multicolore. E\' uno dei look più riconoscibili della Roma.', 100, 'img/maglie/1754853652_home20-21.jpg'),
        (51, 'casa', 'L', 'Nike, Qatar Airways', '2020/21', 'Proprio come la maglia della A.S. Roma 1979-80, che combinava una base rossa brillante con sfumature di arancione e giallo con blocchi intorno al colletto e sui polsini delle maniche, anche il kit home per la stagione 2020-21 ha un incredibile look multicolore. E\' uno dei look più riconoscibili della Roma.', 100, 'img/maglie/1754853667_home20-21.jpg'),
        (52, 'casa', 'XL', 'Nike, Qatar Airways', '2020/21', 'Proprio come la maglia della A.S. Roma 1979-80, che combinava una base rossa brillante con sfumature di arancione e giallo con blocchi intorno al colletto e sui polsini delle maniche, anche il kit home per la stagione 2020-21 ha un incredibile look multicolore. E\' uno dei look più riconoscibili della Roma.', 100, 'img/maglie/1754853681_home22-23.jpg'),
        (53, 'fuori', 'S', 'Nike, Qatar Airways', '2020/21', 'La maglia da trasferta Nike A.S. Roma 2020-21 è di colore prevalentemente \"avorio pallido\" e presenta uno scudetto Lupetto rosso e nero sul petto a sinistra.', 100, 'img/maglie/1754853860_fuori20-21.jpg'),
        (54, 'fuori', 'M', 'Nike, Qatar Airways', '2020/21', 'La maglia da trasferta Nike A.S. Roma 2020-21 è di colore prevalentemente \"avorio pallido\" e presenta uno scudetto Lupetto rosso e nero sul petto a sinistra.', 100, 'img/maglie/1754853884_fuori20-21.jpg'),
        (55, 'fuori', 'L', 'Nike, Qatar Airways', '2020/21', 'La maglia da trasferta Nike A.S. Roma 2020-21 è di colore prevalentemente \"avorio pallido\" e presenta uno scudetto Lupetto rosso e nero sul petto a sinistra.', 100, 'img/maglie/1754853905_fuori20-21.jpg'),
        (56, 'fuori', 'XL', 'Nike, Qatar Airways', '2020/21', 'La maglia da trasferta Nike A.S. Roma 2020-21 è di colore prevalentemente \"avorio pallido\" e presenta uno scudetto Lupetto rosso e nero sul petto a sinistra.', 100, 'img/maglie/1754853919_fuori20-21.jpg'),
        (57, 'terza', 'S', 'Nike, Qatar Airways', '2020/21', 'Terza Maglia Stagione 2020/21.', 100, 'img/maglie/1754853998_terza20-21.jpg'),
        (58, 'terza', 'M', 'Nike, Qatar Airways', '2020/21', 'Terza Maglia Stagione 2020/21.', 100, 'img/maglie/1754854016_terza20-21.jpg'),
        (59, 'terza', 'L', 'Nike, Qatar Airways', '2020/21', 'Terza Maglia Stagione 2020/21.', 100, 'img/maglie/1754854039_terza20-21.jpg'),
        (60, 'terza', 'XL', 'Nike, Qatar Airways', '2020/21', 'Terza Maglia Stagione 2020/21.', 100, 'img/maglie/1754854071_terza20-21.jpg'),
        (61, 'portiere', 'S', 'Nike, Qatar Airways', '2020/21', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754854157_por120-21.jpg'),
        (62, 'portiere', 'M', 'Nike, Qatar Airways', '2020/21', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754854182_por120-21.jpg'),
        (63, 'portiere', 'L', 'Nike, Qatar Airways', '2020/21', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754854196_por120-21.jpg'),
        (64, 'portiere', 'XL', 'Nike, Qatar Airways', '2020/21', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754854213_por120-21.jpg'),
        (65, 'casa', 'S', 'Nike', '2016/17', 'Come la stagione 2015/16, il kit home della A.S. Roma combina una base rossa con accenti in una tonalità più scura del colore. Questa maglia è caratterizzata da un motivo a strisce in rosso scuro sul davanti, mentre il colore è utilizzato anche per il rivestimento del colletto.', 100, 'img/maglie/1754854573_home16-17.jpg'),
        (66, 'casa', 'M', 'Nike', '2016/17', 'Come la stagione 2015/16, il kit home della A.S. Roma combina una base rossa con accenti in una tonalità più scura del colore. Questa maglia è caratterizzata da un motivo a strisce in rosso scuro sul davanti, mentre il colore è utilizzato anche per il rivestimento del colletto.', 100, 'img/maglie/1754854589_home16-17.jpg'),
        (67, 'casa', 'L', 'Nike', '2016/17', 'Come la stagione 2015/16, il kit home della A.S. Roma combina una base rossa con accenti in una tonalità più scura del colore. Questa maglia è caratterizzata da un motivo a strisce in rosso scuro sul davanti, mentre il colore è utilizzato anche per il rivestimento del colletto.', 100, 'img/maglie/1754854602_home16-17.jpg'),
        (68, 'casa', 'XL', 'Nike', '2016/17', 'Come la stagione 2015/16, il kit home della A.S. Roma combina una base rossa con accenti in una tonalità più scura del colore. Questa maglia è caratterizzata da un motivo a strisce in rosso scuro sul davanti, mentre il colore è utilizzato anche per il rivestimento del colletto.', 100, 'img/maglie/1754854615_home16-17.jpg'),
        (69, 'fuori', 'S', 'Nike', '2016/17', 'Ispirandosi al vecchio logo e in onore del 90° anniversario che ricorre nel 2017, l\'A.S. Roma sfoggia il tradizionale stemma \"Lupetto\" sul kit da trasferta 2016-17, per la prima volta dalla stagione 2012-13. Utilizzato tra il 1979 e il 1997, il classico stemma dell\'A.S. Roma \"Lupetto\" mostra una testa di lupo stilizzata, circondata da un cerchio bianco con tratti rossi e arancioni.', 100, 'img/maglie/1754855025_fuori16-17.jpg'),
        (70, 'fuori', 'M', 'Nike', '2016/17', 'Ispirandosi al vecchio logo e in onore del 90° anniversario che ricorre nel 2017, l\'A.S. Roma sfoggia il tradizionale stemma \"Lupetto\" sul kit da trasferta 2016-17, per la prima volta dalla stagione 2012-13. Utilizzato tra il 1979 e il 1997, il classico stemma dell\'A.S. Roma \"Lupetto\" mostra una testa di lupo stilizzata, circondata da un cerchio bianco con tratti rossi e arancioni.', 100, 'img/maglie/1754855039_fuori16-17.jpg'),
        (71, 'fuori', 'L', 'Nike', '2016/17', 'Ispirandosi al vecchio logo e in onore del 90° anniversario che ricorre nel 2017, l\'A.S. Roma sfoggia il tradizionale stemma \"Lupetto\" sul kit da trasferta 2016-17, per la prima volta dalla stagione 2012-13. Utilizzato tra il 1979 e il 1997, il classico stemma dell\'A.S. Roma \"Lupetto\" mostra una testa di lupo stilizzata, circondata da un cerchio bianco con tratti rossi e arancioni.', 100, 'img/maglie/1754855073_fuori16-17.jpg'),
        (72, 'fuori', 'XL', 'Nike', '2016/17', 'Ispirandosi al vecchio logo e in onore del 90° anniversario che ricorre nel 2017, l\'A.S. Roma sfoggia il tradizionale stemma \"Lupetto\" sul kit da trasferta 2016-17, per la prima volta dalla stagione 2012-13. Utilizzato tra il 1979 e il 1997, il classico stemma dell\'A.S. Roma \"Lupetto\" mostra una testa di lupo stilizzata, circondata da un cerchio bianco con tratti rossi e arancioni.', 100, 'img/maglie/1754855086_fuori16-17.jpg'),
        (73, 'terza', 'S', 'Nike', '2016/17', 'Sempre basata su un modello globale, utilizzato anche per squadre del calibro di Barcellona e Manchester City, la terza maglia Nike A.S. Roma è di colore rosso vivo nella parte superiore e nelle maniche, che sfuma in un arancione acceso nella parte inferiore.', 100, 'img/maglie/1754855261_terza16-17.jpg'),
        (74, 'terza', 'M', 'Nike', '2016/17', 'Sempre basata su un modello globale, utilizzato anche per squadre del calibro di Barcellona e Manchester City, la terza maglia Nike A.S. Roma è di colore rosso vivo nella parte superiore e nelle maniche, che sfuma in un arancione acceso nella parte inferiore.', 100, 'img/maglie/1754855278_terza16-17.jpg'),
        (75, 'terza', 'L', 'Nike', '2016/17', 'Sempre basata su un modello globale, utilizzato anche per squadre del calibro di Barcellona e Manchester City, la terza maglia Nike A.S. Roma è di colore rosso vivo nella parte superiore e nelle maniche, che sfuma in un arancione acceso nella parte inferiore.', 100, 'img/maglie/1754855315_terza16-17.jpg'),
        (76, 'terza', 'XL', 'Nike', '2016/17', 'Sempre basata su un modello globale, utilizzato anche per squadre del calibro di Barcellona e Manchester City, la terza maglia Nike A.S. Roma è di colore rosso vivo nella parte superiore e nelle maniche, che sfuma in un arancione acceso nella parte inferiore.', 100, 'img/maglie/1754855332_terza16-17.jpg'),
        (77, 'portiere', 'S', 'Nike', '2016/17', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754855394_por216-17.jpg'),
        (78, 'portiere', 'M', 'Nike', '2016/17', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754855407_por216-17.jpg'),
        (79, 'portiere', 'L', 'Nike', '2016/17', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754855420_por216-17.jpg'),
        (80, 'portiere', 'XL', 'Nike', '2016/17', 'Seconda Maglia da Portiere.', 100, 'img/maglie/1754855446_por216-17.jpg'),
        (81, 'casa', 'S', 'Kappa, INA Assitalia', '2001/02', 'Maglia Home A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754855611_home01-02.jpg'),
        (82, 'casa', 'M', 'Kappa, INA Assitalia', '2001/02', 'Maglia Home A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754855640_home01-02.jpg'),
        (83, 'casa', 'L', 'Kappa, INA Assitalia', '2001/02', 'Maglia Home A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754855694_home01-02.jpg'),
        (84, 'casa', 'XL', 'Kappa, INA Assitalia', '2001/02', 'Maglia Home A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754855718_home01-02.jpg'),
        (85, 'fuori', 'S', 'Kappa, INA Assitalia', '2001/02', 'Maglia Trasferta A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856293_fuori01-02.jpg'),
        (86, 'fuori', 'M', 'Kappa, INA Assitalia', '2001/02', 'Maglia Trasferta A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856313_fuori01-02.jpg'),
        (87, 'fuori', 'L', 'Kappa, INA Assitalia', '2001/02', 'Maglia Trasferta A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856343_fuori01-02.jpg'),
        (88, 'fuori', 'XL', 'Kappa, INA Assitalia', '2001/02', 'Maglia Trasferta A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856357_fuori01-02.jpg'),
        (89, 'terza', 'S', 'Kappa, INA Assitalia', '2001/02', 'Terza Maglia A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856485_terza01-02.jpg'),
        (90, 'terza', 'M', 'Kappa, INA Assitalia', '2001/02', 'Terza Maglia A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856501_terza01-02.jpg'),
        (91, 'terza', 'L', 'Kappa, INA Assitalia', '2001/02', 'Terza Maglia A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856520_terza01-02.jpg'),
        (92, 'terza', 'XL', 'Kappa, INA Assitalia', '2001/02', 'Terza Maglia A.S. Roma stagione 2001-02, con lo scudetto al petto.', 100, 'img/maglie/1754856536_terza01-02.jpg'),
        (93, 'casa', 'S', 'Kappa, INA Assitalia', '2000/01', 'Maglia Home A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto.', 100, 'img/maglie/1754856630_home00-01.jpg'),
        (94, 'casa', 'M', 'Kappa, INA Assitalia', '2000/01', 'Maglia Home A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto.', 100, 'img/maglie/1754856656_home00-01.jpg'),
        (95, 'casa', 'L', 'Kappa, INA Assitalia', '2000/01', 'Maglia Home A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto.', 100, 'img/maglie/1754856671_home00-01.jpg'),
        (96, 'casa', 'XL', 'Kappa, INA Assitalia', '2000/01', 'Maglia Home A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto.', 100, 'img/maglie/1754856686_home00-01.jpg'),
        (97, 'fuori', 'S', 'Kappa, INA Assitalia', '2000/01', 'Maglia Trasferta A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto.', 100, 'img/maglie/1754856742_fuori00-01.jpg'),
        (98, 'fuori', 'M', 'Kappa, INA Assitalia', '2000/01', 'Maglia Trasferta A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856757_fuori00-01.jpg'),
        (99, 'fuori', 'L', 'Kappa, INA Assitalia', '2000/01', 'Maglia Trasferta A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856772_fuori00-01.jpg'),
        (100, 'fuori', 'XL', 'Kappa, INA Assitalia', '2000/01', 'Maglia Trasferta A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856811_fuori00-01.jpg'),
        (101, 'terza', 'S', 'Kappa, INA Assitalia', '2000/01', 'Terza Maglia  A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856950_terza00-01.jpg'),
        (102, 'terza', 'M', 'Kappa, INA Assitalia', '2000/01', 'Terza Maglia  A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856967_terza00-01.jpg'),
        (103, 'terza', 'L', 'Kappa, INA Assitalia', '2000/01', 'Terza Maglia  A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856983_terza00-01.jpg'),
        (104, 'terza', 'XL', 'Kappa, INA Assitalia', '2000/01', 'Terza Maglia  A.S. Roma stagione 2000-01, stagione dell\'ultimo scudetto. ', 100, 'img/maglie/1754856998_terza00-01.jpg'),
        (105, 'portiere', 'S', 'Kappa, INA Assitalia', '2000/01', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754857056_por100-01.jpg'),
        (106, 'portiere', 'M', 'Kappa, INA Assitalia', '2000/01', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754857072_por100-01.jpg'),
        (107, 'portiere', 'L', 'Kappa, INA Assitalia', '2000/01', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754857088_por100-01.jpg'),
        (108, 'portiere', 'XL', 'Kappa, INA Assitalia', '2000/01', 'Prima Maglia da Portiere.', 100, 'img/maglie/1754857106_por100-01.jpg'),
        (109, 'casa', 'S', 'Adidas, Barilla', '1993/94', 'Maglia Home A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857205_home93-94.jpg'),
        (110, 'casa', 'M', 'Adidas, Barilla', '1993/94', 'Maglia Home A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857237_home93-94.jpg'),
        (111, 'casa', 'L', 'Adidas, Barilla', '1993/94', 'Maglia Home A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857252_home93-94.jpg'),
        (112, 'casa', 'XL', 'Adidas, Barilla', '1993/94', 'Maglia Home A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857267_home93-94.jpg'),
        (113, 'fuori', 'S', 'Adidas, Barilla', '1993/94', 'Maglia Trasferta A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857420_fuori93-94.jpg'),
        (114, 'fuori', 'M', 'Adidas, Barilla', '1993/94', 'Maglia Trasferta A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857438_fuori93-94.jpg'),
        (115, 'fuori', 'L', 'Adidas, Barilla', '1993/94', 'Maglia Trasferta A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857455_fuori93-94.jpg'),
        (116, 'fuori', 'XL', 'Adidas, Barilla', '1993/94', 'Maglia Trasferta A.S. Roma stagione 1993-94.', 100, 'img/maglie/1754857476_fuori93-94.jpg'),
        (117, 'casa', 'S', 'Adidas, Barilla', '1991/92', 'Maglia Home A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857753_home91-92.jpg'),
        (118, 'casa', 'M', 'Adidas, Barilla', '1991/92', 'Maglia Home A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857784_home91-92.jpg'),
        (119, 'casa', 'L', 'Adidas, Barilla', '1991/92', 'Maglia Home A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857802_home91-92.jpg'),
        (120, 'casa', 'XL', 'Adidas, Barilla', '1991/92', 'Maglia Home A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857817_home91-92.jpg'),
        (121, 'fuori', 'S', 'Adidas, Barilla', '1991/92', 'Maglia Trasferta A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857887_fuori91-92.jpg'),
        (122, 'fuori', 'M', 'Adidas, Barilla', '1991/92', 'Maglia Trasferta A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857912_fuori91-92.jpg'),
        (123, 'fuori', 'L', 'Adidas, Barilla', '1991/92', 'Maglia Trasferta A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857928_fuori91-92.jpg'),
        (124, 'fuori', 'XL', 'Adidas, Barilla', '1991/92', 'Maglia Trasferta A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857944_fuori91-92.jpg'),
        (125, 'terza', 'S', 'Adidas, Barilla', '1991/92', 'Terza Maglia A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754857988_terza91-92.jpg'),
        (126, 'terza', 'M', 'Adidas, Barilla', '1991/92', 'Terza Maglia A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754858010_terza91-92.jpg'),
        (127, 'terza', 'L', 'Adidas, Barilla', '1991/92', 'Terza Maglia A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754858026_terza91-92.jpg'),
        (128, 'terza', 'XL', 'Adidas, Barilla', '1991/92', 'Terza Maglia A.S. Roma stagione 1991-92.', 100, 'img/maglie/1754858042_terza91-92.jpg'),
        (129, 'casa', 'S', 'Patrick, Barilla', '1983/84', 'Maglia Home A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858160_home83-84.jpg'),
        (130, 'casa', 'M', 'Patrick, Barilla', '1983/84', 'Maglia Home A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858180_home83-84.jpg'),
        (131, 'casa', 'L', 'Patrick, Barilla', '1983/84', 'Maglia Home A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858197_home83-84.jpg'),
        (132, 'casa', 'XL', 'Patrick, Barilla', '1983/84', 'Maglia Home A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858214_home83-84.jpg'),
        (133, 'fuori', 'S', 'Patrick, Barilla', '1983/84', 'Maglia Trasferta A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858263_fuori83-84.jpg'),
        (134, 'fuori', 'M', 'Patrick, Barilla', '1983/84', 'Maglia Trasferta A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858282_fuori83-84.jpg'),
        (135, 'fuori', 'L', 'Patrick, Barilla', '1983/84', 'Maglia Trasferta A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858299_fuori83-84.jpg'),
        (136, 'fuori', 'XL', 'Patrick, Barilla', '1983/84', 'Maglia Trasferta A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858316_fuori83-84.jpg'),
        (137, 'terza', 'S', 'Kappa, Barilla', '1983/84', 'Terza Maglia A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858388_terza83-84.jpg'),
        (138, 'terza', 'M', 'Kappa, Barilla', '1983/84', 'Terza Maglia A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858413_terza83-84.jpg'),
        (139, 'terza', 'L', 'Kappa, Barilla', '1983/84', 'Terza Maglia A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858432_terza83-84.jpg'),
        (140, 'terza', 'XL', 'Kappa, Barilla', '1983/84', 'Terza Maglia A.S. Roma stagione 1983-84.', 100, 'img/maglie/1754858446_terza83-84.jpg');
        ";
        if (!$conn->query($maglie)) {
            throw new Exception("Errore inserimento Maglie: " . $conn->error);
        }

        // Tutto ok
        $conn->commit();

        $configContent = "<?php\nreturn [\n" .
        "    'host' => 'localhost',\n" .
        "    'user' => 'root',\n" .
        "    'pass' => '',\n" .
        "    'name' => 'playerbase2',\n" .
        "];\n";

        if (file_put_contents(__DIR__ . '/config.php', $configContent) === false) {
        throw new Exception("Impossibile scrivere config.php");
        }

        // Messaggio + redirect
        echo "<script>
            alert('Installazione completata con successo!');
            window.location.href = 'entering.html';
        </script>";
        exit;

    }catch (Exception $e) {
        $conn->rollback();
        die('Installazione annullata: ' . $e->getMessage());
    } finally {
        $conn->close();
    }
    
}
?>
<p style="padding:10px;background:#e8ffe8;border:1px solid #9fd09f;">
  <?php echo htmlspecialchars($connMsg); ?>
</p>

<form method="post">
  <input type="hidden" name="host" value="localhost" />
  <input type="hidden" name="username" value="root" />
  <input type="hidden" name="password" value="" />
  <button type="submit">Avvia installazione del database</button>
</form>
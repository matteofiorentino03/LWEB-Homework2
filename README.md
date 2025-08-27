# LWEB-Homework2

## Autori
- Cristian Buttaro ([@cristian03git](https://github.com/cristian03git))
- Matteo Fiorentino ([@matteofiorentino03](https://github.com/matteofiorentino03))

## Descrizione
**LWEB-Homework2** è un piccolo **gestionale per una società di calcio** sviluppato in **PHP + MySQL** (con HTML/CSS). L’applicazione introduce un **backend** con **autenticazione**, **ruoli** (utente/admin), e **CRUD** su giocatori e maglie, oltre a un **flusso d’acquisto** con storico ordini.

Il flusso tipico per l’**utente** parte da `entering.html` → **login/registrazione** → `homepage_user.php` → **catalogo maglie** (`catalogo_maglie.php`) → **acquisto** (`compra_maglia.php` → `conferma_acquisto.php`) → (opz.) **stampa ordine** (`stampa_ordine.php`) → **storico** (`storico_acquisti_utente.php`).  
Per l’**admin** sono previste pagine di **inserimento/modifica/cancellazione** dei **giocatori** e gestione delle **maglie**, oltre a **tabelle riepilogative**, **storici** e **classifica marcatori**. È presente `install.php` per la **creazione dello schema** del database.

---

## Funzionalità

### Per gli utenti
- Registrazione e login.
- Home utente con scorciatoie principali (`homepage_user.php`).
- Consultazione **catalogo maglie** (`catalogo_maglie.php`).
- **Acquisto guidato** (`compra_maglia.php` → `conferma_acquisto.php`) e **stampa ordine** (`stampa_ordine.php`).
- **Storico acquisti personale** (`storico_acquisti_utente.php`).
- **Modifica dati profilo** (`modifica_info_utente.php`).

### Per gli amministratori
- Home admin (`homepage_admin.php`).
- **Inserimento e gestione giocatori** (pagine dedicate per ruolo: `inserisci_portiere.php`, `inserisci_difensore.php`, `inserisci_centrocampista.php`, `inserisci_attaccante.php`; più `inserimento_giocatore.php`, `modifica_giocatore.php`, `cancella_giocatore.php`).
- **Gestione maglie** (`inserimento_maglia.php`, `modifica_maglia.php`).
- **Visualizzazione tabelle** e **classifica marcatori** (`visualizzazione_tabelle.php`, `tabella_giocatore.php`, `tabella_maglia.php`, `visualizzazione_classifica_marcatori.php`).
- **Storici e audit** (`storico_inserimenti.php`, `storico_acquisti.php`).
- **Accettazione crediti** (`accettazione_crediti.php`).

### Setup DB
- Script di **installazione database**: `install.php` (creazione schema e tabelle, eventuali dati di esempio).

---

## Struttura della cartella `LWEB-Homework2`
- `img/` – asset grafici (loghi, immagini prodotto, icone).
- `styles/` – fogli di stile CSS.

**Pagine di ingresso e navigazione**
- `entering.html`: pagina iniziale (accesso / registrazione).
- `login.php`, `registrazione.php`: form di autenticazione.
- `homepage_user.php`: home per l’utente.
- `homepage_admin.php`: home per l’amministratore.
- `dashboard.php`: eventuale riepilogo/shortcut funzionali.

**Catalogo & Ordini (utente)**
- `catalogo_maglie.php`: visualizzazione catalogo.
- `compra_maglia.php`: selezione e avvio ordine.
- `conferma_acquisto.php`: conferma operazione d’acquisto.
- `stampa_ordine.php`: stampa/riepilogo dell’ordine.
- `storico_acquisti_utente.php`: storico personale.
- `storico_acquisti.php`: storico globale (vista admin).

**Gestione dati (admin)**
- `inserimenti.php`: hub per le operazioni di inserimento.
- `inserimento_giocatore.php`, `modifica_giocatore.php`, `cancella_giocatore.php`: CRUD sui giocatori.
- `inserisci_portiere.php`, `inserisci_difensore.php`, `inserisci_centrocampista.php`, `inserisci_attaccante.php`: inserimenti per ruolo.
- `inserimento_maglia.php`, `modifica_maglia.php`: gestione maglie.
- `visualizzazione_tabelle.php`: accesso rapido alle tabelle.
- `tabella_giocatore.php`, `tabella_maglia.php`: viste tabellari dedicate.
- `visualizzazione_classifica_marcatori.php`: classifica marcatori.
- `storico_inserimenti.php`: audit inserimenti.
- `modifica_info_utente.php`, `modifica_utente.php`, `modifiche.php`: gestione profili/variazioni.

**Setup database**
- `install.php`: creazione schema e tabelle del DB.

---

## Prerequisiti
- **PHP** 8.x (estensione `mysqli` attiva).
- **MySQL/MariaDB** 10.x+.
- **Server web** (Apache consigliato; va bene XAMPP/MAMP/WAMP o Docker).
- **Browser moderno**.

---

## Set-up rapido (locale)
1. **Clona la repo** nella cartella servita da Apache (es. `htdocs`/`www`):
   ```bash
   git clone https://github.com/cristian03git/LWEB-Homework2.git
   ```
2. **Configura il database**:
   - Crea un DB (es. `playerbase2`) e credenziali con permessi `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER`.
   - Avvia `install.php` dal browser: `http://localhost/LWEB-Homework2/install.php` per creare **schema** e **tabelle**.
3. **Verifica le credenziali DB nei file PHP**:
   - Aggiorna host, utente, password e nome DB in tutti i file che istanziano `new mysqli(...)`. (Non c’è un file di config centralizzato, quindi la connessione può essere ripetuta in più script.)
4. **Avvio**:
   - Entra da `entering.html` per **login/registrazione**.
   - Dopo l’accesso verrai reindirizzato alla **home utente** o **home admin** in base al ruolo.

> **Suggerimento:** se usi XAMPP, accertati che Apache e MySQL siano attivi e che `extension=mysqli` sia abilitata nel `php.ini`.

---

## Come si usa

### Utente
1. Registrati da `registrazione.php` e poi effettua il login da `login.php`.
2. Dalla `homepage_user.php`: apri **Catalogo** → scegli una maglia → **Compra** (`compra_maglia.php`) → **Conferma** (`conferma_acquisto.php`) → (opz.) **Stampa ordine** (`stampa_ordine.php`).
3. Consulta **Storico acquisti** in `storico_acquisti_utente.php`.

### Admin
1. Accedi a `homepage_admin.php`.
2. **Gestione giocatori**: inserisci per ruolo (`inserisci_*`), modifica o cancella (`modifica_giocatore.php`, `cancella_giocatore.php`).
3. **Gestione maglie**: `inserimento_maglia.php`, `modifica_maglia.php`.
4. **Tabelle e report**: `visualizzazione_tabelle.php`, `tabella_giocatore.php`, `tabella_maglia.php`, `visualizzazione_classifica_marcatori.php`, `storico_inserimenti.php`, `storico_acquisti.php`.
5. **Accettazione crediti**: `accettazione_crediti.php`.

---

## Flussi principali

- **Onboarding** → `registrazione.php` → `login.php` → `homepage_user.php`/`homepage_admin.php`  
- **Acquisto maglia (utente)** → `catalogo_maglie.php` → `compra_maglia.php` → `conferma_acquisto.php` → `stampa_ordine.php` → registro visibile in `storico_acquisti_utente.php`  
- **Gestione anagrafiche (admin)** → `inserimenti.php` + pagine `inserisci_*`/`modifica_*`/`cancella_giocatore.php` → verifica su `visualizzazione_tabelle.php`



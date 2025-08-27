# LWEB-Homework2

## Autori
- Cristian Buttaro ([@cristian03git](https://github.com/cristian03git))
- Matteo Fiorentino ([@matteofiorentino03](https://github.com/matteofiorentino03))

## Descrizione
**PLAYERBASE** è un piccolo **gestionale per una società di calcio**(in questo caso l'A.S. Roma) sviluppato in **PHP + MySQL** (con HTML/CSS e JavaScript). 
L’applicazione introduce un **backend** con **autenticazione**, **ruoli** (utente/admin), e **CRUD** *(Create-Read-Update-Delete* su giocatori e maglie, oltre a un **flusso d’acquisto** con storico ordini.

---

## Funzionalità

### Per gli utenti:
- Senza fare il login si possono fare le seguenti azioni, entrando da `homepage_user.php`:
  - Consultare il **catalogo maglie** (`catalogo_maglie.php`);
  - Visualizzare la **Tabella di tutti i giocatori dell'attuale stagione** (`tabella_giocatore.php`);
  - Visualizzare la **Classifica dei marcatori dell'attuale stagione** (`visualizzazione_classifica_marcatori.php`).
- Facendo il login (anche a seguito di una registrazione dell'account), oltre a vedere le pagine sopra citate, si possono fare le seguenti azioni:
  - Visualizzare lo **Storico degli acquisti effettuati** da quell'utente (`storico_acquisti_utente.php`) con la possibilità della **stampa di un singolo ordine** (`stampa_ordine.php`);
  - Effettuare le **Modifiche delle informazioni personali** dell'account(`modifica_info_utente.php`), con conseguente possibilità di effettuare una richiesta dei crediti ad un account admin;
  - **Acquisto guidato** (`compra_maglia.php` → `conferma_acquisto.php`).

### Per gli amministratori:
Dopo aver effettuato l'accesso in `login.php`, l'admin, entrando in `homepage_admin.php`, può compiere le seguenti azioni:
- **Inserimento di un record** (`inserimenti.php`):
  - Inserimento di un giocatore (`inserimento_giocatore.php`), che può essere un portiere (`inserisci_portiere.php`), o un difensore (`inserisci_difensore.php`), o un centrocampista (`inserisci_centrocampista.php`) oppure un attaccante (`inserisci_attaccante.php`);
  - Inserimento di una maglia (`inserimento_maglia.php`).
- **Modifiche di un record** (`modifiche.php`):
  - Modifica di un giocatore (`modifica_giocatore.php`);
  - Modifica di una maglia (`modifica_maglia.php`);
  - Modifica di un utente (`modifica_utente.php`).
- **Cancellazione di un giocatore** (`cancella_giocatore.php`);
- **Visualizzazione delle tabelle** (`visualizzazione_tabelle.php`):
  - Visualizzare la **Tabella di tutti i giocatori dell'attuale stagione** (`tabella_giocatore.php`);
  - Visualizzare la **Tabella di tutte le maglie** (`tabella_maglia.php`).
- **Visualizzazione degli utenti registrati** (`dashboard.php`);
- Effettuare l'**accettazione delle richieste dei crediti** dagli utenti (`accettazione_crediti.php`);
- Visualizzare lo **Storico degli acquisti effettuati** da tutti gli utenti (`storico_acquisti.php`);
- Visualizzare lo **Storico degli inserimenti effettuati** da tutti gli utenti admin (`storico_inserimenti.php`).

## Setup per il database:
L'utente amministratore deve possedere i seguenti prerequisiti:
- **PHP** 8.x (estensione `mysqli` attiva);
- **MySQL/MariaDB** 10.x+;
- **Server web** (Apache consigliato; va bene XAMPP/MAMP/WAMP o Docker);
- **Browser moderno**, come Google Chrome.
Una volta creato l'account utente del server locale (con tutti i privilegi concessi e selezionato tutti i privilegi) su phpMyAdmin, tramite `install.php`, viene creato tutto lo schema e le tabelle, con i dati d'esempio.
Una volta caricati i dati, dopo aver effettuato l'autenticazione su `install.php`, l'amministratore può visionare il sito.
> **Suggerimento:** se usi XAMPP, accertati che Apache e MySQL siano attivi e che `extension=mysqli` sia abilitata nel `php.ini`.

Il sito sarà visionabile SOLTANTO SE l'amministratore ha posizionato tutta questa cartella di git, all'interno di httdocs (aprendola da XAMPP):<br>
--> **Clona la repo** nella cartella servita da Apache (es. `htdocs`):
   ```bash
   git clone https://github.com/cristian03git/LWEB-Homework2.git
   ```

## Struttura della cartella `LWEB-Homework2`:
All'interno di questa cartella, oltre alle pagine sopra citate, sono presenti altre due cartelle: 
- `img/` – asset grafici (loghi, immagini prodotto, icone).
- `styles/` – fogli di stile CSS.


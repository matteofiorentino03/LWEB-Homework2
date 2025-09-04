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
  - **Acquisto guidato** (tramite `compra_maglia.php).

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

## Setup del Database

Per utilizzare correttamente l’applicazione, assicurati di avere i seguenti prerequisiti:

- **PHP** 8.x (con estensione `mysqli` abilitata)  
- **MySQL/MariaDB** 10.x o superiore  
- **Server web** (Apache consigliato; vanno bene XAMPP, MAMP, WAMP o Docker)  
- **Browser moderno** (es. Google Chrome)

### 1. `connect.php`

Tutte le pagine usano **un unico punto di accesso** al database:  
`connect.php`.  

- Centralizza le credenziali (`host`, `username`, `password`, `dbname`).  
- In fase di sviluppo si connette come **root** (senza password) su `localhost`.  
- Dopo l’installazione, carica automaticamente le credenziali da **`config.php`** (generato da `install.php`).  

In questo modo non è necessario modificare ogni script quando cambiano i dati di connessione: basta aggiornare `config.php`.


### 2. `install.php`

Serve ad **inizializzare il database**.  

- Si collega al server MySQL come **root** (senza password).  
- Crea lo schema `playerbase2` e tutte le tabelle richieste.  
- Inserisce i dati di esempio (utenti di default, giocatori, maglie, ecc.).  
- Genera automaticamente un file **`config.php`**, usato poi da `connect.php` per tutte le connessioni successive.  

⚠️ **Attenzione:** l’installazione ricrea lo schema da zero. Tutti i dati precedenti andranno persi.  


### 3. Avvio del sito

1. Clona la repository nella cartella servita da Apache (`htdocs` in XAMPP, `MAMP/htdocs`, ecc.):  
   ```bash
   git clone https://github.com/cristian03git/LWEB-Homework2.git

---

## Struttura della cartella:
All'interno di questa cartella, oltre alle pagine sopra citate, sono presenti altre due cartelle: 
- `img/` – asset grafici (loghi, immagini prodotto, icone).
- `styles/` – fogli di stile CSS.


# Rimozione Sistematica Funzionalità Non Necessarie

## Panoramica

Questo documento descrive la rimozione sistematica di tutte le funzionalità relative a sconti, ricorrenze, spedizioni e metodi di pagamento dal sistema di gestione documenti.

## Modifiche Database (Migrazione)

### Campi Rimossi
- **Tabella `documents`:**
  - `metodo_pagamento_id`
  - `conto_bancario_id`

- **Tabella `documents_products`:**
  - `tipo_sconto`
  - `sconto`

- **Tabella `documents_altro`:**
  - `tipo_sconto`
  - `sconto`
  - `ricorrenza`

### Tabelle Eliminate
- `documents_rate`
- `documents_spedizioni`
- `documents_trasporto`
- `documents_dettagli`
- `metodi_pagamento`
- `conti_bancari`
- `spedizioni`

## Modelli Eliminati

- `DocumentRata.php`
- `DocumentSpedizione.php`
- `DocumentTrasporto.php`
- `MetodoPagamento.php`
- `ContoBancario.php`
- `Spedizione.php`

## Controller Eliminati

- `SpedizioneController.php`
- `MetodoPagamentoController.php`
- `ContoBancarioController.php`

## Controller Base Modificato

### AbstractDocumentController.php
- Rimossi tutti i riferimenti a sconti, ricorrenze, spedizioni e metodi di pagamento
- Impostati valori di default per i campi sconto (0% e 0)
- Semplificata la validazione
- Rimossi i metodi non necessari
- Codice rimosso mantenuto commentato per documentazione

## Modelli Aggiornati

### Document.php
- Rimossi i campi dal fillable
- Rimosse le relazioni (commentate per documentazione)

### DocumentProduct.php
- Rimossi `tipo_sconto` e `sconto` dal fillable

### DocumentAltro.php
- Mantenuto il fillable originale (i campi sono stati rimossi dal DB)

## Helper Aggiornato

### FunctionsHelper.php
- Rimossi i calcoli di sconto e spedizione
- Codice rimosso mantenuto commentato per documentazione
- Calcoli di importo semplificati (senza sconti)

## Rotte

### web.php
- Rimosse le rotte per metodi-pagamento, conti-bancari e spedizioni
- Rotte mantenute commentate per documentazione

## Codice Commentato per Documentazione

Tutti i riferimenti rimossi sono stati commentati invece che eliminati per:
- Mantenere traccia di cosa è stato rimosso
- Facilitare eventuali rollback
- Documentare le modifiche effettuate
- Permettere modifiche future

## Risultato Finale

Il sistema è ora completamente pulito da tutte le funzionalità non necessarie. Il `OrdineAcquistoController` eredita automaticamente tutte le modifiche dal controller base.

### Funzionalità Mantenute
- Elementi base (prodotti, altro, descrizioni)
- Indirizzi per mittente/destinatario
- Allegati
- Aliquote IVA per i calcoli fiscali

### Funzionalità Rimosse
- Sconti (tipo_sconto, sconto)
- Ricorrenze
- Spedizioni
- Metodi di pagamento
- Conti bancari
- Rate di pagamento
- Dettagli trasporto

## Note Tecniche

- La migrazione è reversibile (metodo `down()` implementato)
- Tutti i controller che ereditano da `AbstractDocumentController` beneficiano automaticamente delle modifiche
- Il codice commentato serve come documentazione delle modifiche effettuate
- I calcoli di importo sono stati semplificati (quantità × prezzo senza sconti)

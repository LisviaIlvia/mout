# 🗺️ MAPPA CONCETTUALE COMPLETA - MOUT CRM

## 📋 INDICE
1. [PANORAMICA GENERALE](#panoramica-generale)
2. [ARCHITETTURA TECNICA](#architettura-tecnica)
3. [FLUSSO UTENTE](#flusso-utente)
4. [GESTIONE ENTITÀ](#gestione-entità)
5. [GESTIONE DOCUMENTI](#gestione-documenti)
6. [GESTIONE PRODOTTI](#gestione-prodotti)
7. [SISTEMA DI SICUREZZA](#sistema-di-sicurezza)
8. [FUNZIONALITÀ AVANZATE](#funzionalità-avanzate)
9. [INTEGRAZIONI](#integrazioni)
10. [FLUSSI DI BUSINESS](#flussi-di-business)

---

## 🎯 PANORAMICA GENERALE

```
┌─────────────────────────────────────────────────────────────────┐
│                        MOUT CRM SYSTEM                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  🔐 AUTHENTICATION LAYER                                       │
│  ├── Login/Logout                                              │
│  ├── Password Reset                                            │
│  └── Remember Me                                               │
│                                                                 │
│  👥 USER MANAGEMENT LAYER                                      │
│  ├── Users                                                     │
│  ├── Roles                                                     │
│  └── Permissions                                               │
│                                                                 │
│  🏢 BUSINESS LAYER                                             │
│  ├── Entities (Clienti/Fornitori)                             │
│  ├── Documents (Ordini/Fatture/DDT)                           │
│  ├── Products (Merci/Servizi)                                 │
│  └── Warehouse (Magazzino)                                    │
│                                                                 │
│  🔗 INTEGRATION LAYER                                          │
│  ├── QR Code System                                            │
│  ├── Zoom Integration                                          │
│  ├── Excel Export                                              │
│  └── PDF Generation                                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🏗️ ARCHITETTURA TECNICA

```
┌─────────────────────────────────────────────────────────────────┐
│                    ARCHITETTURA TECNICA                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  BACKEND (Laravel 12)                                          │
│  ├── Controllers                                               │
│  │   ├── AbstractCrudController                               │
│  │   ├── AbstractEntityController                             │
│  │   ├── AbstractDocumentController                           │
│  │   └── Specific Controllers                                 │
│  │                                                             │
│  ├── Models                                                    │
│  │   ├── Entity, User, Document                               │
│  │   ├── Product, Category, AliquotaIva                       │
│  │   └── Relationships                                         │
│  │                                                             │
│  ├── Services                                                  │
│  │   ├── ZoomService                                           │
│  │   ├── ExportService                                         │
│  │   └── QrCodeService                                         │
│  │                                                             │
│  └── Middleware                                                │
│      ├── Authentication                                        │
│      ├── Permission                                            │
│      └── Inertia                                               │
│                                                                 │
│  FRONTEND (Vue 3 + Vuetify)                                    │
│  ├── Layouts                                                   │
│  │   ├── LayoutAuth (Area autenticata)                        │
│  │   ├── LayoutGuest (Login)                                  │
│  │   └── LayoutPublic (QR View)                               │
│  │                                                             │
│  ├── Components                                                │
│  │   ├── CrudIndex (Tabelle)                                  │
│  │   ├── DialogCreate/Edit/Show/Delete                        │
│  │   └── DocumentGroup                                         │
│  │                                                             │
│  ├── Pages                                                     │
│  │   ├── Dashboard                                             │
│  │   ├── Entities (Clienti/Fornitori)                         │
│  │   ├── Documents (Ordini/Fatture)                           │
│  │   └── Products (Merci/Servizi)                             │
│  │                                                             │
│  └── Store (Pinia)                                             │
│      └── YearStore (Gestione anno fiscale)                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 👤 FLUSSO UTENTE

```
┌─────────────────────────────────────────────────────────────────┐
│                        FLUSSO UTENTE                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. ACCESSO SISTEMA                                            │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │   LOGIN     │───▶│ VALIDAZIONE │───▶│  DASHBOARD  │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │PASSWORD RESET│   │REMEMBER ME  │   │SIDEBAR MENU  │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                 │
│  2. NAVIGAZIONE                                                │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │  DASHBOARD  │───▶│   ENTITÀ    │───▶│  DOCUMENTI  │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │  MAGAZZINO  │   │   PRODOTTI   │   │  IMPOSTAZ.   │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                 │
│  3. OPERAZIONI CRUD                                            │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │    CREATE   │───▶│     READ    │───▶│    UPDATE   │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│     ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│     │    DELETE   │   │    CLONE     │   │    EXPORT    │     │
│     └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 👥 GESTIONE ENTITÀ

```
┌─────────────────────────────────────────────────────────────────┐
│                      GESTIONE ENTITÀ                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ENTITY (Entità Base)                                          │
│  ├── Codice                                                    │
│  ├── Nome                                                      │
│  ├── Partita IVA                                               │
│  ├── Codice Fiscale                                            │
│  ├── Email                                                     │
│  ├── Telefono                                                  │
│  ├── PEC                                                       │
│  ├── CUU                                                       │
│  ├── Note                                                      │
│  └── Type (clienti/fornitori)                                  │
│                                                                 │
│  RELAZIONI ENTITY                                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   ENTITY    │───▶│  INDIRIZZI  │───▶│  REFERENTI  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ DOCUMENTI   │   │  ATTIVITÀ    │   │   CLIENTI   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  FORNITORI  │   │   ORDINI     │   │   FATTURE   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO GESTIONE ENTITÀ                                        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CREATE    │───▶│    EDIT     │───▶│    SHOW     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   DELETE    │   │  INDIRIZZI   │   │  REFERENTI   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📄 GESTIONE DOCUMENTI

```
┌─────────────────────────────────────────────────────────────────┐
│                    GESTIONE DOCUMENTI                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  DOCUMENT (Documento Base)                                     │
│  ├── Type (ordini-vendita, ordini-acquisto, fatture, DDT)      │
│  ├── Numero                                                    │
│  ├── Data                                                      │
│  ├── Stato                                                     │
│  ├── Note                                                      │
│  ├── Entity ID (Cliente/Fornitore)                             │
│  └── Parent ID (Documento padre)                               │
│                                                                 │
│  RELAZIONI DOCUMENT                                            │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  DOCUMENT   │───▶│   PRODUCTS  │───▶│    ALTRO    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ INDIRIZZO   │   │ DESCRIZIONI  │   │   DETTAGLI  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    MEDIA    │   │   PARENT     │   │  CHILDREN   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  TIPI DI DOCUMENTO                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ORDINI VENDITA│   │ORDINI ACQUISTO│   │  FATTURE    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    DDT      │   │NOTE CREDITO  │   │  PROFORMA   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO DOCUMENTO                                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CREATE    │───▶│    EDIT     │───▶│    SHOW     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    CLONE    │   │    EXPORT    │   │     PDF     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   QR CODE   │   │    MAGIC     │   │   DELETE    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 GESTIONE PRODOTTI

```
┌─────────────────────────────────────────────────────────────────┐
│                     GESTIONE PRODOTTI                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PRODUCT (Prodotto Base)                                       │
│  ├── Type (merci/servizi)                                      │
│  ├── Codice                                                    │
│  ├── Nome                                                      │
│  ├── Descrizione                                               │
│  ├── Unità di Misura                                           │
│  ├── Prezzo                                                    │
│  ├── Aliquota IVA ID                                           │
│  ├── Tax In                                                    │
│  └── Giacenza Iniziale                                         │
│                                                                 │
│  CATEGORY (Categoria)                                          │
│  ├── Nome                                                      │
│  └── Parent ID (Categoria padre)                               │
│                                                                 │
│  ALIQUOTA IVA                                                  │
│  ├── Aliquota (%)                                              │
│  ├── Nome                                                      │
│  ├── Descrizione                                               │
│  └── Predefinita                                               │
│                                                                 │
│  RELAZIONI PRODOTTI                                            │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   PRODUCT   │───▶│  CATEGORY   │───▶│ALIQUOTA IVA │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │DOCUMENT PROD│   │  MAGAZZINO   │   │   PREZZI    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO PRODOTTI                                                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CREATE    │───▶│    EDIT     │───▶│    SHOW     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   DELETE    │   │  CATEGORIE   │   │  ALIQUOTE   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔐 SISTEMA DI SICUREZZA

```
┌─────────────────────────────────────────────────────────────────┐
│                    SISTEMA DI SICUREZZA                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  AUTHENTICATION                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    LOGIN    │───▶│ VALIDAZIONE │───▶│   SESSION   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │PASSWORD RESET│   │REMEMBER ME  │   │   LOGOUT    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  AUTHORIZATION                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    USER     │───▶│    ROLE     │───▶│ PERMISSION  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   ACCESS    │   │  OPERATIONS  │   │   RESOURCE   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  PERMISSIONS MATRIX                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    SHOW     │    │   CREATE    │    │    EDIT     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   DELETE    │    │   EXPORT    │    │     PDF     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    CLONE    │    │    MAGIC    │    │   QR CODE   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  MIDDLEWARE STACK                                               │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  WEB MIDDLEWARE │───▶│AUTH MIDDLEWARE│───▶│PERMISSION MIDDLEWARE│        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚀 FUNZIONALITÀ AVANZATE

```
┌─────────────────────────────────────────────────────────────────┐
│                   FUNZIONALITÀ AVANZATE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  QR CODE SYSTEM                                                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  GENERATE   │───▶│   DISPLAY   │───▶│   DOWNLOAD  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   PUBLIC    │   │   SCANNABLE  │   │  FORMATS    │        │
│  │    VIEW     │   │     URL      │   │(SVG/PNG/EPS)│        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  EXPORT SYSTEM                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    EXCEL    │───▶│     PDF     │───▶│   CUSTOM    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  DOCUMENTS  │   │  MAGAZZINO   │   │   REPORTS   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  MAGIC SYSTEM                                                   │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   SYNC      │───▶│   PROCESS   │───▶│   UPDATE    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  ZOOM INTEGRATION                                               │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   TOKEN     │───▶│   MEETING   │───▶│ PARTICIPANT│        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔗 INTEGRAZIONI

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTEGRAZIONI                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ZOOM API                                                       │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   TOKEN     │───▶│   MEETING   │───▶│ PARTICIPANT│        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CREATE    │   │    LIST      │   │     ADD     │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  EXCEL EXPORT                                                    │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  MAATWEBSITE│───▶│   FORMAT    │───▶│   DOWNLOAD  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  PDF GENERATION                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   SPATIE    │───▶│   TEMPLATE  │───▶│   RENDER    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  QR CODE LIBRARY                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ SIMPLE QR   │───▶│   FORMAT    │───▶│   OUTPUT    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📈 FLUSSI DI BUSINESS

```
┌─────────────────────────────────────────────────────────────────┐
│                     FLUSSI DI BUSINESS                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  FLUSSO ORDINE VENDITA                                         │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CLIENTE   │───▶│ORDINE VENDITA│───▶│   PRODOTTI  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  INDIRIZZO  │   │   CALCOLI    │   │   QR CODE   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │    PDF      │   │   EXPORT     │   │   MAGAZZINO │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO ORDINE ACQUISTO                                        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  FORNITORE  │───▶│ORDINE ACQUISTO│───▶│   PRODOTTI  │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │  INDIRIZZO  │   │   CALCOLI    │   │   EXPORT    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO MAGAZZINO                                               │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   PRODOTTO  │───▶│   MOVIMENTI │───▶│  GIACENZA   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   ENTRATA   │   │    USCITA    │   │   REPORT    │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
│  FLUSSO QR CODE                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   ORDINE    │───▶│  GENERATE   │───▶│   DISPLAY   │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│           │                     │                     │        │
│           ▼                     ▼                     ▼        │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   DOWNLOAD  │   │   PUBLIC     │   │   SCAN      │        │
│  └─────────────┘    └─────────────┘    └─────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 CONCLUSIONI

Il **MOUT CRM** è un sistema completo e integrato che gestisce:

### ✅ **FUNZIONALITÀ CORE**
- Gestione completa clienti e fornitori
- Documentazione commerciale (ordini, fatture, DDT)
- Gestione prodotti e magazzino
- Sistema di permessi granulare

### ✅ **FUNZIONALITÀ AVANZATE**
- QR Code per accesso pubblico
- Export Excel/PDF
- Integrazione Zoom
- Sistema di relazioni tra documenti

### ✅ **ARCHITETTURA ROBUSTA**
- Laravel 12 + Vue 3 + Vuetify
- Inertia.js per SPA
- Sistema di permessi Spatie
- Componenti riutilizzabili

### ✅ **USER EXPERIENCE**
- UI moderna e responsive
- Flussi intuitivi
- Accesso pubblico via QR
- Dashboard centralizzata

Il sistema è **enterprise-ready** e copre tutti gli aspetti di gestione aziendale moderna. 
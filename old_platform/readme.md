# Tutor81 -  TutorItalia

Piattaforma gestionale elearning

## Description

Sito per l'amministrazione della piattaforma gestionale di corsi in elearning e in aula di Tutor81 e TutorItalia.

## Routing

### codifica URL

    http://hub.tutor81.com/area/page/section/


dove **area** può assumere i seguanti valori:

* user
* company
* tutor
* member
* admin

A seconda del valore assunto verrà visualizzata l'interfaccia grafica relativa.

L'area viene utilizzata per decidere quale file

    content-area.php

caricare nel container principale. In questi file, tramite un ciclo switch si
decide quale pagina caricare in base ai parametri **page** e **section** dell'URL

Ad esempio nella home page dell'area company vengono visulizzati i pannelli per 
la gestione degli utenti e lo stato di avanzamento dei corsi e l'eneco dei corsi 
da selezionare per acquistarli.

## Struttura delle directory

- api *//embrione di un API da utilizzare con l'ecommerce*
- css
- download *//contiene file scaricabili*
- fonts
- graphs *//grafici*
- img
- js
- lib *//classi per l'interrogazione del database*
- manage *//file che gestiscono le chiamate ajax(POST) a cui restituiscono i dati richiesti*
- modals *//struttura e, a volte, contenuto di modals bootstrap*
- pages *//pagine principali*
    - sections *//pannelli e menu contenuti in genere nelle pages*
- report *//alcune pagine di report*


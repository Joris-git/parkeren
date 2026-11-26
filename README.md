# Wat kost je zondag?

Ludieke rekentool: wat kost parkeren op zondag in Utrecht zone A1 en A2 vanaf 1 januari 2027? Kies een verjaardag, kerkbezoek, sportochtend of oma op bezoek en zie het bonnetje.

## Gebruik

Open `index.html` in een browser. Er is geen build nodig; alles staat in één bestand.

## Rekenregels

- Betaald op zondag: A1 en A2 van 09.00 tot 23.00 uur (vanaf 2027). In 2026: A1 van 12.00 tot 18.00 uur, A2 gratis.
- Tarieven standaard die van 2026: A1 € 8,01 en A2 € 6,94 per uur. Aan te passen in de tool zodra de tarieven voor 2027 bekend zijn.
- Bezoekerskorting 50% in A1 en A2, alleen voor bezoek van bewoners (verjaardag, oma). De grens op kortingsuren per jaar wordt niet meegerekend.
- Bedrag = betaalde uren binnen het venster × tarief × aantal auto's × keer per jaar.

Geen officiële berekening en niet van de gemeente Utrecht.

## Toeter-teller

De knop "TOET!" telt hoeveel bezoekers meetoeteren tegen betaald parkeren op zondag. De teller heeft PHP nodig (vrijwel elk webhostingpakket heeft dat).

- `api/toeter.php` bewaart het totaal in `data/teller.json`.
- De map `data/` moet schrijfbaar zijn voor de webserver. `data/.htaccess` blokkeert toegang via het web op Apache-servers. Gebruikt je host nginx, laat dan `data/` afschermen of zet de map buiten de webroot.
- Er wordt niets per bezoeker opgeslagen: geen IP-adressen, geen cookies. De browser onthoudt alleen lokaal dat er al getoeterd is.
- Per browser telt de eerste klik; daarna geeft de knop alleen nog grapjes.
- Tegen opdrijven telt de server maximaal 60 toeters per minuut, van alle bezoekers samen. Daarboven krijgt de bezoeker de melding dat het even druk is.
- Let op: de webserver van je host houdt zelf vaak toegangslogs met IP-adressen bij. Die staan los van de teller. Bij veel hosts kun je ze in het controlepaneel uitzetten of anonimiseren.
- Zonder PHP (bijvoorbeeld op GitHub Pages of als je het bestand lokaal opent) werkt de knop wel, maar loopt er geen teller.

Het is een ludieke teller, geen petitie. De teller is niet fraudebestendig.

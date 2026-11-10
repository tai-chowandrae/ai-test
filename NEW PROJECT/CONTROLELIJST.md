# Controlelijst voor ontwikkeling en oplevering

Gebruik alleen onderdelen die op de opdracht van toepassing zijn. Dit document is een werkhulp, geen verklaring dat controles al zijn uitgevoerd.

## Voor wijzigingen

- Lees de afspraken, projectstructuur, Git-status en betrokken code.
- Controleer bestaande gebruikerswijzigingen en behoud ze.
- Leg noodzakelijke aannames en projectkeuzes vast; vraag alleen wanneer uitvoering anders riskant is.
- Controleer dat testverbindingen en tests niet op productie zijn gericht.

## Code en interface

- Logica is verdeeld over begrijpelijke modules en gedeelde helpers.
- CSS en JavaScript staan in eigen bestanden; comments leggen structuur en intentie uit.
- Naamgeving en talen volgen de gekozen projectconventies.
- Pagina's hebben een nuttige meta description; beschermde pagina's zijn niet onbedoeld indexeerbaar.
- Formulieren, meldingen, lege staten, labels en toetsenbordbediening zijn bruikbaar.
- Normale interacties gebruiken geen alert, confirm of prompt.
- Mobiel en desktop: geen overlap, afgesneden tekst of onbruikbare knoppen.
- Routes, refreshes, redirects en basispaden werken zoals bedoeld.

## Database en rechten

- Alle queries bekeken, inclusief zoeken, sorteren, filters, exports en dynamische querydelen.
- Waarden zijn gebonden; dynamische identifiers hebben een vaste toegestane lijst.
- Invoer wordt op de server gecontroleerd; JavaScript uitschakelen omzeilt geen bedrijfsregels.
- Niet-ingelogde bezoekers krijgen geen beschermde gegevens.
- Een gewone gebruiker kan geen beheeractie uitvoeren door een aanvraag handmatig te sturen.
- Test met twee gebruikers dat lezen, tellen, exporteren, wijzigen en verwijderen geen gegevens van de ander blootlegt.
- Test apostroffen en SQL-achtige tekst als gewone gegevens; voer geen destructieve payloads uit tegen productie.
- Inactieve of verwijderde accounts verliezen toegang; ingetrokken rollen blijven niet in oude sessies hangen.
- Mutaties hebben CSRF-bescherming; ontbrekende of ongeldige tokens worden geweigerd.
- Login en registratie hebben gedeelde poginglimieten en veilige opslag daarvan.
- Foutmeldingen lekken geen SQL, databasenamen, paden, configuratie of stack traces.

## Configuratie en hosting

- Lokale en live gegevens blijven apart; ontbrekende live instellingen worden geweigerd.
- Echte configuratie staat niet in Git, voorbeelden, screenshots of installatie-instructies.
- De webserver weigert toegang tot config, database, dotfiles, tests, logs, back-ups en updates. Test concrete URLs en gecodeerde/padvarianten waar relevant.
- Controleer de .htaccess op de werkelijke Apache-hosting; PHP's ingebouwde server bewijst geen Apache-afscherming.
- Controleer HTTPS, cookies, beveiligingsheaders, schrijfrechten en hostinginstellingen voor foutweergave.
- De applicatieaccount heeft minimale databaserechten; netwerktoegang tot de database is waar mogelijk beperkt.
- Een publieke registratie met domeincontrole claimt geen geverifieerd mailboxbezit zonder een verificatiestap.

## Testverslag

- Voer passende syntaxcontroles, gerichte regressietests en waar nodig browsertests uit.
- Gebruik wegwerpgegevens en ruim uitsluitend eigen testgegevens op.
- Onderscheid statische codecontrole, geisoleerde tests, echte database-integratie, Apache-tests en live hostingcontrole.
- Meld niet-uitgevoerde controles, beperkingen en nog openstaande risico's expliciet.

## Uploadpakket na een commit

- Gebruik de gecommitteerde versies van alle toegevoegde en gewijzigde bestanden, met mappenstructuur en verborgen bestanden zoals .htaccess.
- Naam: update-JJJJ-MM-DD_UU-mm-ss_commitnaam.zip, met de committerdatum/tijd in Europe/Amsterdam en een bestandsveilige titel volgens AGENTS.md.
- Bewaar het pakket in de van Git uitgesloten map updates/.
- Maak naast de zip een bestand met dezelfde basisnaam plus _INSTALLATIE.txt; voeg precies dat bestand ook aan de zip toe.
- Vermeld bovenaan zipnaam en volledig commit-ID. Geen geheimen opnemen.
- Geef concrete Nederlandse stappen: back-up, vereisten, bestanden en bestemmingen, configuratie, controles en eventuele handmatig te verwijderen bestanden.
- Vermeld expliciet of SQL nodig is; zo ja exacte migratiebestanden, volgorde, uitvoermoment en eventuele vereiste eerdere updates.
- Onderscheid migraties van een volledig schema; waarschuw dat een volledig schema niet op een bestaande database mag worden geimporteerd.
- Vergelijk zipinhoud met de commit, controleer het instructiebestand en vermeld verwijderingen apart: een zip verwijdert geen bestanden op hosting.
- Geef een downloadlink en meld of het pakket een eerdere update vereist. Deploy alleen als dat onderdeel van de opdracht is.

# Projectafspraken voor samenwerking met Codex

Dit bestand beschrijft de werkafspraken en verwachtingen voor een project. Het bevat geen projectinhoud, domeindata of klantcontext, zodat het herbruikbaar is bij een volgend project.

## Algemene werkwijze

- Werk proactief door tot de vraag echt is afgehandeld.
- Lees eerst de bestaande projectstructuur en relevante bestanden voordat je wijzigingen maakt.
- Sluit aan op de bestaande stijl, naamgeving, mappenstructuur en technische keuzes van het project.
- Maak gerichte wijzigingen en vermijd ongevraagde refactors.
- Vraag alleen om verduidelijking als een redelijke aanname riskant zou zijn.
- Geef korte tussentijdse updates bij groter werk, vooral tijdens zoeken, analyseren, aanpassen en testen.
- Antwoord in het Nederlands, tenzij expliciet om een andere taal wordt gevraagd.

## Code en bestanden

- Gebruik bij zoeken bij voorkeur `rg` of `rg --files`.
- Gebruik `apply_patch` voor handmatige bestandswijzigingen.
- Bewaar bestaande wijzigingen van de gebruiker; draai niets terug zonder expliciete opdracht.
- Verwijder geen bestanden of data zonder duidelijke toestemming.
- Houd code begrijpelijk en voeg alleen comments toe waar ze echt helpen.
- Voorzie alle pagina's van korte, nuttige comments die de hoofdlogica, belangrijke blokken en niet-direct-zichtbare keuzes duiden.
- Gebruik comments om intentie en structuur uit te leggen, niet om elke losse regel code te herhalen.
- Gebruik zo min mogelijk inline styling en inline JavaScript.
- Plaats styling in bestanden binnen `css/` en JavaScript in bestanden binnen `js/`, passend bij de bestaande projectstructuur.
- Gebruik inline code alleen bij kleine, goed gemotiveerde uitzonderingen of wanneer de hosting/context dit noodzakelijk maakt.
- Gebruik ASCII tenzij het bestand of de inhoud duidelijk om Unicode vraagt.
- Leg in de eindreactie kort uit wat is aangepast en hoe het is gecontroleerd.

## Naamgeving

- Gebruik duidelijke, beschrijvende namen voor bestanden, functies, variabelen, databasevelden en routes.
- Gebruik consequent dezelfde taal binnen een project. Kies bij voorkeur Nederlands voor zichtbare labels en Engels voor technische code als het bestaande project dat ook doet.
- Gebruik `PascalCase` voor PHP classes en database-entiteiten wanneer dat aansluit bij het project.
- Gebruik `camelCase` voor lokale variabelen en functies in JavaScript.
- Gebruik `snake_case` of bestaande conventies voor databasekolommen als het project dat al gebruikt; verander bestaande conventies niet onnodig.
- Vermijd afkortingen, tenzij ze algemeen bekend zijn binnen het project.

## Mappenstructuur

- Houd de root schoon en plaats logica, assets en configuratie in herkenbare mappen.
- Gebruik waar passend deze hoofdstructuur:
  - `api/` voor endpoints en serveracties.
  - `config/` voor configuratie en databaseverbindingen.
  - `css/` voor stylesheets.
  - `js/` voor JavaScript.
  - `images/` voor afbeeldingen en visuele assets.
  - `includes/` voor gedeelde PHP-onderdelen zoals layout, auth en helpers.
  - `database/` voor schema's, migraties, seedbestanden of databasehulpen.
- Plaats herbruikbare code centraal en voorkom dubbele implementaties in losse pagina's.
- Verdeel functionaliteit over losse, logische pagina's of modules; maak geen enkele grote HTML/PHP-pagina waarin alle functionaliteit samenkomt.
- Gebruik gedeelde includes, layouts en helpers om herhaling tussen pagina's te voorkomen.
- Houd publieke bestanden geschikt voor shared hosting; ga niet uit van build tooling tenzij het project daar al voor is ingericht.

## Veiligheid en privacy

- Neem geen gevoelige projectdata, persoonsgegevens, wachtwoorden, hashes of database-inhoud over in herbruikbare documenten.
- Behandel dumps, exports en configuratiebestanden als mogelijk gevoelig.
- Vermijd het tonen of verspreiden van secrets; verwijs liever naar variabelen of placeholders.
- Wees extra voorzichtig met acties die data wijzigen, importeren, verwijderen of overschrijven.

## Testen en verificatie

- Controleer wijzigingen waar mogelijk met bestaande tests, linters of een gerichte handmatige check.
- Als testen niet mogelijk is, meld dat duidelijk en noem de resterende onzekerheid.
- Bij frontendwerk: start waar nodig een lokale server en controleer de interface in de browser.
- Controleer bij visueel werk of tekst niet overlapt, knoppen passen en mobiele/desktopweergaven bruikbaar blijven.

## Frontendverwachtingen

- Bouw de echte bruikbare interface, geen marketingpagina, tenzij daarom wordt gevraagd.
- Houd operationele tools rustig, overzichtelijk en efficient.
- Gebruik bestaande designpatronen en componenten van het project.
- Voeg aan iedere HTML/PHP-pagina een passende `<meta name="description">` toe in de `<head>`.
- Beschrijf in de meta description kort en concreet wat de gebruiker op die pagina kan doen.
- Zorg dat formulieren, tabellen, knoppen, foutmeldingen en lege staten logisch en compleet aanvoelen.
- Gebruik geen JavaScript `alert()`, `confirm()` of `prompt()` voor normale interacties; gebruik netjes opgemaakte meldingen, modals of inline validatie in de interface.
- Maak succes-, fout- en waarschuwingsmeldingen duidelijk herkenbaar en consistent met de rest van het ontwerp.
- Gebruik icon-fonts voor iconen, bijvoorbeeld Font Awesome of Bootstrap Icons, tenzij het bestaande project al een andere iconoplossing gebruikt.
- Gebruik iconen waar dat natuurlijk is voor acties, maar houd de interface helder.
- Vermijd overbodige decoratie, zware gradients en grote hero-secties in praktische applicaties.

## URLs en routing

- Gebruik vriendelijke, leesbare URLs zonder onnodige `.php` in zichtbare navigatie wanneer de hosting dit ondersteunt.
- Regel vriendelijke URLs via `.htaccess` op Apache/shared hosting.
- Houd routes logisch, kort en voorspelbaar, bijvoorbeeld `/login`, `/dashboard`, `/admin` en `/ritten`.
- Zorg dat redirects consistent zijn en voorkom dubbele toegankelijke varianten van dezelfde pagina.
- Test na routewijzigingen altijd of directe URLs, refreshes en redirects blijven werken.

## Robots en crawlers

- Voeg standaard een `robots.txt` toe voor niet-publieke, test- of applicatieprojecten.
- Blokkeer crawlers standaard met:

```txt
User-agent: *
Disallow: /
```

- Maak alleen een crawlbare configuratie wanneer het expliciet om een publieke website gaat die geindexeerd mag worden.
- Voorkom dat adminpagina's, testomgevingen, exports, API-routes of uploads per ongeluk indexeerbaar zijn.

## Hosting bij TransIP

- Bouw standaard geschikt voor reguliere TransIP webhosting met Apache, PHP en MySQL/MariaDB, tenzij anders afgesproken.
- Gebruik `.htaccess` voor URL-rewrites en basisinstellingen die op shared hosting passen.
- Ga niet uit van shell-toegang, Node buildstappen, achtergrondprocessen of serverconfiguratie buiten de hostingomgeving.
- Houd configuratie buiten de code waar mogelijk en gebruik aparte configbestanden of environment-achtige instellingen.
- Zorg dat databaseverbindingen, paden en redirects werken vanuit een subdirectory of domeinroot als dat nodig is.
- Gebruik relatieve paden of centrale padhelpers waar dat onderhoud en hostingcompatibiliteit verbetert.
- Controleer bij oplevering of de applicatie zonder lokale ontwikkelserver kan draaien wanneer het een PHP/shared-hostingproject is.

## Communicatiestijl

- Wees duidelijk, kort en praktisch.
- Noem belangrijke keuzes en risico's zonder lange omwegen.
- Geef bij afronding een compacte samenvatting met eventueel uitgevoerde checks.
- Als iets niet gelukt is, zeg dat eerlijk en concreet.

## Hergebruik in een nieuw project

Plaats dit bestand in de root van een nieuw project en verwijs er aan het begin van de samenwerking naar. Vul eventueel projectspecifieke afspraken aan in een apart bestand, zodat deze algemene werkafspraken schoon en herbruikbaar blijven.

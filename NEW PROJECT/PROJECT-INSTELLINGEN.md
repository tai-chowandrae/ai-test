# Projectinstellingen

Dit bestand bevat de technische keuzes voor het nieuwe project. Vul het aan zonder geheimen. Expliciete projectkeuzes gaan voor algemene voorkeuren in `PROJECT-AFSPRAKEN.md`; behoud bij bestaande code de bestaande conventies tenzij anders afgesproken.

## Projectprofiel

- Naam: [invullen]
- Doel: [invullen]
- Doelgroep en rollen: [invullen]
- Eerste functionaliteit: [invullen]
- Buiten scope: [invullen indien bekend]
- Publieke website of besloten applicatie: standaard besloten applicatie.
- Hosting: standaard reguliere TransIP shared hosting met Apache, PHP en MySQL/MariaDB.
- Domeinroot of subdirectory: [invullen; ondersteun centrale basispadconfiguratie].
- Externe diensten: alleen toevoegen wanneer de functionaliteit die vereist; geen oude API-integraties overnemen.

## Techniek en naamgeving

- Gebruik een ondersteunde PHP-versie die beschikbaar is op de gekozen hosting, met PDO en pdo_mysql. Leg de werkelijke minimumversie vast in de project-README.
- Gebruik een ondersteunde MySQL/MariaDB-versie op de hosting; stem SQL daarop af.
- Frontend: gewone HTML, CSS en JavaScript, zonder framework of buildstap tenzij afgesproken.
- Apache met mod_rewrite en werkende .htaccess. Houd deployment geschikt voor shared hosting zonder shell-toegang of achtergrondprocessen.
- Organiseer endpoints in `api/`, configuratie in `config/`, gedeelde PHP in `includes/`, CSS in `css/`, JavaScript in `js/`, afbeeldingen in `images/`, SQL in `database/` en tests in `tests/`.
- Technische namen en comments in het Engels; zichtbare labels en meldingen in het Nederlands.
- Naamgeving bij een nieuw project: PascalCase voor PHP-classes en database-entiteiten, camelCase voor JavaScript-variabelen en functies, snake_case voor databasekolommen. Als PascalCase voor alle PHP-identifiers en databaseobjecten gewenst is, leg die afwijking hier vast voordat de implementatie begint. Meng conventies niet.
- Gebruikersschermen standaard mobile-first; beheer mag op desktop gericht zijn maar moet ook op smalle schermen bruikbaar blijven.
- Gebruik leesbare routes zonder .php waar ondersteund. Test directe URLs, vernieuwen en redirects, ook onder een subdirectory.

## Lokaal en live

- Houd afzonderlijke instellingen voor `local` en `live`; bewaar beide zodat wisselen geen gegevens overschrijft.
- Selecteer de omgeving met serverconfiguratie of een expliciete lokale instelling, nooit via HTTP_HOST of onbevestigde proxyheaders.
- Documenteer per omgeving de configuratievelden en de voorrangsregel van omgevingsvariabelen. Gebruik generieke namen zoals APP_ENV, DB_HOST, DB_NAME, DB_USER en DB_PASSWORD.
- Commit alleen voorbeeldconfiguratie zonder echte gegevens. Bewaar echte instellingen buiten de publieke webroot waar mogelijk, anders in een afgeschermd en van Git uitgesloten bestand. Gebruik geen echte live gegevens in een gedeeld lokaal/live voorbeeldbestand.
- Ontbrekende live gegevens moeten een veilige fout geven; val dan niet terug op een lokale database of root-account.
- Gebruik een aparte applicatiegebruiker met alleen noodzakelijke databaserechten. Gebruik voor schemawijzigingen een apart onderhoudsaccount.
- Leg benodigde PHP-extensies, schrijfrechten en installatiestappen vast. Test uitsluitend tegen een aparte testdatabase.

## Beveiligingsbasis

- Valideer alle invoer server-side, inclusief type, lengte, formaat en bedrijfsregels. Weiger arrays waar een enkele waarde wordt verwacht.
- Gebruik PDO prepared statements met gebonden waarden en native prepares; schakel meerdere MySQL-statements per aanvraag uit. Bind ook filters en paginering met het juiste datatype.
- Gebruik vaste toegestane lijsten voor dynamische tabelnamen, kolomnamen en sorteerrichtingen; parameters kunnen SQL-identifiers niet veilig vervangen.
- Verplicht authenticatie en controleer de rol bij iedere beschermde actie. Controleer eigendom van records server-side, ook bij lezen, exports, tellers, joins, wijzigingen en verwijderingen.
- Gebruik de ingelogde sessie als bron voor gebruikersidentiteit. Vertrouw niet op een meegestuurd UserId of op verborgen knoppen als toegangsbeveiliging.
- Controleer bij iedere ingelogde aanvraag of het account nog bestaat, actief is en de actuele rechten heeft.
- Gebruik password_hash en password_verify; sla geen gewone of omkeerbaar versleutelde wachtwoorden op. Ververs de sessie-ID na login en rechtenwijzigingen.
- Bescherm sessiecookies met HttpOnly, SameSite en Secure op HTTPS. Gebruik alleen vertrouwde serverinformatie voor TLS-detectie.
- Bescherm muterende aanvragen met CSRF-tokens en geschikte HTTP-methoden. Laat GET geen gegevens wijzigen.
- Beperk login- en registratiepogingen over sessies heen. Praktisch uitgangspunt per 15 minuten: login 20 per IP en 10 per e-mailadres, registratie 5 per IP en 3 per e-mailadres. Stem af op de gebruikersgroep en documenteer de gevolgen.
- Als bestandstellers worden gebruikt: gebruik locks, gehashte sleutels, afgeschermde opslag, vervaltijden en veilige foutafhandeling. Vertrouw niet op willekeurige forwardingheaders voor het client-IP.
- Als registratie een domeinbeperking vereist: vergelijk het volledige e-maildomein hoofdletterongevoelig op de server. Domeinbezit is geen mailboxbezit; gebruik e-mailverificatie, SSO of beheerdersgoedkeuring wanneer alleen bevoegde medewerkers toegang mogen krijgen.
- Escape uitvoer passend bij HTML, attributen, URLs of JavaScript. Gebruik veilige DOM-methoden voor ongevertrouwde tekst.
- Toon algemene foutmeldingen aan bezoekers; log technische fouten alleen op een afgeschermde plek. Zet display_errors en display_startup_errors op hostingniveau uit.
- Scherm dotfiles, configuratie, includes, SQL, dumps, logs, tests, storage en updates af. Blokkeer directorylisting en onbedoelde downloads van archieven en editorback-ups. Diagnostics uitsluitend via CLI of expliciet beveiligd beheer.
- Gebruik HTTPS op publieke hosts. Configureer vertrouwde TLS-terminatie expliciet; localhost mag voor ontwikkeling HTTP gebruiken. Gebruik passende no-store- en beveiligingsheaders; activeer HSTS alleen op de bedoelde HTTPS-hosts.
- Behandel robots.txt en noindex als crawlerinstructies, niet als toegangsbeveiliging. Blokkeer crawlers standaard voor besloten applicaties.
- Bij een gevonden geheim: verspreid het niet verder, verwijder het uit actuele voorbeelden en adviseer rotatie. Verwijderen uit een bestand wist Git-historie of oude uploadpakketten niet.

## Oplevering

- Documenteer installatie, lokaal testen, live configuratie, afhankelijkheden en verificatie in de project-README.
- Voer alleen noodzakelijke controles uit die bij de wijziging passen; vermijd tests die uitsluitend de implementatie herhalen.
- Voer destructieve of live databasehandelingen niet uit zonder passende toestemming. Een uploadpakket maken is geen toestemming om te deployen.
- Volg voor iedere gemaakte commit de volledige uploadafspraak uit AGENTS.md. Sluit updates, runtimebestanden en geheimen uit van Git.

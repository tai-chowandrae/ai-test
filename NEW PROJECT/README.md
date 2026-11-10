# Startpakket voor een nieuw project

Dit pakket bevat herbruikbare afspraken uit de projectafspraken, AGENTS.md, technische werkafspraken en aanvullende afspraken over beveiliging, lokaal/live configuratie en uploadpakketten. Het bevat geen klantgegevens, wachtwoorden, database-inhoud of functionaliteit van het oude project.

## Gebruik

1. Kopieer de inhoud van deze map naar de root van je nieuwe project. Voeg bestaande afspraken samen als daar al bestanden met dezelfde namen staan; overschrijf ze niet blind.
2. Vul de projectomschrijving en keuzes in `PROJECT-INSTELLINGEN.md` in. Laat onbekende keuzes open; de assistent mag veilige aannames doen.
3. Voeg `/updates/` toe aan de `.gitignore` van het nieuwe project. Sluit echte geheimen en lokale configuratiebestanden ook uit; commit alleen voorbeelden met placeholders.
4. Open het nieuwe project in je editor en plak de tekst onder 'Te plakken prompt' uit `STARTPROMPT.md` in de chat.
5. Beschrijf je eerste concrete opdracht in die prompt. De bestanden zelf bouwen nog geen applicatie en stellen geen hosting of beveiliging in.

## Bestanden

| Bestand | Doel |
| --- | --- |
| `AGENTS.md` | Instructies voor de assistent en de volledige afspraak voor uploadpakketten na commits. |
| `PROJECT-AFSPRAKEN.md` | Algemene werkwijze, structuur, frontend, routing, hosting en communicatie. |
| `PROJECT-INSTELLINGEN.md` | Invulbaar projectprofiel met technische en beveiligingsuitgangspunten. |
| `CONTROLELIJST.md` | Controles voor bouwen, beveiliging, configuratie en opleveren. |
| `STARTPROMPT.md` | Prompt om het nieuwe project te starten. |

De algemene afspraken zijn overgenomen. Conflicterende naamgevingsvoorkeuren uit het eerdere project zijn als expliciete projectkeuze uitgewerkt. Oude domeinen, lokale databasenamen, migratiebestanden, testresultaten en API-integraties zijn bewust niet meegenomen: die gelden niet automatisch voor een nieuw project.

Bewaar dit pakket als ontwikkeldocumentatie. Publiceer deze map of documenten niet onbedoeld via de website.

# Startprompt

Kopieer de tekst hieronder in de chat van het nieuwe project en vul de laatste onderdelen aan.

## Te plakken prompt

Lees eerst `AGENTS.md`, `PROJECT-AFSPRAKEN.md`, `PROJECT-INSTELLINGEN.md` en `CONTROLELIJST.md` in de projectroot en volg deze afspraken gedurende het hele project.

Bekijk daarna de bestaande mappenstructuur, Git-status en relevante code. Bewaar mijn bestaande wijzigingen. Werk proactief door tot mijn opdracht af is, maak gerichte wijzigingen en vermijd ongevraagde refactors. Stel alleen vragen als ontbrekende informatie een veilige of juiste uitvoering verhindert. Communiceer kort en praktisch in het Nederlands en geef bij langer werk korte voortgangsupdates.

Gebruik de technische keuzes uit het projectprofiel. Als er nog geen stack is afgesproken, ga uit van een praktische applicatie voor reguliere TransIP shared hosting: Apache, een ondersteunde PHP-versie, PDO met MySQL/MariaDB en gewone HTML/CSS/JavaScript. Houd stijlen en scripts apart, verdeel gebruikersstromen over logische pagina's en gebruik gedeelde helpers. Bouw geen marketingpagina tenzij ik daarom vraag.

Pas invoervalidatie, rechtencontrole en gegevensisolatie altijd op de server toe. Gebruik gebonden SQL-parameters, bescherm sessies en mutaties, scherm configuratie en back-ups af en toon geen technische foutdetails. Behandel frontendcontroles alleen als gebruiksgemak. Neem geen geheimen op in codevoorbeelden, documentatie, Git of uploadinstructies.

Zorg dat lokale en live instellingen apart bewaard blijven en dat duidelijk is hoe ik wissel. Gebruik daarvoor geen door bezoekers aangeleverde headers. Houd echte configuratie buiten Git, bied veilige voorbeeldbestanden en voorkom dat lokale tests de productiedatabase gebruiken.

Controleer het resultaat met passende syntaxcontroles, tests en waar nodig browserchecks. Noem eerlijk welke controles niet zijn uitgevoerd en waarom. Claim geen volledige beveiliging of werkende live omgeving op basis van alleen codecontrole.

Na iedere commit die je maakt: maak het uploadpakket in `updates/` exact volgens `AGENTS.md`, inclusief installatie-instructies, expliciete SQL-migratie-informatie, eventuele verwijderingen, controle van de zipinhoud en een downloadlink. Maak niet automatisch een commit of deployment alleen omdat deze afspraken bestaan; volg mijn concrete opdracht en de afgesproken workflow.

Project: [naam en korte omschrijving]
Gebruikers en belangrijkste doel: [invullen]
Eerste concrete opdracht: [beschrijf wat je nu wilt laten bouwen of aanpassen]
Aanvullende wensen of beperkingen: [optioneel]

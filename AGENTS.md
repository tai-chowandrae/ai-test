# Projectafspraken

Lees en volg `PROJECT-AFSPRAKEN.md` bij werkzaamheden in dit project.

## Uploadpakket na een commit

- Maak na iedere nieuwe commit die je in dit project aanmaakt een upload-zip met alle toegevoegde en gewijzigde bestanden uit die commit, met behoud van de mappenstructuur en verborgen bestanden zoals `.htaccess`.
- Gebruik als bestandsnaam `update-JJJJ-MM-DD_UU-mm-ss_commitnaam.zip`, op basis van de committerdatum en -tijd van de commit, omgerekend naar Europe/Amsterdam, gevolgd door de committitel. Maak de committitel bestandsveilig: kleine letters, spaties en ongeldige tekens vervangen door koppeltekens, opeenvolgende koppeltekens samenvoegen en koppeltekens aan begin en einde weglaten.
- Bewaar de zip in de map `updates/`; deze map is uitgesloten via `.gitignore`.
- Maak een instructiebestand met dezelfde basisnaam als de zip, gevolgd door `_INSTALLATIE.txt`. Bewaar dit naast de zip en voeg hetzelfde bestand aan de zip toe. Vermeld bovenaan de volledige zipnaam en commit-ID zodat de instructie ook los herkenbaar is. Geef concrete Nederlandstalige stappen: back-up, welke bestanden waar uploaden, vereisten, controles en eventuele handmatig te verwijderen bestanden. Vermeld expliciet of een SQL-migratie nodig is; zo ja, noem de exacte bestanden, uitvoervolgorde en het moment van uitvoeren. Onderscheid migraties van een volledig databaseschema en waarschuw dat een volledig schema niet op een bestaande database moet worden geimporteerd. Neem geen wachtwoorden of andere geheimen op.
- Controleer de inhoud van de zip en geef de gebruiker een downloadlink. Meld eventuele verwijderde bestanden apart, omdat een zip deze niet van de hosting verwijdert.

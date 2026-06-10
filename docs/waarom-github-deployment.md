# Onze vernieuwde deploymentaanpak — wat er is veranderd en waarom

Bij Happy Horizon hebben we onze deploymentpipeline vernieuwd. We zijn overgestapt van een Bitbucket-gebaseerde naar een GitHub-gebaseerde aanpak, gebouwd op Hypernode Deploy. Dit document legt uit wat er concreet is veranderd en welke voordelen dat oplevert voor jouw project.

---

## 1. Atomische releases — veiliger bij onverwachte situaties

Onze vorige aanpak werkte via directe rsync naar de live map op de server. Dit was snel en effectief, maar had één nadeel: als er iets onverwachts misging halverwege een deploy (een netwerkkieping, een fout in een script), kon de site tijdelijk in een inconsistente staat terechtkomen.

De nieuwe aanpak zet elke release volledig klaar in een tijdelijke map. Pas als alles succesvol is afgerond, wordt de live versie in één stap omgewisseld. Zo is de overgang altijd atomair — de site is volledig oud of volledig nieuw, nooit half.

---

## 2. Rollback zonder handmatig werk

Vorige releases worden bewaard op de server. Als er na een deploy toch een probleem opduikt, kan in seconden worden teruggeschakeld naar de vorige werkende versie — zonder handmatig bestanden te herstellen of database-aanpassingen terug te draaien.

---

## 3. Bouwen en deployen zijn gescheiden

Voorheen vonden het bouwproces (composer install, compileren, static content genereren) en de deploy in één aaneengesloten pipeline plaats. Dat werkte prima, maar betekende dat productie ook het bouwproces meemaakte.

Nu wordt de applicatie eenmalig gebouwd en opgeslagen als een kant-en-klaar pakket. Dat pakket kan meerdere keren worden ingezet — op staging ter validatie, en daarna op productie — zonder opnieuw te bouwen. Dit maakt deploys reproduceerbaar: staging en productie draaien exact dezelfde build.

---

## 4. Één centrale deploylogica voor alle projecten

Voorheen bevatte elk project zijn eigen kopie van de deploymentconfiguratie. Verbeteringen moesten per project worden doorgevoerd.

Nu staat de kernlogica (bouwen, deployen, CI, nginx-synchronisatie, cron) in één centrale gedeelde repository. Alle projecten maken hier gebruik van. Een verbetering of aanpassing in het deployproces geldt automatisch voor elk project dat de gedeelde toolkit gebruikt.

---

## 5. Nginx en crontab worden meegedeployed

Aanpassingen aan de webserverconfiguratie (nginx) of geplande taken (crontab) staan nu in de repository en worden automatisch meegenomen bij elke deploy. Hierdoor zijn ook infrastructurele wijzigingen traceerbaar en reproduceerbaar — net als codewijzigingen.

---

## 6. Codekwaliteit ingebouwd in het proces

Bij elke pull request en push worden automatisch statische codeanalyses uitgevoerd. Potentiële problemen worden gesignaleerd vóórdat ze de server bereiken.

---

## Overzicht

| | Vorige aanpak | Nieuwe aanpak |
|---|---|---|
| Deploystrategie | Directe rsync naar live map | Atomische releases met symlink-wissel |
| Onverwachte fout tijdens deploy | Tijdelijk inconsistente staat mogelijk | Live site onaangetast |
| Rollback | Handmatig | Automatisch, seconden |
| Bouwen vs. deployen | Samen in één stap | Gescheiden — build eenmalig, deploy wanneer klaar |
| Staging en productie | Aparte builds | Zelfde build, gevalideerd op staging |
| Deploylogica per project | Eigen kopie | Centraal gedeeld |
| Nginx / crontab beheer | Handmatig via SSH | Automatisch meegedeployed vanuit repository |
| Codekwaliteit | Optioneel | Ingebouwd in elke pull request |


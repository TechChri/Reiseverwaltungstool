# Wagner Reiseangebote

Theme-unabhängige Reiseverwaltung für Wagner Reisen. Das Plugin trennt die redaktionelle Darstellung auf der Wagner-Website von der externen Buchungsabwicklung bei Busfinder.

## Version 0.2.0

- eigener Post-Type `wagner_reise`
- Reisearten und Reiseziele als eigene Taxonomien
- Termin, Preis, Status und Dauer
- direkter Busfinder-Link und Busfinder-ID
- Leistungen, Zustiegsstellen und Bildergalerie
- strukturiertes Reiseprogramm mit beliebig vielen Tagesabschnitten
- responsive Reiseübersicht mit Filtern
- eigene Reisedetailseite
- strukturierte Daten vom Typ `TouristTrip` und `Offer`
- Gutenberg-/Block-Editor statt Elementor-Pflicht
- theme-unabhängige Templates und Styles
- Neuanlage und vollständige Pflege direkt in WordPress
- abgesicherter Einmalimport aus Buspilot

## Buspilot-Schnittstelle

Buspilot kann eine Reise unter `POST /wp-json/wagner-reisen/v1/importe/buspilot`
einmalig als WordPress-Entwurf übertragen. Die Anmeldung erfolgt über einen eigenen
WordPress-Benutzer mit Application Password und der Berechtigung, Beiträge zu
bearbeiten. Anschließend sind beide Reisen vollständig getrennt: Änderungen in
Buspilot überschreiben WordPress nicht und Änderungen in WordPress werden nicht an
Buspilot zurückgesendet. Ein erneuter Import derselben Buspilot-ID wird abgewiesen,
damit keine versehentlichen Dubletten entstehen. Die Neuanlage direkt im normalen
WordPress-Editor bleibt vollständig nutzbar.

## Geplanter nächster Schritt

Bestehende Traveler-Reisen werden über einen kontrollierten Migrationslauf in Entwürfe des neuen Post-Types kopiert. Traveler-Inhalte bleiben dabei unverändert, bis die neue Ausgabe vollständig geprüft wurde.


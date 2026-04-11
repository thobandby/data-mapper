Dieses ZIP enthält Testdateien für corrupted/edge-case Imports.

Enthalten:
- JSON: valide und mehrere invalide/problematische Varianten
- XML: valide und mehrere invalide Varianten
- CSV: valide und mehrere invalide Varianten
- CSV 6000 Zeilen:
  - csv_valid_6000_semicolon.csv
  - csv_invalid_6000_error_at_line_4987.csv

Hinweis zu csv_invalid_6000_error_at_line_4987.csv:
- Der erste Fehler tritt absichtlich in Zeile 4987 auf.
- Ursache: zusätzliches Semikolon im unquoted content, obwohl Semikolon das Trennzeichen ist.

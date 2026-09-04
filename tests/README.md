# Tests des exports

Pré-requis PHP : extensions `dom`, `mbstring` et `gd`.

```bash
php tests/export_regression.php
php tests/export_pdf_smoke.php
```

Le premier test vérifie les règles de calcul, la réconciliation, l'absence de migrations SQL dans les exports et la protection de chaque endpoint. Le second génère 13 PDF représentatifs (portrait, paysage, tableaux, PNG et SVG) dans le dossier temporaire du système et valide leur signature PDF.

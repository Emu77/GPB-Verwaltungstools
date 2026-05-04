<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="styles.css" />
  <title>Test Export Anwesenheiten zu MITIS</title>
</head>
<body>
<h1>Test Export Anwesenheiten zu MITIS</h1>
<form target="mitis" action="anwesenheit_fuer_mitis.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="MITIS_GUID" value="6B2AA285-C665-4967-AC6C-89CEB4D3F9A8" />
  <input type="hidden" name="action" value="anwesenheit" />
  Klasse-MITISID&nbsp;<input type="number" name="Klasse_ID" value="" style="width:50px;" /><br />
  Startdatum&nbsp;<input type="date" name="START_DATUM" value="<?= date('Y-m-d',strtotime('-1 week',strtotime('last monday'))) ?>" /><br />
  <input type="submit" value="Testen" />
</form>
</body>
</html>
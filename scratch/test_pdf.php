<?php
$id = 1;
$title = "MODELO DE PREDICCION DEL DESEMPENO";

$pdfString = "%PDF-1.4\n" .
"1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n" .
"2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n" .
"3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<</Font</F1 4 0 R>>>>/Contents 5 0 R>>endobj\n" .
"4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n" .
"5 0 obj<</Length 120>>stream\n" .
"BT\n" .
"/F1 16 Tf\n" .
"50 720 Td\n" .
"(CEC AR - DOCUMENTO DE TRABAJO DE GRADO #{$id}) Tj\n" .
"/F1 12 Tf\n" .
"0 -30 Td\n" .
"({$title}) Tj\n" .
"ET\n" .
"endstream\n" .
"endobj\n" .
"xref\n" .
"0 6\n" .
"0000000000 65535 f\n" .
"0000000010 00000 n\n" .
"0000000057 00000 n\n" .
"0000000112 00000 n\n" .
"0000000239 00000 n\n" .
"0000000306 00000 n\n" .
"trailer<</Size 6/Root 1 0 R>>\n" .
"startxref\n" .
"475\n" .
"%%EOF";

file_put_contents(__DIR__ . '/sample_test.pdf', $pdfString);
echo "Generated PDF size: " . strlen($pdfString) . " bytes\n";

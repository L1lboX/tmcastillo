<?php

namespace App\Traits;

use ZipArchive;

trait GeneratesExcel
{
    private function xlsx(array $headers, array $rows, string $sheetName = 'Datos', string $tableName = 'TablaDatos'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $sheetRows = array_merge([$headers], $rows === [] ? [array_fill(0, count($headers), '')] : $rows);
        [$sharedStrings, $sharedMap] = $this->sharedStrings($sheetRows);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/workbook.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="{$sheetName}" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStringsXml($sharedStrings, count($sheetRows) * count($headers)));
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows, $sharedMap));
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/tables/table1.xml', $this->tableXml($headers, count($rows), $tableName));
        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function worksheetXml(array $headers, array $rows, array $sharedMap): string
    {
        if ($rows === []) {
            $rows = [array_fill(0, count($headers), '')];
        }

        $sheetRows = array_merge([$headers], $rows);
        $xmlRows = [];
        $lastColumn = $this->columnName(count($headers));
        $lastRow = count($sheetRows);
        $range = 'A1:'.$lastColumn.$lastRow;

        foreach ($sheetRows as $index => $row) {
            $rowNumber = $index + 1;
            $cells = [];

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).$rowNumber;
                $sharedIndex = $sharedMap[(string) ($value ?? '')];
                $style = $rowNumber === 1 ? 1 : ($rowNumber % 2 === 0 ? 2 : 3);
                $cells[] = '<c r="'.$cell.'" s="'.$style.'" t="s"><v>'.$sharedIndex.'</v></c>';
            }

            $xmlRows[] = '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$range.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="14" customWidth="1"/><col min="3" max="8" width="20" customWidth="1"/><col min="9" max="14" width="18" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .'<tableParts count="1"><tablePart r:id="rId1"/></tableParts>'
            .'</worksheet>';
    }

    private function sharedStrings(array $rows): array
    {
        $strings = [];
        $map = [];

        foreach ($rows as $row) {
            foreach (array_values($row) as $value) {
                $value = (string) ($value ?? '');
                if (! array_key_exists($value, $map)) {
                    $map[$value] = count($strings);
                    $strings[] = $value;
                }
            }
        }

        return [$strings, $map];
    }

    private function sharedStringsXml(array $strings, int $totalCount): string
    {
        $items = array_map(
            fn (string $value) => '<si><t>'.htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></si>',
            $strings,
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$totalCount.'" uniqueCount="'.count($strings).'">'
            .implode('', $items)
            .'</sst>';
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2F5597"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFD9E2F3"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB7C9E2"/></left>
      <right style="thin"><color rgb="FFB7C9E2"/></right>
      <top style="thin"><color rgb="FFB7C9E2"/></top>
      <bottom style="thin"><color rgb="FFB7C9E2"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
  <dxfs count="0"/>
  <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
XML;
    }

    private function tableXml(array $headers, int $rowCount, string $tableName = 'TablaDatos'): string
    {
        $lastColumn = $this->columnName(count($headers));
        $lastRow = max(2, $rowCount + 1);
        $range = 'A1:'.$lastColumn.$lastRow;
        $columns = [];

        foreach ($headers as $index => $header) {
            $columns[] = '<tableColumn id="'.($index + 1).'" name="'.
                htmlspecialchars((string) $header, ENT_XML1 | ENT_COMPAT, 'UTF-8').'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="1" name="'.$tableName.'" displayName="'.$tableName.'" ref="'.$range.'" headerRowCount="1" totalsRowShown="0">'
            .'<autoFilter ref="'.$range.'"/>'
            .'<tableColumns count="'.count($headers).'">'.implode('', $columns).'</tableColumns>'
            .'<tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>'
            .'</table>';
    }

    private function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
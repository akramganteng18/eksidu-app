<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis berkas .xlsx minimal TANPA dependensi tambahan (hanya ext-zip bawaan PHP).
 * Sengaja dibuat sendiri karena paket maatwebsite/excel tidak terpasang di proyek ini.
 * Semua sel ditulis sebagai teks inline sehingga isi sel tidak pernah dievaluasi
 * sebagai rumus (aman dari formula injection).
 */
class XlsxWriter
{
    public static function tersedia(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @param  array<int,string>            $header
     * @param  array<int,array<int,mixed>>  $baris
     * @return string  path berkas sementara
     */
    public static function buat(array $header, array $baris, string $namaSheet = 'Laporan'): string
    {
        if (! self::tersedia()) {
            throw new RuntimeException('Ekstensi PHP zip tidak aktif.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'eksidu_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas XLSX sementara.');
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc(mb_substr($namaSheet, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        // Gaya 0 = normal, gaya 1 = header (tebal, latar abu-abu muda).
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');

        // Lebar kolom perkiraan dari isi terpanjang (maks 60 karakter).
        $lebar = [];
        foreach (array_merge([$header], $baris) as $r) {
            foreach (array_values($r) as $i => $v) {
                $lebar[$i] = max($lebar[$i] ?? 8, min(60, mb_strlen((string) $v) + 2));
            }
        }
        $cols = '';
        foreach ($lebar as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>';

        $xml .= self::baris(1, $header, 1);
        foreach ($baris as $n => $r) {
            $xml .= self::baris($n + 2, $r, 0);
        }
        $xml .= '</sheetData></worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();

        return $tmp;
    }

    private static function baris(int $nomor, array $sel, int $gaya): string
    {
        $out = '<row r="' . $nomor . '">';
        foreach (array_values($sel) as $i => $nilai) {
            $ref = self::kolom($i) . $nomor;
            $out .= '<c r="' . $ref . '" t="inlineStr" s="' . $gaya . '"><is><t xml:space="preserve">'
                . self::esc((string) $nilai) . '</t></is></c>';
        }

        return $out . '</row>';
    }

    /** 0 => A, 25 => Z, 26 => AA ... */
    private static function kolom(int $indeks): string
    {
        $huruf = '';
        for ($n = $indeks; $n >= 0; $n = intdiv($n, 26) - 1) {
            $huruf = chr(65 + $n % 26) . $huruf;
        }

        return $huruf;
    }

    private static function esc(string $teks): string
    {
        // Buang karakter kontrol yang tidak sah di XML 1.0.
        $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $teks) ?? '';

        return htmlspecialchars($teks, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

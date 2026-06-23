<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MembersTemplateController extends Controller
{
    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Members');

        // ── Column headers ──────────────────────────────────────────────
        $headers = [
            'A' => 'Parent Name',
            'B' => 'Parent Email',
            'C' => 'Parent WhatsApp',
            'D' => 'Child Name',
            'E' => 'Child Birth Date (DD/MM/YYYY)',
            'F' => 'Child Gender (male/female)',
            'G' => 'School',
            'H' => 'Jersey Name',
            'I' => 'Jersey Number',
            // Optional — active enrollment for existing members being migrated.
            // Fill ALL of J–O together, or leave them all blank.
            'J' => 'Location',
            'K' => 'Program',
            'L' => 'Day',
            'M' => 'Package',
            'N' => 'Remaining Sessions',
            'O' => 'Expiry Date (DD/MM/YYYY)',
            'P' => 'Start Date (DD/MM/YYYY)',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // ── Header style: navy background, white bold text ───────────────
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Tint the optional enrollment block (J–P) so it reads as a group.
        $sheet->getStyle('J1:P1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D5C8A']],
        ]);

        // ── Column widths ────────────────────────────────────────────────
        $widths = ['A'=>24,'B'=>28,'C'=>22,'D'=>24,'E'=>30,'F'=>26,'G'=>22,'H'=>18,'I'=>16,
                   'J'=>22,'K'=>20,'L'=>14,'M'=>22,'N'=>18,'O'=>26,'P'=>26];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Gender dropdown validation for column F ──────────────────────
        for ($i = 2; $i <= 300; $i++) {
            $validation = $sheet->getCell("F{$i}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(false);
            $validation->setFormula1('"male,female"');
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid');
            $validation->setError('Please enter male or female.');

            // Day dropdown for column L (optional — blank allowed)
            $dayVal = $sheet->getCell("L{$i}")->getDataValidation();
            $dayVal->setType(DataValidation::TYPE_LIST);
            $dayVal->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dayVal->setAllowBlank(true);
            $dayVal->setShowDropDown(false);
            $dayVal->setFormula1('"Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday"');
            $dayVal->setShowErrorMessage(true);
            $dayVal->setErrorTitle('Invalid');
            $dayVal->setError('Pick a day of the week (or leave blank).');
        }

        // ── Example rows ─────────────────────────────────────────────────
        $examples = [
            // Plain member (no enrollment) — J–P blank
            ['Ahmad Fauzi', 'ahmad.fauzi@email.com', '08123456789', 'Rizki Fauzi',  '15/03/2016', 'male',   'SD Negeri 1',  'Rizki', '7', '', '', '', '', '', '', ''],
            // Migrated active member — with package/program/location/sessions
            ['Dewi Susanti', 'dewi.susanti@email.com', '08987654321', 'Siti Susanti', '22/08/2017', 'female', 'SD Islam Al-Kautsar', 'Siti', '12',
             'Cikarang Court', 'Junior', 'Monday', 'Regular 8 Sessions', '5', '31/12/2026', '01/06/2026'],
        ];

        foreach ($examples as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $j => $val) {
                $sheet->setCellValue(chr(65 + $j) . $rowNum, $val);
            }
            // Light grey background for example rows
            $sheet->getStyle("A{$rowNum}:P{$rowNum}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            ]);
        }

        // ── Notes sheet ──────────────────────────────────────────────────
        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Instructions');
        $notesData = [
            ['INSTRUCTIONS FOR IMPORT'],
            [''],
            ['Column',         'Required', 'Format / Notes'],
            ['Parent Name',    'Yes',      'Full name of the parent/guardian'],
            ['Parent Email',   'Yes',      'Unique email — used as login. If email already exists, the child will be added to that parent.'],
            ['Parent WhatsApp','No',       'e.g. 08123456789 or 628123456789'],
            ['Child Name',     'Yes',      'Full name of the child'],
            ['Child Birth Date','Yes',     'Format: DD/MM/YYYY  e.g. 15/03/2016'],
            ['Child Gender',   'Yes',      'male  or  female'],
            ['School',         'No',       'School name (optional)'],
            ['Jersey Name',    'No',       'Name printed on jersey (optional)'],
            ['Jersey Number',  'No',       'Jersey number (optional)'],
            [''],
            ['ACTIVE ENROLLMENT (optional — for migrating existing members)'],
            ['Fill ALL of these together, or leave them ALL blank.'],
            ['Location',          'If enrolling', 'Must match an existing location name exactly (e.g. Cikarang Court).'],
            ['Program',           'If enrolling', 'Must match an existing program name (e.g. Junior).'],
            ['Day',               'If enrolling', 'Day of the weekly class: Monday..Sunday (Senin..Minggu also accepted).'],
            ['Package',           'If enrolling', 'Must match a package name that belongs to the Location above.'],
            ['Remaining Sessions','If enrolling', 'How many sessions the member still has left (number).'],
            ['Expiry Date',       'If enrolling', 'When the package expires. Format: DD/MM/YYYY.'],
            ['Start Date',        'No',           'When the member originally started. Format: DD/MM/YYYY. Optional.'],
            [''],
            ['TIPS'],
            ['- Delete the two grey example rows before importing.'],
            ['- Do not modify or rename the column headers.'],
            ['- One row = one child. If a parent has two children, add two rows with the same parent email.'],
            ['- Leave J–P blank for brand-new members who have not paid for a package yet.'],
            ['- Location + Program + Day must point to a real class schedule, otherwise that row is skipped with an error.'],
            ['- Migrated enrollments do NOT create a payment record (no effect on revenue reports).'],
            ['- If the parent email matches their Google account, they can log in with Google straight away — no approval needed.'],
        ];

        foreach ($notesData as $r => $row) {
            foreach ($row as $c => $val) {
                $notes->setCellValue(chr(65 + $c) . ($r + 1), $val);
            }
        }

        $notes->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1E3A5F']],
        ]);
        $notes->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EEF6']],
        ]);
        $notes->getColumnDimension('A')->setWidth(22);
        $notes->getColumnDimension('B')->setWidth(12);
        $notes->getColumnDimension('C')->setWidth(70);
        $notes->getStyle('C')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        // ── Stream response ───────────────────────────────────────────────
        $filename = 'members-import-template.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

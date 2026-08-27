<?php

namespace App\Services;

use App\Models\Trabajo;
use App\Models\Profesor;
use App\Models\Area;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteExcelService
{
    /**
     * Genera el libro Excel de reporte general con 3 hojas:
     * 1. Semestre 1 (Ene - Jun)
     * 2. Semestre 2 (Jul - Dic)
     * 3. Reporte de Evaluadores
     *
     * @param int $year
     * @return string Ruta temporal del archivo .xlsx generado
     */
    public function generarReporte(int $year): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Eliminar hoja por defecto

        // Obtener trabajos del año seleccionado
        $trabajosYear = Trabajo::with([
            'tipo',
            'estudiante.area.facultad',
            'directores',
            'evaluadores.usuario',
            'evaluadores.area.facultad',
            'evaluaciones'
        ])
        ->whereYear('fecha_subida', $year)
        ->get();

        // Separar por semestre
        $trabajosSemestre1 = $trabajosYear->filter(function ($t) {
            $mes = Carbon::parse($t->fecha_subida)->month;
            return $mes >= 1 && $mes <= 6;
        });

        $trabajosSemestre2 = $trabajosYear->filter(function ($t) {
            $mes = Carbon::parse($t->fecha_subida)->month;
            return $mes >= 7 && $mes <= 12;
        });

        // HOJA 1: Semestre 1
        $this->construirHojaTrabajos($spreadsheet, "Semestre 1 (Ene-Jun)", $trabajosSemestre1, $year, "Primer Semestre (Enero - Junio)");

        // HOJA 2: Semestre 2
        $this->construirHojaTrabajos($spreadsheet, "Semestre 2 (Jul-Dic)", $trabajosSemestre2, $year, "Segundo Semestre (Julio - Diciembre)");

        // HOJA 3: Reporte de Evaluadores
        $this->construirHojaEvaluadores($spreadsheet, "Reporte de Evaluadores", $year);

        // Activar la primera hoja
        $spreadsheet->setActiveSheetIndex(0);

        // Guardar archivo en carpeta temporal de scratch
        $tempPath = storage_path("app/public/Reporte_General_{$year}_" . time() . ".xlsx");
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Construye una hoja con la tabla detallada de Trabajos de Grado.
     */
    private function construirHojaTrabajos(Spreadsheet $spreadsheet, string $sheetTitle, $trabajos, int $year, string $semestreNombre): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetTitle);
        $sheet->setShowGridlines(true);

        // Encabezado institucional del reporte
        $sheet->setCellValue('A1', 'SISTEMA DE GESTIÓN DE TRABAJOS DE GRADO - CECAR');
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('07321E');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "Reporte de Trabajos de Grado - {$semestreNombre} {$year}");
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('07321E'));
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F0EA');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Títulos de columnas
        $headers = [
            'A4' => 'Código',
            'B4' => 'Título del Proyecto',
            'C4' => 'Tipo / Modalidad',
            'D4' => 'Facultad',
            'E4' => 'Área',
            'F4' => 'Estudiante(s)',
            'G4' => 'Correo(s) Estudiante',
            'H4' => 'Director / Subdirector',
            'I4' => 'Evaluadores Asignados',
            'J4' => 'Estado del Proceso',
            'K4' => '¿Sustentó?',
            'L4' => 'Fecha de Subida',
            'M4' => 'Fecha Calificación / Acta',
            'N4' => 'Dictámenes / Calificaciones',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Estilo cabecera de tabla
        $headerRange = 'A4:N4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('07321E');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Llenar datos
        $row = 5;
        foreach ($trabajos as $t) {
            // Estudiantes
            $estudiantesNombres = $t->estudiante->map(fn($e) => $e->nombre . ' ' . $e->apellido)->implode("\n");
            $estudiantesCorreos = $t->estudiante->map(fn($e) => $e->correo ?? 'N/A')->implode("\n");

            // Directores
            $directoresText = $t->directores->map(fn($d) => $d->nombre . ' ' . $d->apellido . ' (' . ucfirst($d->pivot->rol ?? 'director') . ')')->implode("\n");

            // Evaluadores
            $evaluadoresText = $t->evaluadores->map(fn($ev) => optional($ev->usuario)->nombre . ' ' . optional($ev->usuario)->apellido)->implode("\n");

            // Facultad & Área
            $primerEst = $t->estudiante->first();
            $nombreArea = optional(optional($primerEst)->area)->nombre_area ?? 'N/A';
            $nombreFacultad = optional(optional(optional($primerEst)->area)->facultad)->nombre_facultad ?? 'N/A';

            // Estado del Proceso (Solo 3 valores: Finalizado, En Revisión, Sin Calificar)
            $esFinalizado = ($t->estado === 'finalizado' || !empty($t->archivo_acta_sustentacion));
            $esEnRevision = ($t->estado === 'en_revision' || $t->evaluadores->contains(fn($e) => !empty($e->pivot->requiere_nueva_revision)));

            if ($esFinalizado) {
                $estadoTexto = 'Finalizado';
            } elseif ($esEnRevision) {
                $estadoTexto = 'En Revisión';
            } else {
                $estadoTexto = 'Sin Calificar';
            }

            // ¿Sustentó? (Sí si tiene acta subida, No si no la tiene)
            $sustentoTexto = !empty($t->archivo_acta_sustentacion) ? 'Sí' : 'No';

            // Dictámenes
            $dictamenes = $t->evaluaciones->map(function($ev) {
                $profNombre = optional(optional($ev->profesor)->usuario)->nombre ?? 'Evaluador';
                $res = str_replace('_', ' ', ucfirst($ev->resultado ?? 'Pendiente'));
                return "{$profNombre}: {$res}";
            })->implode("\n");

            // Fecha última calificación / acta
            $fechaCalificacion = 'N/A';
            if (!empty($t->archivo_acta_sustentacion)) {
                $fechaCalificacion = $t->updated_at ? Carbon::parse($t->updated_at)->format('Y-m-d') : 'Acta Adjunta';
            } elseif ($t->evaluaciones->isNotEmpty()) {
                $lastEval = $t->evaluaciones->sortByDesc('updated_at')->first();
                if ($lastEval && $lastEval->updated_at) {
                    $fechaCalificacion = Carbon::parse($lastEval->updated_at)->format('Y-m-d');
                }
            }

            $sheet->setCellValue("A{$row}", $t->codigo_proyecto ?? ('Propuesta #' . $t->id_trabajo));
            $sheet->setCellValue("B{$row}", $t->titulo);
            $sheet->setCellValue("C{$row}", optional($t->tipo)->nombre_tipo ?? 'Sin tipo');
            $sheet->setCellValue("D{$row}", $nombreFacultad);
            $sheet->setCellValue("E{$row}", $nombreArea);
            $sheet->setCellValue("F{$row}", $estudiantesNombres ?: 'N/A');
            $sheet->setCellValue("G{$row}", $estudiantesCorreos ?: 'N/A');
            $sheet->setCellValue("H{$row}", $directoresText ?: 'Sin Director');
            $sheet->setCellValue("I{$row}", $evaluadoresText ?: 'Sin Evaluadores');
            $sheet->setCellValue("J{$row}", $estadoTexto);
            $sheet->setCellValue("K{$row}", $sustentoTexto);
            $sheet->setCellValue("L{$row}", Carbon::parse($t->fecha_subida)->format('Y-m-d'));
            $sheet->setCellValue("M{$row}", $fechaCalificacion);
            $sheet->setCellValue("N{$row}", $dictamenes ?: 'Sin Evaluaciones');

            // Habilitar ajuste de texto multilínea
            $sheet->getStyle("A{$row}:N{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            
            // Alineación centrada para Estado y ¿Sustentó?
            $sheet->getStyle("J{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Cebra sutil
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if ($row === 5) {
            $sheet->setCellValue("A5", "No se encontraron trabajos de grado registrados en este semestre.");
            $sheet->mergeCells("A5:N5");
            $sheet->getStyle("A5")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            $sheet->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Bordes de la tabla
        $lastRow = max(5, $row - 1);
        $sheet->getStyle("A4:N{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        // Autoajustar ancho de columnas
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Construye la Hoja 3 con el reporte de Carga de Evaluadores por Área.
     */
    private function construirHojaEvaluadores(Spreadsheet $spreadsheet, string $sheetTitle, int $year): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetTitle);
        $sheet->setShowGridlines(true);

        // Encabezado institucional
        $sheet->setCellValue('A1', 'SISTEMA DE GESTIÓN DE TRABAJOS DE GRADO - CECAR');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('07321E');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "Reporte General de Docentes Evaluadores y Carga Académica - Año {$year}");
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('07321E'));
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F0EA');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Títulos de columnas
        $headers = [
            'A4' => 'Área de Conocimiento',
            'B4' => 'Facultad',
            'C4' => 'Docente Evaluador',
            'D4' => 'Correo Electrónico',
            'E4' => 'Trabajos Asignados (Carga)',
            'F4' => 'Evaluaciones Completadas',
            'G4' => 'Proyectos Asignados (Códigos)',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerRange = 'A4:G4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('07321E');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Obtener todos los profesores evaluadores ordenados por área y cantidad de trabajos
        $evaluadores = Profesor::whereHas('usuario', fn($q) => $q->where('rol', 'Evaluador'))
            ->with(['usuario', 'area.facultad', 'trabajos.evaluaciones'])
            ->get()
            ->sortByDesc(fn($p) => $p->trabajos->count());

        $row = 5;
        foreach ($evaluadores as $profesor) {
            $user = $profesor->usuario;
            $nombreDocente = $user ? ($user->nombre . ' ' . $user->apellido) : 'Docente Sin Nombre';
            $correoDocente = $user ? $user->correo : 'N/A';
            $nombreArea = optional($profesor->area)->nombre_area ?? 'Sin Área';
            $nombreFacultad = optional(optional($profesor->area)->facultad)->nombre_facultad ?? 'N/A';

            $trabajosCount = $profesor->trabajos ? $profesor->trabajos->count() : 0;
            $evalsCompletadas = $profesor->trabajos ? $profesor->trabajos->filter(function($t) use ($profesor) {
                return $t->evaluaciones->where('id_profesor', $profesor->id_profesor)->where('evaluacion_completada', true)->count() > 0;
            })->count() : 0;

            $codigosTrabajos = $profesor->trabajos ? $profesor->trabajos->map(fn($t) => $t->codigo_proyecto ?? ('Propuesta #' . $t->id_trabajo))->implode(", ") : 'Ninguno';

            $sheet->setCellValue("A{$row}", $nombreArea);
            $sheet->setCellValue("B{$row}", $nombreFacultad);
            $sheet->setCellValue("C{$row}", $nombreDocente);
            $sheet->setCellValue("D{$row}", $correoDocente);
            $sheet->setCellValue("E{$row}", "{$trabajosCount} / 3 asignados");
            $sheet->setCellValue("F{$row}", "{$evalsCompletadas} finalizadas");
            $sheet->setCellValue("G{$row}", $codigosTrabajos ?: 'Sin asignaciones');

            $sheet->getStyle("A{$row}:G{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

            // Resaltar evaluadores con carga completa (3 asignados)
            if ($trabajosCount >= 3) {
                $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B91C1C'));
            }

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if ($row === 5) {
            $sheet->setCellValue("A5", "No hay evaluadores registrados en el sistema.");
            $sheet->mergeCells("A5:G5");
            $sheet->getStyle("A5")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            $sheet->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $lastRow = max(5, $row - 1);
        $sheet->getStyle("A4:G{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

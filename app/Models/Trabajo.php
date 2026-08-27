<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\TipoTrabajo;
use App\Models\Rubrica;


/** Representa un trabajo de grado, con su archivo PDF, estado y asignaciones. */
class Trabajo extends Model
{
    use HasFactory;

    protected $table = 'trabajo';
    protected $primaryKey = 'id_trabajo';

    protected $fillable = [
        'codigo_proyecto',
        'titulo',
        'fecha_subida',
        'id_tipo',
        'plantilla_rubrica',
        'archivo_pdf',
        'archivo_acta',
        'archivo_acta_sustentacion',
        'estado',
        'retirado',
    ];

    protected $casts = [
        'retirado' => 'boolean',
    ];

    public $timestamps = true;

    protected static function booted()
    {
        static::creating(function ($trabajo) {
            if (empty($trabajo->codigo_proyecto)) {
                $trabajo->codigo_proyecto = static::generarCodigoProyecto();
            }
        });

        static::deleting(function ($trabajo) {
            // Eliminar directores asociados si solo están asociados a este trabajo
            $directores = $trabajo->directores()->get();
            foreach ($directores as $director) {
                if ($director->trabajos()->count() <= 1) {
                    $director->delete();
                }
            }

            if ($trabajo->archivo_pdf) {
                $relative = preg_replace('#^storage/#', '', $trabajo->archivo_pdf);
                if (Storage::disk('public')->exists($relative)) {
                    Storage::disk('public')->delete($relative);
                }
            }
            if ($trabajo->archivo_acta) {
                $relativeActa = preg_replace('#^storage/#', '', $trabajo->archivo_acta);
                if (Storage::disk('public')->exists($relativeActa)) {
                    Storage::disk('public')->delete($relativeActa);
                }
            }
            if ($trabajo->archivo_acta_sustentacion) {
                $relativeActaSus = preg_replace('#^storage/#', '', $trabajo->archivo_acta_sustentacion);
                if (Storage::disk('public')->exists($relativeActaSus)) {
                    Storage::disk('public')->delete($relativeActaSus);
                }
            }
        });
    }

    /**
     * Genera un código autoincrementable único con formato PGTG-XXX-YY (ej. PGTG-001-26)
     */
    public static function generarCodigoProyecto(?Carbon $fecha = null): string
    {
        $fechaRef = $fecha ?? now();
        $yy = $fechaRef->format('y'); // p. ej. 26 para 2026, 27 para 2027

        // Buscar el correlativo más alto para el año YY
        // Se buscan códigos que coincidan con 'PGTG-%-YY'
        $patron = "PGTG-%-" . $yy;
        $ultimosCodigos = static::where('codigo_proyecto', 'LIKE', $patron)
            ->pluck('codigo_proyecto');

        $maxNum = 0;
        foreach ($ultimosCodigos as $cod) {
            // Ejemplo: PGTG-005-26 -> partes [PGTG, 005, 26]
            $partes = explode('-', $cod);
            if (count($partes) === 3 && is_numeric($partes[1])) {
                $num = (int)$partes[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $siguiente = $maxNum + 1;
        return sprintf("PGTG-%03d-%s", $siguiente, $yy);
    }

    /**
     * Indica si el trabajo de grado tiene cargada el Acta de inicio/aprobación.
     */
    public function tieneActa(): bool
    {
        return !empty($this->archivo_acta);
    }

    /**
     * Indica si el trabajo de grado tiene cargada el Acta de Sustentación
     * (el proceso del proyecto finaliza cuando esta acta es subida).
     */
    public function tieneActaSustentacion(): bool
    {
        return !empty($this->archivo_acta_sustentacion);
    }

    // Relación con tipo de trabajo
    public function tipo()
    {
        return $this->belongsTo(TipoTrabajo::class, 'id_tipo', 'id_tipo');
    }
    // Relación con estudiantes
    public function estudiante()
    {
        return $this->hasMany(Estudiante::class, 'id_trabajo');
    }
    public function evaluadores()
    {
    // 1. Modelo relacionado: Usuario::class
    // 2. Tabla pivote: 'trabajo_profesor'
    // 3. FK de la tabla local en el pivote: 'id_trabajo' (PK del Trabajo)
    // 4. FK del modelo relacionado en el pivote: 'id_profesor' (PK del Usuario es 'id_usuario')
    return $this->belongsToMany(\App\Models\Profesor::class, 'trabajo_profesor', 'id_trabajo', 'id_profesor')
                 // MUY IMPORTANTE: Los nombres de columna deben coincidir exactamente con la base de datos.
                 ->withPivot('fecha_asignacion', 'fecha_limite_revision', 'estado_revision', 'decision_evaluador', 'motivo_rechazo', 'terminos_aceptados', 'datos_aceptados', 'requiere_nueva_revision');
    }
    public function rubricas()
    {
    return $this->belongsToMany(Rubrica::class, 'trabajo_rubrica', 'id_trabajo', 'id_rubrica')
                ->withPivot('fecha_asignacion');    
    }   
    public function rubricaAsignada()
    {
    return $this->hasOne(TrabajoRubrica::class, 'id_trabajo');
    }

    public function retroalimentaciones()
    {
        return $this->hasMany(Retroalimentacion::class, 'trabajo_grado_id', 'id_trabajo');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'trabajo_grado_id', 'id_trabajo');
    }

    public function directores()
    {
        return $this->belongsToMany(Director::class, 'director_trabajo', 'id_trabajo', 'id_director');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'id_trabajo');
    }

    /**
     * Calcula la nota promedio basada en las evaluaciones independientes de los profesores.
     */
    public function getNotaPromedioAttribute()
    {
        $notas = $this->evaluaciones()->whereNotNull('nota_final')->pluck('nota_final');
        if ($notas->isEmpty()) {
            return null;
        }
        return round($notas->avg(), 2);
    }
}


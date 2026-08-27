<?php

namespace Tests\Feature\Gestor;

use App\Models\Trabajo;
use App\Models\Usuario;
use App\Models\Profesor;
use App\Models\Estudiante;
use App\Models\Director;
use App\Models\Evaluacion;
use App\Models\HistorialEstado;
use App\Notifications\PropuestaEvaluada;
use App\Notifications\NuevaVersionDisponible;
use App\Notifications\TrabajoRechazado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Prueba el flujo completo de una propuesta de grado rechazada por los evaluadores:
 * evaluación con resultado 'rechazada' → bloqueo del informe final → decisión del
 * gestor (continuar con nueva propuesta o finalizar como Rechazada) → eliminación.
 * También cubre el rechazo de un evaluador a la asignación.
 */
class PropuestaRechazadaTest extends TestCase
{
    private Usuario $gestor;
    private Usuario $admin;
    private Usuario $evaluador1;
    private Usuario $evaluador2;
    private Profesor $profesor1;
    private Profesor $profesor2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDefaultTables();
        $this->seedUsuarios();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Esquema de prueba (SQLite :memory:) equivalente al esquema real del proyecto
    // ─────────────────────────────────────────────────────────────────────────────
    private function createDefaultTables(): void
    {
        Schema::create('usuario', function ($table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 150)->unique();
            $table->string('password');
            $table->string('rol');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('id_facultad')->nullable();
            $table->timestamps();
        });

        Schema::create('profesor', function ($table) {
            $table->id('id_profesor');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_area')->nullable();
            $table->boolean('terminos_aceptados')->default(false);
            $table->boolean('datos_aceptados')->default(false);
            $table->timestamps();
        });

        Schema::create('tipo_trabajo', function ($table) {
            $table->id('id_tipo');
            $table->string('nombre_tipo', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('trabajo', function ($table) {
            $table->id('id_trabajo');
            $table->string('codigo_proyecto', 50)->nullable();
            $table->string('titulo', 200);
            $table->timestamp('fecha_subida')->nullable();
            $table->unsignedBigInteger('id_tipo')->nullable();
            $table->string('plantilla_rubrica', 50)->nullable();
            $table->string('archivo_pdf', 255)->nullable();
            $table->string('archivo_acta', 255)->nullable();
            $table->string('estado', 50)->nullable();
            $table->boolean('retirado')->default(false);
            $table->timestamps();
        });

        Schema::create('trabajo_profesor', function ($table) {
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_profesor');
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamp('fecha_limite_revision')->nullable();
            $table->string('estado_revision', 50)->nullable();
            $table->boolean('retroalimentacion_finalizada')->default(false);
            $table->string('decision_evaluador', 20)->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->boolean('terminos_aceptados')->default(false);
            $table->boolean('datos_aceptados')->default(false);
            $table->timestamps();
            $table->primary(['id_trabajo', 'id_profesor']);
        });

        Schema::create('evaluaciones', function ($table) {
            $table->id();
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_profesor')->nullable();
            $table->string('tipo_plantilla', 50);
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->string('resultado', 50)->nullable();
            $table->text('observaciones_globales')->nullable();
            $table->json('criterios')->nullable();
            $table->text('firma')->nullable();
            $table->string('celular', 20)->nullable();
            $table->text('firma_evaluador_2')->nullable();
            $table->string('celular_evaluador_2', 20)->nullable();
            $table->boolean('evaluacion_completada')->default(false);
            $table->timestamps();
        });

        Schema::create('historial_estados', function ($table) {
            $table->id();
            $table->unsignedBigInteger('trabajo_grado_id');
            $table->string('estado');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('observacion_estado')->nullable();
            $table->timestamps();
        });

        Schema::create('retroalimentaciones', function ($table) {
            $table->id();
            $table->unsignedBigInteger('trabajo_grado_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('estudiante', function ($table) {
            $table->id('id_estudiante');
            $table->unsignedBigInteger('id_trabajo')->nullable();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 150)->nullable();
            $table->unsignedBigInteger('id_area')->nullable();
            $table->string('motivo_eliminacion', 255)->nullable();
        });

        Schema::create('trabajo_estudiante', function ($table) {
            $table->id();
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_estudiante');
            $table->timestamps();
        });

        Schema::create('rubrica', function ($table) {
            $table->id('id_rubrica');
            $table->unsignedBigInteger('id_tipo')->nullable();
            $table->string('archivo', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('activo')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
        });

        Schema::create('trabajo_rubrica', function ($table) {
            $table->id('id_trabajo_rubrica');
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_rubrica');
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamps();
        });

        Schema::create('directors', function ($table) {
            $table->id('id_director');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo_electronico', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('director_trabajo', function ($table) {
            $table->id();
            $table->unsignedBigInteger('id_director');
            $table->unsignedBigInteger('id_trabajo');
            $table->string('rol')->nullable();
            $table->timestamps();
        });

        Schema::create('alerta', function ($table) {
            $table->id('id_alerta');
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_profesor');
            $table->timestamp('fecha_envio')->nullable();
            $table->string('tipo_alerta', 100)->nullable();
            $table->boolean('leido')->nullable();
        });

        Schema::create('seguimiento', function ($table) {
            $table->id('id_seguimiento');
            $table->unsignedBigInteger('id_trabajo');
            $table->unsignedBigInteger('id_admin')->nullable();
            $table->string('estado_visualizacion', 50)->nullable();
            $table->timestamp('fecha_revision')->nullable();
        });

        Schema::create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Datos base
    // ─────────────────────────────────────────────────────────────────────────────
    private function seedUsuarios(): void
    {
        $this->gestor = Usuario::create([
            'nombre' => 'Gestor', 'apellido' => 'Test',
            'correo' => 'gestor@test.com', 'password' => bcrypt('pass'),
            'rol' => 'Gestor', 'activo' => true,
        ]);

        $this->admin = Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'Test',
            'correo' => 'admin@test.com', 'password' => bcrypt('pass'),
            'rol' => 'Administrador', 'activo' => true,
        ]);

        $this->evaluador1 = Usuario::create([
            'nombre' => 'Evaluador', 'apellido' => 'Uno',
            'correo' => 'evaluador1@test.com', 'password' => bcrypt('pass'),
            'rol' => 'Evaluador', 'activo' => true,
        ]);
        $this->profesor1 = Profesor::create(['id_usuario' => $this->evaluador1->id_usuario]);

        $this->evaluador2 = Usuario::create([
            'nombre' => 'Evaluador', 'apellido' => 'Dos',
            'correo' => 'evaluador2@test.com', 'password' => bcrypt('pass'),
            'rol' => 'Evaluador', 'activo' => true,
        ]);
        $this->profesor2 = Profesor::create(['id_usuario' => $this->evaluador2->id_usuario]);
    }

    /**
     * Crea una propuesta de grado en revisión con ambos evaluadores asignados y
     * habilitados para evaluar (aceptado + términos aceptados).
     */
    private function crearPropuestaEnRevision(): Trabajo
    {
        $trabajo = Trabajo::create([
            'titulo' => 'Propuesta de Investigación Demo',
            'fecha_subida' => now(),
            'plantilla_rubrica' => 'propuesta_de_grado',
            'archivo_pdf' => 'storage/pdf/propuesta-demo.pdf',
            'estado' => 'en_revision',
        ]);

        $trabajo->evaluadores()->attach($this->profesor1->id_profesor, [
            'fecha_asignacion' => now()->subMinutes(10),
            'fecha_limite_revision' => now()->addWeekdays(15),
            'estado_revision' => 'Asignado',
            'decision_evaluador' => 'aceptado',
            'motivo_rechazo' => null,
            'terminos_aceptados' => true,
            'datos_aceptados' => true,
        ]);
        $trabajo->evaluadores()->attach($this->profesor2->id_profesor, [
            'fecha_asignacion' => now()->subMinutes(5),
            'fecha_limite_revision' => now()->addWeekdays(15),
            'estado_revision' => 'Asignado',
            'decision_evaluador' => 'aceptado',
            'motivo_rechazo' => null,
            'terminos_aceptados' => true,
            'datos_aceptados' => true,
        ]);

        return $trabajo;
    }

    private function payloadEvaluacionRechazada(): array
    {
        return [
            'tipo_plantilla' => 'propuesta_de_grado',
            'nota_final' => 2.5,
            'resultado' => 'rechazada',
            'observaciones_globales' => 'La propuesta no cumple los requisitos mínimos de la rúbrica.',
            'criterios' => [
                ['descripcion' => 'Claridad del planteamiento', 'calificacion' => 2.0, 'comentario' => 'Debe mejorar', 'valoracion' => 'deficiente'],
                ['descripcion' => 'Pertinencia', 'calificacion' => 3.0, 'comentario' => 'Aceptable', 'valoracion' => 'aceptable'],
            ],
            'firma' => 'data:image/png;base64,' . str_repeat('A', 300),
        ];
    }

    /**
     * Ambos evaluadores guardan su evaluación con resultado 'rechazada'.
     * Asume que Notification está fakeado por el test que lo invoca.
     */
    private function finalizarEvaluacionRechazada(Trabajo $trabajo): void
    {
        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-evaluacion", $this->payloadEvaluacionRechazada())
            ->assertJson(['success' => true]);

        // La notificación solo debe dispararse cuando AMBOS evaluadores finalizan
        Notification::assertNotSentTo($this->gestor, PropuestaEvaluada::class);

        $this->actingAs($this->evaluador2)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-evaluacion", $this->payloadEvaluacionRechazada())
            ->assertJson(['success' => true, 'evaluacion_completada' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1. Evaluación con resultado 'rechazada'
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_propuesta_evaluada_como_rechazada_por_ambos_evaluadores(): void
    {
        Notification::fake();
        $trabajo = $this->crearPropuestaEnRevision();

        $this->finalizarEvaluacionRechazada($trabajo);

        // La evaluación compartida queda con resultado rechazada y completada
        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertNotNull($evaluacion);
        $this->assertSame('rechazada', $evaluacion->resultado);
        $this->assertTrue($evaluacion->evaluacion_completada);
        $this->assertNotEmpty($evaluacion->firma);
        $this->assertNotEmpty($evaluacion->firma_evaluador_2);

        // Ambos evaluadores quedan con estado_revision Finalizado
        $this->assertSame('Finalizado', $this->pivotEstado($trabajo, $this->profesor1));
        $this->assertSame('Finalizado', $this->pivotEstado($trabajo, $this->profesor2));

        // Se notifica a los gestores activos con el mensaje específico de rechazo
        Notification::assertSentTo($this->gestor, PropuestaEvaluada::class, function ($notification) {
            $data = $notification->toDatabase($this->gestor);

            return str_contains($data['mensaje'], 'fue rechazada por los evaluadores')
                && $data['tipo'] === 'propuesta_evaluada';
        });

        // Queda registrado en el historial
        $this->assertDatabaseHas('historial_estados', [
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'evaluacion_completada',
        ]);

        // Comportamiento actual documentado: el trabajo NO se auto-marca como
        // 'rechazada'; depende de la decisión del gestor en decidirPropuesta().
        $this->assertSame('en_revision', $trabajo->fresh()->estado);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2. Bloqueo del informe final
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_subir_informe_final_bloqueado_si_propuesta_rechazada(): void
    {
        Notification::fake();
        $trabajo = $this->crearPropuestaEnRevision();
        $this->finalizarEvaluacionRechazada($trabajo);

        // GET del formulario → redirige con error
        $this->actingAs($this->gestor)
            ->get(route('gestor.trabajo.informe-final', $trabajo->id_trabajo))
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('error', 'No es posible subir el informe final: la propuesta fue rechazada.');

        // POST del informe final → redirige con error
        $this->actingAs($this->gestor)
            ->post(route('gestor.trabajo.informe-final.store', $trabajo->id_trabajo), [
                'archivo_pdf' => UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('error', 'No es posible subir el informe final: la propuesta fue rechazada.');

        // El trabajo sigue siendo propuesta (no se convirtió a trabajo de grado)
        $this->assertSame('propuesta_de_grado', $trabajo->fresh()->plantilla_rubrica);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3. Decisión del gestor: NO → propuesta marcada como Rechazada
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_decidir_propuesta_no_finaliza_como_rechazada(): void
    {
        Notification::fake();
        $trabajo = $this->crearPropuestaEnRevision();
        $this->finalizarEvaluacionRechazada($trabajo);

        $this->actingAs($this->gestor)
            ->post(route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo), ['decision' => 'no'])
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('success', 'Proceso finalizado. La propuesta ha quedado marcada como Rechazada.');

        $this->assertSame('rechazada', $trabajo->fresh()->estado);

        $this->assertDatabaseHas('historial_estados', [
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'propuesta_rechazada',
        ]);

        // Un segundo intento debe rechazarse porque el proceso ya finalizó
        $this->actingAs($this->gestor)
            ->post(route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo), ['decision' => 'no'])
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('error', 'El proceso de esta propuesta ya fue finalizado.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 4. Decisión del gestor: SÍ → nueva propuesta y ciclo reiniciado
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_decidir_propuesta_si_reinicia_ciclo_de_evaluacion(): void
    {
        Notification::fake();
        Storage::fake('public');
        $trabajo = $this->crearPropuestaEnRevision();
        $this->finalizarEvaluacionRechazada($trabajo);

        $this->actingAs($this->gestor)
            ->post(route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo), [
                'decision' => 'si',
                'archivo_pdf' => UploadedFile::fake()->create('nueva-propuesta.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('success', 'Nueva propuesta subida. Los evaluadores han sido notificados para reiniciar la revisión.');

        $trabajo->refresh();

        // El trabajo vuelve al estado inicial con el nuevo archivo
        $this->assertSame('subido', $trabajo->estado);
        $this->assertStringContainsString('nueva-propuesta', $trabajo->archivo_pdf);

        // El pivote se reinicia para ambos evaluadores
        foreach ([$this->profesor1, $this->profesor2] as $profesor) {
            $pivot = DB::table('trabajo_profesor')
                ->where('id_trabajo', $trabajo->id_trabajo)
                ->where('id_profesor', $profesor->id_profesor)
                ->first();
            $this->assertSame('Asignado', $pivot->estado_revision);
            $this->assertNull($pivot->decision_evaluador);
            $this->assertNull($pivot->motivo_rechazo);
            $this->assertNotNull($pivot->fecha_limite_revision);
        }

        // La evaluación del ciclo anterior se resetea
        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertNotNull($evaluacion);
        $this->assertNull($evaluacion->resultado);
        $this->assertNull($evaluacion->nota_final);
        $this->assertNull($evaluacion->firma);
        $this->assertNull($evaluacion->firma_evaluador_2);
        $this->assertFalse($evaluacion->evaluacion_completada);
        $this->assertSame([], $evaluacion->criterios);

        $this->assertDatabaseHas('historial_estados', [
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'nueva_propuesta_subida',
        ]);

        // Se notifica a ambos evaluadores para reiniciar la revisión
        Notification::assertSentTo([$this->evaluador1, $this->evaluador2], NuevaVersionDisponible::class);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 5. Guardas de decidirPropuesta
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_decidir_propuesta_error_si_no_esta_en_estado_rechazo(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        // Sin evaluación rechazada, la decisión debe fallar
        $this->actingAs($this->gestor)
            ->post(route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo), ['decision' => 'no'])
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('error', 'Esta propuesta no está en estado de rechazo.');

        $this->assertNotSame('rechazada', $trabajo->fresh()->estado);
    }

    public function test_decidir_propuesta_si_requiere_archivo_pdf(): void
    {
        Notification::fake();
        $trabajo = $this->crearPropuestaEnRevision();
        $this->finalizarEvaluacionRechazada($trabajo);

        $url = route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo);
        $this->actingAs($this->gestor)
            ->from($url)
            ->post($url, ['decision' => 'si'])
            ->assertRedirect($url)
            ->assertSessionHasErrors('archivo_pdf');

        // El trabajo no debe haber cambiado de estado
        $this->assertSame('en_revision', $trabajo->fresh()->estado);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 6. Eliminación de propuestas rechazadas
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_eliminar_propuesta_rechazada_elimina_todo_el_registro(): void
    {
        // El observer de Trabajo borra el PDF del disco público al eliminar; se
        // fakea para que la prueba no toque el almacenamiento real.
        Storage::fake('public');

        $trabajo = $this->crearPropuestaEnRevision();
        $trabajo->update(['estado' => 'rechazada']);

        // Relaciones y registros asociados que deben eliminarse en cascada
        $estudiante = Estudiante::create([
            'id_trabajo' => $trabajo->id_trabajo,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'correo' => 'juan@test.com',
        ]);
        DB::table('trabajo_estudiante')->insert([
            'id_trabajo' => $trabajo->id_trabajo,
            'id_estudiante' => $estudiante->id_estudiante,
        ]);
        DB::table('alerta')->insert([
            'id_trabajo' => $trabajo->id_trabajo,
            'id_profesor' => $this->profesor1->id_profesor,
            'tipo_alerta' => 'plazo',
        ]);
        DB::table('seguimiento')->insert([
            'id_trabajo' => $trabajo->id_trabajo,
            'estado_visualizacion' => 'visto',
        ]);
        $idRubrica = DB::table('rubrica')->insertGetId(['id_tipo' => 1]);
        $trabajo->rubricas()->attach($idRubrica);
        $director = Director::create([
            'nombre' => 'Dir', 'apellido' => 'Director',
            'correo_electronico' => 'dir@test.com',
        ]);
        $trabajo->directores()->attach($director->id_director, ['rol' => 'director']);
        $trabajo->retroalimentaciones()->create([
            'user_id' => $this->gestor->id_usuario,
            'comentario' => 'Observación previa',
        ]);
        Evaluacion::create([
            'id_trabajo' => $trabajo->id_trabajo,
            'id_profesor' => $this->profesor1->id_profesor,
            'tipo_plantilla' => 'propuesta_de_grado',
            'resultado' => 'rechazada',
            'nota_final' => 2.5,
        ]);
        HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'propuesta_rechazada',
            'user_id' => $this->gestor->id_usuario,
        ]);

        $id = $trabajo->id_trabajo;

        $this->actingAs($this->gestor)
            ->delete(route('gestor.trabajo.eliminarRechazada', $id))
            ->assertRedirect(route('gestor.dashboard'))
            ->assertSessionHas('success', 'Propuesta rechazada eliminada correctamente.');

        $this->assertNull(Trabajo::find($id));
        $this->assertSame(0, DB::table('trabajo_profesor')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('trabajo_rubrica')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('director_trabajo')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('trabajo_estudiante')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('alerta')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('seguimiento')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('retroalimentaciones')->where('trabajo_grado_id', $id)->count());
        $this->assertSame(0, DB::table('historial_estados')->where('trabajo_grado_id', $id)->count());
        $this->assertSame(0, DB::table('evaluaciones')->where('id_trabajo', $id)->count());
        $this->assertSame(0, DB::table('estudiante')->where('id_trabajo', $id)->count());
    }

    public function test_eliminar_propuesta_solo_permite_estado_rechazada(): void
    {
        $trabajo = $this->crearPropuestaEnRevision(); // estado 'en_revision'

        $this->actingAs($this->gestor)
            ->delete(route('gestor.trabajo.eliminarRechazada', $trabajo->id_trabajo))
            ->assertRedirect(route('gestor.trabajo.detalles', $trabajo->id_trabajo))
            ->assertSessionHas('error', 'Solo es posible eliminar directamente las propuestas marcadas como Rechazadas.');

        $this->assertNotNull(Trabajo::find($trabajo->id_trabajo));
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 7. Rechazo del evaluador a la asignación
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_evaluador_rechaza_trabajo_asignado(): void
    {
        Notification::fake();
        $trabajo = $this->crearPropuestaEnRevision();

        $this->actingAs($this->evaluador1)
            ->postJson(route('evaluador.rechazar-trabajo', $trabajo->id_trabajo), [
                'motivo' => 'Conflicto de disponibilidad con el plazo establecido.',
            ])
            ->assertJson(['success' => true]);

        // El pivot queda con la decisión y el motivo
        $pivot = DB::table('trabajo_profesor')
            ->where('id_trabajo', $trabajo->id_trabajo)
            ->where('id_profesor', $this->profesor1->id_profesor)
            ->first();
        $this->assertSame('rechazado', $pivot->decision_evaluador);
        $this->assertSame('Conflicto de disponibilidad con el plazo establecido.', $pivot->motivo_rechazo);

        // Se registra en el historial como rechazo de evaluador
        $this->assertDatabaseHas('historial_estados', [
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'evaluador_rechazo',
        ]);

        // Se notifica a gestores y administradores para reasignar evaluador,
        // indicando el motivo del rechazo
        Notification::assertSentTo([$this->gestor, $this->admin], TrabajoRechazado::class, function ($notification) {
            $data = $notification->toDatabase($this->gestor);

            return $data['tipo'] === 'trabajo_rechazado'
                && str_contains($data['mensaje'], 'Conflicto de disponibilidad');
        });

        // El trabajo NO se marca como rechazado: sigue activo esperando reasignación
        $this->assertSame('en_revision', $trabajo->fresh()->estado);
    }

    public function test_evaluador_rechaza_requiere_motivo(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        $this->actingAs($this->evaluador1)
            ->postJson(route('evaluador.rechazar-trabajo', $trabajo->id_trabajo), [])
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 8. Persistencia de la firma al guardar progreso
    // ─────────────────────────────────────────────────────────────────────────────
    public function test_guardar_progreso_persiste_la_firma_del_evaluador(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        $firma = 'data:image/png;base64,' . str_repeat('B', 300);

        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'nota_final' => 3.5,
                'resultado' => null,
                'observaciones_globales' => 'Avance parcial',
                'criterios' => [],
                'firma' => $firma,
            ])
            ->assertJson(['success' => true]);

        // La firma del evaluador 1 (slot 1) se persiste al guardar progreso
        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertNotNull($evaluacion);
        $this->assertSame($firma, $evaluacion->firma);
        $this->assertNull($evaluacion->firma_evaluador_2);

        // Guardar progreso NO cambia el estado de revisión
        $this->assertSame('Asignado', $this->pivotEstado($trabajo, $this->profesor1));
    }

    public function test_guardar_progreso_firma_se_guarda_por_slot(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        $firma1 = 'data:image/png;base64,' . str_repeat('C', 300);
        $firma2 = 'data:image/png;base64,' . str_repeat('D', 300);

        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'criterios' => [],
                'firma' => $firma1,
            ])
            ->assertJson(['success' => true]);

        $this->actingAs($this->evaluador2)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'criterios' => [],
                'firma' => $firma2,
            ])
            ->assertJson(['success' => true]);

        // Cada firma queda en el campo de su slot
        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertSame($firma1, $evaluacion->firma);
        $this->assertSame($firma2, $evaluacion->firma_evaluador_2);

        // Guardar progreso no completa la evaluación
        $this->assertFalse($evaluacion->evaluacion_completada);
        $this->assertSame('Asignado', $this->pivotEstado($trabajo, $this->profesor1));
        $this->assertSame('Asignado', $this->pivotEstado($trabajo, $this->profesor2));
    }

    public function test_guardar_progreso_sin_firma_conserva_la_existente(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        $firma = 'data:image/png;base64,' . str_repeat('E', 300);

        // Primero se guarda con firma
        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'criterios' => [],
                'firma' => $firma,
            ])
            ->assertJson(['success' => true]);

        // Después sin firma: la anterior se conserva (no se pierde)
        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'criterios' => [],
                'firma' => null,
            ])
            ->assertJson(['success' => true]);

        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertSame($firma, $evaluacion->firma);
    }

    public function test_guardar_progreso_firma_corta_no_se_persiste(): void
    {
        $trabajo = $this->crearPropuestaEnRevision();

        $this->actingAs($this->evaluador1)
            ->postJson("/trabajos/{$trabajo->id_trabajo}/guardar-progreso", [
                'tipo_plantilla' => 'propuesta_de_grado',
                'criterios' => [],
                'firma' => 'firma-corta',
            ])
            ->assertJson(['success' => true]);

        // Una firma que no parece un data URL real (menos de 100 caracteres) no se persiste
        $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)->first();
        $this->assertNotNull($evaluacion);
        $this->assertNull($evaluacion->firma);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────────
    private function pivotEstado(Trabajo $trabajo, Profesor $profesor): ?string
    {
        return DB::table('trabajo_profesor')
            ->where('id_trabajo', $trabajo->id_trabajo)
            ->where('id_profesor', $profesor->id_profesor)
            ->value('estado_revision');
    }
}

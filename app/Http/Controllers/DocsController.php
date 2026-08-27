<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Sistema de Grado API',
    description: 'API para la gestión de trabajos de grado. Proporciona endpoints para administradores, gestores y evaluadores.'
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Servidor local'
)]
/** Controlador para mostrar la interfaz de documentación Swagger/OpenAPI. */
class DocsController extends Controller
{
    public function index()
    {
        return view('vendor.l5-swagger.index');
    }
}

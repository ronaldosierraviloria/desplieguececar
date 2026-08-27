<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/** Controlador para gestionar las notificaciones del usuario autenticado. */
class NotificacionController extends Controller
{
    #[OA\Get(
        path: '/notificaciones',
        summary: 'Obtener notificaciones del usuario autenticado',
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de notificaciones', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'notificaciones', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'noLeidas', type: 'integer'),
            ])),
        ]
    )]
    public function index()
    {
        $query = auth()->user()->notifications()->where(function($q) {
            $q->where('type', 'inicio_sesion')
              ->orWhere('type', 'like', '%InicioSesion%');
        });

        $notificaciones = (clone $query)->latest()->take(50)->get();
        $noLeidas = (clone $query)->whereNull('read_at')->count();

        return response()->json([
            'notificaciones' => $notificaciones,
            'noLeidas' => $noLeidas,
        ]);
    }

    #[OA\Get(
        path: '/notificaciones/panel',
        summary: 'Vista completa de notificaciones del usuario autenticado',
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Vista del panel de notificaciones'),
        ]
    )]
    public function panel()
    {
        $query = auth()->user()->notifications()->where(function($q) {
            $q->where('type', 'inicio_sesion')
              ->orWhere('type', 'like', '%InicioSesion%');
        });

        $notificaciones = (clone $query)->latest()->paginate(15);
        $noLeidas = (clone $query)->whereNull('read_at')->count();

        $layout = match (auth()->user()->rol) {
            'Administrador' => 'layouts.baseAdmin',
            'Gestor' => 'layouts.baseGestor',
            'Evaluador' => 'layouts.baseEvaluador',
            default => 'layouts.baseAdmin',
        };

        $backUrl = match (auth()->user()->rol) {
            'Administrador' => route('admin.dashboard'),
            'Gestor' => route('gestor.dashboard'),
            'Evaluador' => route('evaluador.dashboard'),
            default => route('login'),
        };

        return view('notificaciones.index', compact('notificaciones', 'noLeidas', 'layout', 'backUrl'));
    }

    #[OA\Post(
        path: '/notificaciones/{id}/leida',
        summary: 'Marcar notificación como leída',
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', description: 'ID de la notificación')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notificación marcada como leída', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
            ])),
            new OA\Response(response: 404, description: 'Notificación no encontrada'),
        ]
    )]
    public function marcarLeida($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    #[OA\Post(
        path: '/notificaciones/todas-leidas',
        summary: 'Marcar todas las notificaciones como leídas',
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Todas marcadas como leídas', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
            ])),
        ]
    )]
    public function marcarTodasLeidas()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    #[OA\Delete(
        path: '/notificaciones/todas',
        summary: 'Eliminar todas las notificaciones del usuario',
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Notificaciones eliminadas', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
            ])),
        ]
    )]
    public function destroyAll()
    {
        auth()->user()->notifications()->delete();

        return response()->json(['success' => true]);
    }
}

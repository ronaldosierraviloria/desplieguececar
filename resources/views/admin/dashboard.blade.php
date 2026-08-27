@extends('layouts.baseAdmin')

@section('title', 'Inicio | Panel Admin')
@section('meta_description', 'Dashboard principal del sistema de gestión de trabajos de grado con estadísticas avanzadas, KPIs y gráficos Recharts.')

@section('content')
<x-notification type="success" />

{{-- Contenedor del Dashboard React --}}
<div id="admin-dashboard-root" data-props="{{ json_encode($dashboardProps) }}"></div>

@push('scripts')
@viteReactRefresh
@vite(['resources/css/app.css', 'resources/js/admin-dashboard.jsx'])
@endpush
@endsection
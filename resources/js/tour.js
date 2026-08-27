import { TourGuideClient } from '@sjmc11/tourguidejs/src/Tour';
import '@sjmc11/tourguidejs/src/scss/tour.scss';

const TOURS = {
    admin: [
        {
            target: '#app-sidebar img',
            title: 'Bienvenido al panel de Administración',
            content: 'Este recorrido te mostrará las secciones principales del sistema. Usa los botones, las flechas del teclado o el clic para avanzar.',
        },
        {
            target: '#app-sidebar ul li:first-child a',
            title: 'Inicio',
            content: 'Accede al resumen general del sistema: estadísticas, indicadores y accesos rápidos.',
        },
        {
            target: '#app-sidebar ul li:nth-child(2) a',
            title: 'Trabajos de Grado',
            content: 'Consulta y gestiona los trabajos de grado registrados en el sistema.',
        },
        {
            target: '#app-sidebar ul li:nth-child(3) a',
            title: 'Estudiantes',
            content: 'Administra la información y el estado de los estudiantes.',
        },
        {
            target: '#app-sidebar ul li:nth-child(4) a',
            title: 'Comentarios',
            content: 'Consulta los motivos por los que los evaluadores rechazan los trabajos de grado y el motivo de eliminación cuando se elimina un estudiante.',
        },
        {
            target: '#app-sidebar ul li:nth-child(5) a',
            title: 'Usuarios',
            content: 'Gestiona los usuarios del sistema y asigna sus roles.',
        },
        {
            target: '#app-sidebar ul li:nth-child(6) a',
            title: 'Facultades y Áreas',
            content: 'Organiza las facultades y áreas de conocimiento.',
        },
        {
            target: '#app-sidebar ul li:nth-child(7) a',
            title: 'Tipos de Trabajo',
            content: 'Administra las tipologías de trabajos de grado disponibles.',
        },
        {
            target: '#app-sidebar form button',
            title: 'Cerrar sesión',
            content: 'Sal de la plataforma de forma segura cuando termines.',
        },
        {
            target: '[x-data="notificacionesDropdown"]',
            title: 'Notificaciones',
            content: 'Recibe alertas de las actividades recientes del sistema.',
        },
        {
            target: '#dropdownUserButton',
            title: 'Tu perfil',
            content: 'Accede a tus datos personales y ajustes de cuenta.',
        },
        {
            title: '¡Todo listo!',
            content: 'Explora el panel con confianza. Puedes repetir este recorrido cuando quieras con el botón de ayuda (?) ubicado en el encabezado superior.',
        },
    ],
    gestor: [
        {
            target: '#app-sidebar img',
            title: 'Bienvenido al panel del Gestor',
            content: 'Este recorrido te mostrará las secciones principales de tu panel. Usa los botones, las flechas del teclado o el clic para avanzar.',
        },
        {
            target: '#app-sidebar ul li:first-child a',
            title: 'Inicio',
            content: 'Accede al resumen de tus proyectos y actividades.',
        },
        {
            target: '#app-sidebar ul li:nth-child(2) a',
            title: 'Añadir Proyecto',
            content: 'Registra un nuevo trabajo de grado en el sistema.',
        },
        {
            target: '#app-sidebar ul li:nth-child(3) a',
            title: 'Lista de Evaluadores',
            content: 'Consulta los evaluadores disponibles y su asignación.',
        },
        {
            target: '#app-sidebar form button',
            title: 'Cerrar sesión',
            content: 'Sal de la plataforma de forma segura cuando termines.',
        },
        {
            target: '[x-data="notificacionesDropdown"]',
            title: 'Notificaciones',
            content: 'Recibe alertas de las actividades recientes del sistema.',
        },
        {
            target: '#dropdownUserButton',
            title: 'Tu perfil',
            content: 'Accede a tus datos personales y ajustes de cuenta.',
        },
        {
            title: '¡Todo listo!',
            content: 'Explora tu panel con confianza. Puedes repetir este recorrido cuando quieras con el botón de ayuda (?) ubicado en el encabezado superior.',
        },
    ],
    evaluador: [
        {
            target: '#app-sidebar img',
            title: 'Bienvenido al panel del Evaluador',
            content: 'Este recorrido te mostrará las secciones principales de tu panel. Usa los botones, las flechas del teclado o el clic para avanzar.',
        },
        {
            target: '#app-sidebar ul li:first-child a',
            title: 'Trabajos Asignados',
            content: 'Consulta los trabajos de grado que tienes asignados para evaluar.',
        },
        {
            target: '#app-sidebar ul li:nth-child(2) a',
            title: 'Calificados',
            content: 'Revisa los trabajos que ya has evaluado y sus calificaciones.',
        },
        {
            target: '#app-sidebar form button',
            title: 'Cerrar sesión',
            content: 'Sal de la plataforma de forma segura cuando termines.',
        },
        {
            target: '[x-data="notificacionesDropdown"]',
            title: 'Notificaciones',
            content: 'Recibe alertas de las actividades recientes del sistema.',
        },
        {
            target: '#dropdownUserButton',
            title: 'Tu perfil',
            content: 'Accede a tus datos personales y ajustes de cuenta.',
        },
        {
            title: '¡Todo listo!',
            content: 'Explora tu panel con confianza. Puedes repetir este recorrido cuando quieras con el botón de ayuda (?) ubicado en el encabezado superior.',
        },
    ],
};

const TOUR_OPTIONS = {
    nextLabel: 'Siguiente',
    prevLabel: 'Atrás',
    finishLabel: 'Finalizar',
    showStepDots: true,
    stepDotsPlacement: 'footer',
    showStepProgress: true,
    showButtons: true,
    closeButton: true,
    keyboardControls: true,
    exitOnEscape: true,
    exitOnClickOutside: true,
    autoScroll: true,
    autoScrollSmooth: true,
    autoScrollOffset: 90,
    targetPadding: 12,
    backdropColor: 'rgba(7, 50, 30, 0.55)',
    dialogMaxWidth: 360,
    completeOnFinish: true,
    activeStepInteraction: true,
    debug: false,
};

let tour = null;

function getRole() {
    const meta = document.querySelector('meta[name="tour-role"]');
    return meta ? meta.getAttribute('content') : null;
}

function buildSteps(role) {
    return (TOURS[role] || [])
        .filter((step) => !step.target || document.querySelector(step.target))
        .map((step) => ({ ...step, group: role }));
}

function getClient(role) {
    if (tour) return tour;
    tour = new TourGuideClient({ ...TOUR_OPTIONS, group: role, steps: [] });
    return tour;
}

function isFinished(role) {
    try {
        return getClient(role).isFinished(role);
    } catch (error) {
        return false;
    }
}

function startTour(role = getRole()) {
    if (!role || !TOURS[role]) return;

    const steps = buildSteps(role);
    if (!steps.length) return;

    const client = getClient(role);
    if (client.isVisible) return;

    client.setOptions({ steps });
    client.start(role)
        .then(() => client.finishTour(false, role))
        .catch(() => {});
}

function restartTour(role = getRole()) {
    if (!role || !TOURS[role]) return;
    getClient(role).deleteFinishedTour(role);
    startTour(role);
}

function initAutoStart() {
    const role = getRole();
    if (!role || !TOURS[role] || isFinished(role)) return;

    const waitForReady = () => {
        if (document.body.classList.contains('alpine-ready')) {
            startTour(role);
            return;
        }
        requestAnimationFrame(waitForReady);
    };

    waitForReady();
}

window.startTour = startTour;
window.restartTour = restartTour;

document.addEventListener('DOMContentLoaded', initAutoStart);

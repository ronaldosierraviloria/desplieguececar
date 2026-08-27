import React, { useState } from 'react';
import {
  FileText,
  UserCheck,
  UserX,
  Clock,
  CheckCircle2,
  AlertTriangle,
  Award,
  GraduationCap,
  Briefcase,
  Users,
  ChevronDown
} from 'lucide-react';
import {
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Tooltip,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  BarChart,
  Bar
} from 'recharts';

// Custom Minimalist Tooltip (Pure Shadcn / Vercel style)
const MinimalTooltip = ({ active, payload, label }) => {
  if (active && payload && payload.length) {
    return (
      <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-md">
        {label && <p className="font-semibold text-slate-800 mb-1 border-b border-slate-100 pb-1">{label}</p>}
        {payload.map((entry, index) => (
          <div key={`tt-${index}`} className="flex items-center justify-between gap-3 py-0.5">
            <span className="text-slate-500">{entry.name}:</span>
            <span className="font-bold text-slate-900">{entry.value}</span>
          </div>
        ))}
      </div>
    );
  }
  return null;
};

export default function AdminDashboard({ data }) {
  const { kpis, flowStatusData, monthlyData, modalidadData, dictamenesData = [], year: initialYear, yearsDisponibles } = data;
  const [selectedYear, setSelectedYear] = useState(initialYear);

  const handleYearChange = (e) => {
    const newYear = e.target.value;
    setSelectedYear(newYear);
    window.location.href = `/admin?year=${newYear}`;
  };

  // Metric Groups with soft colored borders and white background
  const projectKpis = [
    { title: 'Total Trabajos', value: kpis.totalTrabajos, sub: 'Registrados', icon: FileText, borderColor: 'border-blue-200', iconBg: 'bg-blue-100 text-blue-700' },
    { title: 'Con Evaluadores', value: kpis.conEvaluadores, sub: 'Asignados', icon: UserCheck, borderColor: 'border-indigo-200', iconBg: 'bg-indigo-100 text-indigo-700' },
    { title: 'Sin Evaluadores', value: kpis.sinEvaluadores, sub: 'Pendientes', icon: UserX, borderColor: 'border-amber-200', iconBg: 'bg-amber-100 text-amber-700' },
    { title: 'En Evaluación', value: kpis.enEvaluacion, sub: 'En proceso', icon: Clock, borderColor: 'border-violet-200', iconBg: 'bg-violet-100 text-violet-700' },
    { title: 'Evaluados', value: kpis.evaluados, sub: 'Calificados', icon: CheckCircle2, borderColor: 'border-emerald-200', iconBg: 'bg-emerald-100 text-emerald-700' },
  ];

  const statusKpis = [
    { title: 'En Revisión', value: kpis.enRevision, sub: 'Requiere ajuste', icon: AlertTriangle, borderColor: 'border-rose-200', iconBg: 'bg-rose-100 text-rose-700' },
    { title: 'Finalizados', value: kpis.finalizados, sub: 'Con Acta', icon: Award, borderColor: 'border-teal-200', iconBg: 'bg-teal-100 text-teal-700' },
    { title: 'Estudiantes', value: kpis.totalEstudiantes, sub: 'Registrados', icon: GraduationCap, borderColor: 'border-cyan-200', iconBg: 'bg-cyan-100 text-cyan-700' },
    { title: 'Directores', value: kpis.totalDirectores, sub: 'Tutores principales', icon: Briefcase, borderColor: 'border-purple-200', iconBg: 'bg-purple-100 text-purple-700' },
    { title: 'SubDirectores', value: kpis.totalSubdirectores, sub: 'Co-directores', icon: Users, borderColor: 'border-sky-200', iconBg: 'bg-sky-100 text-sky-700' },
  ];

  return (
    <div className="space-y-6 font-sans text-slate-900 antialiased max-w-7xl mx-auto">
      {/* ── HEADER ── */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
          <h1 className="text-xl font-bold tracking-tight text-slate-900">Dashboard</h1>
          <p className="text-xs text-slate-500 mt-0.5">Resumen de trabajos de grado y estado del proceso.</p>
        </div>

        <div className="flex items-center gap-3">
          <a
            href={`/admin/exportar-excel?year=${selectedYear}`}
            className="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-[#07321e] text-white text-xs font-bold shadow-xs hover:bg-[#07321e]/90 transition-all active:scale-95 shrink-0"
            title="Descargar reporte completo en Excel Multi-Hoja (Semestre 1, Semestre 2, Evaluadores)"
          >
            <FileText className="h-4 w-4 text-[#c2d500]" />
            Descargar Reporte Excel
          </a>

          <div className="relative">
            <select
              value={selectedYear}
              onChange={handleYearChange}
              className="h-9 appearance-none rounded-lg border border-slate-200 bg-white px-3 pr-8 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none cursor-pointer shadow-2xs"
            >
              {yearsDisponibles.map((y) => (
                <option key={y} value={y}>
                  Año {y}
                </option>
              ))}
            </select>
            <ChevronDown className="pointer-events-none absolute right-2.5 top-3 h-3.5 w-3.5 text-slate-400" />
          </div>
        </div>
      </div>

      {/* ── METRICAS KPIS (10 KPIs con fondo blanco y contornos de color suave) ── */}
      <div className="space-y-3">
        {/* Fila 1: Trabajos y Evaluación */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          {projectKpis.map((item, idx) => (
            <div
              key={idx}
              className={`rounded-xl border ${item.borderColor} bg-white p-3.5 transition-all hover:shadow-md hover:-translate-y-0.5`}
            >
              <div className="flex items-center justify-between gap-2">
                <p className="text-[11px] font-bold text-slate-700 truncate">{item.title}</p>
                <div className={`p-1.5 rounded-lg shrink-0 ${item.iconBg}`}>
                  <item.icon className="h-4 w-4" />
                </div>
              </div>
              <p className="text-2xl font-black tracking-tight text-slate-900 mt-1">{item.value}</p>
              <p className="text-[10px] font-medium text-slate-500 mt-0.5">{item.sub}</p>
            </div>
          ))}
        </div>

        {/* Fila 2: Estados y Personas */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          {statusKpis.map((item, idx) => (
            <div
              key={idx}
              className={`rounded-xl border ${item.borderColor} bg-white p-3.5 transition-all hover:shadow-md hover:-translate-y-0.5`}
            >
              <div className="flex items-center justify-between gap-2">
                <p className="text-[11px] font-bold text-slate-700 truncate">{item.title}</p>
                <div className={`p-1.5 rounded-lg shrink-0 ${item.iconBg}`}>
                  <item.icon className="h-4 w-4" />
                </div>
              </div>
              <p className="text-2xl font-black tracking-tight text-slate-900 mt-1">{item.value}</p>
              <p className="text-[10px] font-medium text-slate-500 mt-0.5">{item.sub}</p>
            </div>
          ))}
        </div>
      </div>

      {/* ── GRAFICOS (Diseño limpio y profesional) ── */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {/* Carga Mensual */}
        <div className="lg:col-span-7 rounded-lg border border-slate-200 bg-white p-5">
          <div className="mb-4">
            <h3 className="text-sm font-semibold text-slate-900">Carga Mensual de Trabajos</h3>
            <p className="text-xs text-slate-500">Registros por mes durante {selectedYear}</p>
          </div>

          <div className="h-64 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={monthlyData} margin={{ top: 10, right: 10, left: -25, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="mes" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip content={<MinimalTooltip />} />
                <Area
                  type="monotone"
                  dataKey="trabajos"
                  name="Trabajos"
                  stroke="#0f172a"
                  strokeWidth={1.5}
                  fill="#0f172a"
                  fillOpacity={0.05}
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Estado del Flujo */}
        <div className="lg:col-span-5 rounded-lg border border-slate-200 bg-white p-5 flex flex-col justify-between">
          <div>
            <h3 className="text-sm font-semibold text-slate-900">Estado del Flujo</h3>
            <p className="text-xs text-slate-500">Etapas del proceso</p>
          </div>

          <div className="h-48 w-full relative my-2">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={flowStatusData}
                  cx="50%"
                  cy="50%"
                  innerRadius={50}
                  outerRadius={75}
                  paddingAngle={2}
                  dataKey="value"
                >
                  {flowStatusData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} stroke="#ffffff" strokeWidth={1.5} />
                  ))}
                </Pie>
                <Tooltip content={<MinimalTooltip />} />
              </PieChart>
            </ResponsiveContainer>

            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
              <span className="text-xl font-bold text-slate-900">{kpis.totalTrabajos}</span>
              <span className="text-[10px] text-slate-400 font-medium uppercase">Total</span>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 pt-3">
            {flowStatusData.map((item, i) => (
              <div key={i} className="flex items-center gap-2">
                <span className="h-2 w-2 rounded-full shrink-0" style={{ backgroundColor: item.color }} />
                <span className="text-slate-600 truncate">{item.name}</span>
                <span className="font-semibold text-slate-900 ml-auto">{item.value}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Fila 2: Modalidad y Equipo Docente */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {/* Modalidad de Grado */}
        <div className="rounded-lg border border-slate-200 bg-white p-5">
          <div className="mb-4">
            <h3 className="text-sm font-semibold text-slate-900">Modalidades de Grado</h3>
            <p className="text-xs text-slate-500">Distribución por tipo de trabajo</p>
          </div>

          <div className="h-56 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={modalidadData} margin={{ top: 10, right: 10, left: -25, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip content={<MinimalTooltip />} />
                <Bar dataKey="total" name="Proyectos" fill="#0f172a" radius={[3, 3, 0, 0]} barSize={30} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Resultados de Evaluación / Dictámenes */}
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="mb-4">
            <h3 className="text-sm font-semibold text-slate-900">Resultados de Evaluación</h3>
            <p className="text-xs text-slate-500">Dictámenes emitidos (Aprobados, Con Correcciones, Rechazados)</p>
          </div>

          <div className="h-56 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={dictamenesData} margin={{ top: 10, right: 10, left: -25, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip content={<MinimalTooltip />} />
                <Bar dataKey="cantidad" name="Evaluaciones" radius={[4, 4, 0, 0]} barSize={34}>
                  {dictamenesData.map((entry, index) => (
                    <Cell key={`dictamen-${index}`} fill={entry.fill} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  );
}

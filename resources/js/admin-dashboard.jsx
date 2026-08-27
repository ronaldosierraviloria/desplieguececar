import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from './components/AdminDashboard';

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('admin-dashboard-root');
  if (container) {
    const rawProps = container.getAttribute('data-props');
    let props = {};
    try {
      if (rawProps) {
        props = JSON.parse(rawProps);
      }
    } catch (e) {
      console.error('Error al parsear data-props para AdminDashboard:', e);
    }
    const root = createRoot(container);
    root.render(<AdminDashboard data={props} />);
  }
});

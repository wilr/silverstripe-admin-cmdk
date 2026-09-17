import React from 'react';
import { createRoot } from 'react-dom/client';
import CommandPalette from '../components/CommandPalette';

/**
 * Mounts the command palette into its own React root appended to the end of `<body>`, independent
 * of the CMS's main Redux/Injector-driven React tree - a bug here should never be able to break
 * the rest of the admin UI.
 */
function boot() {
  const config = window.__CmdkConfig__;

  if (!config || !config.endpoints) {
    return;
  }

  const container = document.createElement('div');
  container.id = 'wilr-cmdk-root';
  document.body.appendChild(container);

  createRoot(container).render(<CommandPalette endpoints={config.endpoints} />);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

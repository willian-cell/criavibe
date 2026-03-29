/**
 * CriaVibe — api.js
 * Helper para chamadas à API PHP com credentials (session PHP)
 */

const API = {
  base: '/api',

  async fetch(path, options = {}) {
    const res = await fetch(this.base + path, {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok && data.status === 'erro') throw new Error(data.mensagem || 'Erro desconhecido.');
    return data;
  },

  get(path) { return this.fetch(path, { method: 'GET' }); },

  post(path, body) {
    return this.fetch(path, {
      method: 'POST',
      body: JSON.stringify(body),
    });
  },

  upload(path, formData) {
    return fetch(this.base + path, {
      method: 'POST',
      credentials: 'include',
      body: formData,
    }).then(r => r.json());
  },
};

// Toast global — Estilo Premium
function showToast(msg, type = 'success') {
  const existing = document.querySelectorAll('.toast');
  existing.forEach(e => e.remove());

  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  
  const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
  t.innerHTML = `
    <i class="fa-solid ${icon}"></i>
    <span>${msg}</span>
  `;
  
  // Estilo dinâmico injetado se não houver no CSS
  t.style.position = 'fixed';
  t.style.bottom = '30px';
  t.style.right = '30px';
  t.style.padding = '12px 24px';
  t.style.borderRadius = '12px';
  t.style.background = type === 'success' ? '#059669' : '#dc2626';
  t.style.color = '#fff';
  t.style.display = 'flex';
  t.style.alignItems = 'center';
  t.style.gap = '10px';
  t.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
  t.style.zIndex = '10000';
  t.style.animation = 'toastIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
  t.style.fontFamily = 'inherit';
  t.style.fontSize = '0.9rem';
  t.style.fontWeight = '600';

  if (!document.getElementById('toast-style')) {
    const s = document.createElement('style');
    s.id = 'toast-style';
    s.innerHTML = `
      @keyframes toastIn { from { transform: translateX(100%) opacity: 0; } to { transform: translateX(0) opacity: 1; } }
      .toast { transition: 0.3s; }
    `;
    document.head.appendChild(s);
  }

  document.body.appendChild(t);
  setTimeout(() => {
    t.style.transform = 'translateX(120%)';
    t.style.opacity = '0';
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

// Fechar dropdowns ao clicar fora
document.addEventListener('click', e => {
  if (!e.target.closest('.actions-menu')) {
    document.querySelectorAll('.actions-dropdown.open')
      .forEach(d => d.classList.remove('open'));
  }
});

function toggleMenu(btn) {
  event.stopPropagation();
  document.querySelectorAll('.actions-dropdown.open').forEach(d => {
    if (d !== btn.nextElementSibling) d.classList.remove('open');
  });
  btn.nextElementSibling.classList.toggle('open');
}

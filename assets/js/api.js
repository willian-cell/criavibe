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

// Toast global
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = 'toast';
  t.style.background = type === 'error' ? '#dc2626' : '#1e293b';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
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

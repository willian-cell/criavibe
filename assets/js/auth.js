/**
 * CriaVibe — auth.js
 * Verifica autenticação via /api/auth/me.php e expõe currentUser global
 */

let currentUser = null;

async function checkAuth(redirect = true) {
  try {
    const data = await API.get('/auth/me.php');
    if (data.status === 'ok') {
      currentUser = data.usuario;
      return currentUser;
    }
  } catch {}
  if (redirect) window.location.href = '/entrar.html';
  return null;
}

async function requireAuth() {
  const u = await checkAuth(true);
  return u;
}

async function logout() {
  await API.post('/auth/logout.php', {});
  window.location.href = '/entrar.html';
}

<?php
session_start();
require 'db.php'; // Debe definir $pdo (PDO conectado a MySQL)

// --- CSRF mínimo ---
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];
function check_csrf() {
  if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf'], $_POST['_csrf'])) {
    http_response_code(400);
    die('CSRF inválido');
  }
}

function sanitize_phone($raw) {
  // Deja dígitos y +, elimina otros caracteres. Limita a 20.
  $p = preg_replace('/[^0-9+]/', '', $raw ?? '');
  return substr($p, 0, 20);
}

// --- Manejo de acciones ---
$flash = null;
try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
      // Crear
      $nombre = trim($_POST['nombre'] ?? '');
      $correo = trim($_POST['correo'] ?? '');
      $celular = sanitize_phone($_POST['celular'] ?? '');
      $mensaje = trim($_POST['mensaje'] ?? '');
      if ($nombre && $correo && $mensaje) {
        check_csrf();
        $stmt = $pdo->prepare('INSERT INTO mensajes (nombre, correo, celular, mensaje, fecha) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$nombre, $correo, $celular, $mensaje]);
        $flash = ['type' => 'success', 'msg' => '¡Mensaje creado!'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Completa al menos nombre, correo y mensaje.'];
      }
    } elseif ($action === 'update') {
      // Actualizar
      check_csrf();
      $id = intval($_POST['id'] ?? 0);
      $nombre = trim($_POST['nombre'] ?? '');
      $correo = trim($_POST['correo'] ?? '');
      $celular = sanitize_phone($_POST['celular'] ?? '');
      $mensaje = trim($_POST['mensaje'] ?? '');
      if ($id && $nombre && $correo && $mensaje) {
        $stmt = $pdo->prepare('UPDATE mensajes SET nombre=?, correo=?, celular=?, mensaje=? WHERE id=?');
        $stmt->execute([$nombre, $correo, $celular, $mensaje, $id]);
        $flash = ['type' => 'success', 'msg' => 'Registro actualizado.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Datos incompletos para actualizar.'];
      }
    } elseif ($action === 'delete') {
      // Eliminar
      check_csrf();
      $id = intval($_POST['id'] ?? 0);
      if ($id) {
        $stmt = $pdo->prepare('DELETE FROM mensajes WHERE id = ?');
        $stmt->execute([$id]);
        $flash = ['type' => 'success', 'msg' => 'Registro eliminado.'];
      }
    }
  }
} catch (Throwable $e) {
  $flash = ['type' => 'error', 'msg' => 'Error: ' . htmlspecialchars($e->getMessage())];
}

// --- Consulta de mensajes ---
$rows = [];
try {
  // Soporta que la columna celular aún no exista (evita fallo en SELECT *)
  $cols = $pdo->query("SHOW COLUMNS FROM mensajes")->fetchAll(PDO::FETCH_COLUMN);
  $hasCel = in_array('celular', $cols);
  $select = $hasCel ? 'id, nombre, correo, celular, mensaje, fecha' : 'id, nombre, correo, mensaje, fecha';
  $rows = $pdo->query("SELECT $select FROM mensajes ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $flash = ['type' => 'error', 'msg' => 'Error consultando la base de datos: ' . htmlspecialchars($e->getMessage())];
}

?><!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mensajes · CRUD con Celular</title>
  <!-- TailwindCSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class', theme: { extend: { boxShadow: { 'soft': '0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.1)'} } } };
  </script>
  <script>
    (function() {
      const saved = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const html = document.documentElement;
      if (saved === 'dark' || (!saved && prefersDark)) html.classList.add('dark');
    })();
  </script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gray-50 dark:bg-neutral-950 text-gray-800 dark:text-gray-100 antialiased">
  <div class="max-w-6xl mx-auto p-6 lg:p-10" x-data="crudApp()">
    <header class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Mensajes · CRUD (con Celular)</h1>
        <p class="text-sm text-gray-600 dark:text-gray-300">PHP + MySQL con Tailwind y Alpine.js</p>
      </div>
      <button @click="toggleTheme()" class="px-3 py-2 rounded-xl border border-gray-300/60 dark:border-white/10 hover:bg-white/60 dark:hover:bg-white/5 transition">🌓 Tema</button>
    </header>

    <?php if ($flash): ?>
      <div x-init="notify('<?php echo $flash['type'] ?>','<?php echo addslashes($flash['msg']) ?>')" class="mb-4 rounded-xl border <?php echo $flash['type']==='success'?'border-emerald-200 bg-emerald-50 text-emerald-900':'border-rose-200 bg-rose-50 text-rose-900' ?> p-3">
        <strong><?php echo $flash['type']==='success'?'Éxito':'Error' ?>:</strong> <?php echo htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <!-- Crear -->
    <section class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-neutral-900 p-6 shadow-soft mb-8">
      <h2 class="text-lg font-semibold mb-4">Nuevo mensaje</h2>
      <form method="POST" class="grid md:grid-cols-3 gap-4">
        <input type="hidden" name="action" value="create" />
        <input type="hidden" name="_csrf" value="<?php echo $CSRF; ?>" />
        <div class="md:col-span-1">
          <label class="block text-sm mb-1">Nombre</label>
          <input type="text" name="nombre" required class="w-full rounded-xl border-gray-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Tu nombre">
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm mb-1">Correo</label>
          <input type="email" name="correo" required class="w-full rounded-xl border-gray-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="tucorreo@ejemplo.com">
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm mb-1">Celular <span class="opacity-60">(opcional)</span></label>
          <input type="tel" name="celular" pattern="[0-9+ ]{7,20}" class="w-full rounded-xl border-gray-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="+57 3001234567">
        </div>
        <div class="md:col-span-3">
          <label class="block text-sm mb-1">Mensaje</label>
          <textarea name="mensaje" rows="3" required class="w-full rounded-xl border-gray-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Escribe tu mensaje..."></textarea>
        </div>
        <div class="md:col-span-3 flex justify-end">
          <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 shadow-soft">Guardar</button>
        </div>
      </form>
    </section>

    <!-- Tabla -->
    <section class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-soft overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100/70 dark:bg-white/5 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="text-left px-4 py-3">Nombre</th>
            <th class="text-left px-4 py-3">Correo</th>
            <th class="text-left px-4 py-3">Celular</th>
            <th class="text-left px-4 py-3">Mensaje</th>
            <th class="text-left px-4 py-3">Fecha</th>
            <th class="text-left px-4 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t border-gray-200 dark:border-white/10 hover:bg-gray-50/80 dark:hover:bg-white/5">
              <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($r['nombre']) ?></td>
              <td class="px-4 py-3"><a href="mailto:<?php echo htmlspecialchars($r['correo']) ?>" class="underline decoration-dotted"><?php echo htmlspecialchars($r['correo']) ?></a></td>
              <td class="px-4 py-3">
                <?php if (isset($r['celular']) && $r['celular']): ?>
                  <a href="tel:<?php echo htmlspecialchars($r['celular']) ?>" class="underline decoration-dotted"><?php echo htmlspecialchars($r['celular']) ?></a>
                  <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $r['celular']); ?>" target="_blank" class="ml-2 text-green-600 dark:text-green-400">WhatsApp</a>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 max-w-xl">
                <div class="line-clamp-2" x-data @click="$dispatch('open-modal', {title: 'Mensaje de <?php echo addslashes(htmlspecialchars($r['nombre'])) ?>', content: `<?php echo addslashes(htmlspecialchars($r['mensaje'])) ?>`})">
                  <?php echo nl2br(htmlspecialchars($r['mensaje'])) ?>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($r['fecha']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <button
                  class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white mr-2"
                  @click="$dispatch('edit-open', { id: <?php echo $r['id'] ?>, nombre: '<?php echo addslashes(htmlspecialchars($r['nombre'])) ?>', correo: '<?php echo addslashes(htmlspecialchars($r['correo'])) ?>', celular: '<?php echo isset($r['celular']) ? addslashes(htmlspecialchars($r['celular'])) : '' ?>', mensaje: `<?php echo addslashes(htmlspecialchars($r['mensaje'])) ?>` })">
                  Editar
                </button>
                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este registro?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="_csrf" value="<?php echo $CSRF; ?>">
                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                  <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay mensajes aún.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <footer class="mt-10 text-center text-sm text-gray-500">
      © <?php echo date('Y'); ?> · Demo CRUD PHP + MySQL
    </footer>

    <!-- Modal ver mensaje -->
    <div x-data="{show:false,title:'',content:''}" @open-modal.window="show=true;title=$event.detail.title;content=$event.detail.content" x-show="show" x-cloak class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/50" @click="show=false"></div>
      <div class="relative max-w-xl mx-auto mt-24 rounded-2xl border border-white/10 bg-neutral-900 text-white shadow-soft">
        <div class="flex items-center justify-between px-5 py-3 border-b border-white/10">
          <h3 class="font-semibold" x-text="title"></h3>
          <button class="px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20" @click="show=false">✕</button>
        </div>
        <div class="p-5 text-sm whitespace-pre-line" x-text="content"></div>
      </div>
    </div>

    <!-- Modal editar -->
    <div x-data="editModal()" @edit-open.window="open($event.detail)" x-show="show" x-cloak class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/50" @click="close()"></div>
      <div class="relative max-w-xl mx-auto mt-20 rounded-2xl border border-white/10 bg-neutral-900 text-white shadow-soft">
        <div class="flex items-center justify-between px-5 py-3 border-b border-white/10">
          <h3 class="font-semibold">Editar mensaje</h3>
          <button class="px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20" @click="close()">✕</button>
        </div>
        <form method="POST" class="p-5 space-y-3">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="_csrf" value="<?php echo $CSRF; ?>">
          <input type="hidden" name="id" :value="id">
          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm mb-1">Nombre</label>
              <input type="text" name="nombre" x-model="nombre" required class="w-full rounded-xl border border-white/10 bg-neutral-800 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm mb-1">Correo</label>
              <input type="email" name="correo" x-model="correo" required class="w-full rounded-xl border border-white/10 bg-neutral-800 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm mb-1">Celular</label>
              <input type="tel" name="celular" x-model="celular" pattern="[0-9+ ]{7,20}" class="w-full rounded-xl border border-white/10 bg-neutral-800 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm mb-1">Mensaje</label>
              <textarea name="mensaje" rows="4" x-model="mensaje" required class="w-full rounded-xl border border-white/10 bg-neutral-800 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="close()" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20">Cancelar</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function crudApp() {
      return {
        toggleTheme() {
          const html = document.documentElement;
          const nowDark = !html.classList.contains('dark');
          html.classList.toggle('dark', nowDark);
          localStorage.setItem('theme', nowDark ? 'dark' : 'light');
        },
        notify(type, msg) {
          const el = document.createElement('div');
          el.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-xl text-sm text-white ${type==='success'?'bg-emerald-600':'bg-rose-600'}`;
          el.textContent = msg;
          document.body.appendChild(el);
          setTimeout(() => el.remove(), 2600);
        }
      }
    }
    function editModal() {
      return {
        show: false,
        id: null, nombre: '', correo: '', celular: '', mensaje: '',
        open(data) { this.show = true; this.id = data.id; this.nombre = data.nombre; this.correo = data.correo; this.celular = data.celular || ''; this.mensaje = data.mensaje; },
        close() { this.show = false; }
      }
    }
  </script>
</body>
</html>

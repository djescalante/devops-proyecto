# 🚀 PHP + MySQL CRUD con Docker & phpMyAdmin

Aplicación web desarrollada en **PHP 8.3 + MySQL 8.0**, con despliegue automatizado mediante **Docker Compose**, y panel de administración **phpMyAdmin**.  
Incluye módulo CRUD completo (crear, leer, actualizar y eliminar) con campo adicional **Celular** y soporte para modo oscuro.

---

## 🧰 Stack Tecnológico

| Componente | Versión / Imagen | Descripción |
|-------------|------------------|--------------|
| PHP         | `php:8.3-apache` | App principal con PDO y mod_rewrite habilitados |
| MySQL       | `mysql:8.0`      | Base de datos relacional |
| phpMyAdmin  | `phpmyadmin:latest` | Interfaz web para administrar MySQL |
| TailwindCSS | CDN              | Estilos modernos y responsive |
| Alpine.js   | CDN              | Interactividad (modales, toasts, etc.) |

---

## 📁 Estructura del Proyecto

```
curso-docker-web-php/
│
├── app/
│   ├── Dockerfile
│   ├── index_crud_celular.php
│   ├── db.php
│   └── ...
│
├── initdb/
│   └── 000_schema.sql
│
├── migrations/
│   ├── 000_schema.sql
│   └── 001_add_celular.sql
│
├── .env
└── docker-compose.yml
```

---

## ⚙️ Configuración del entorno

Archivo `.env`:
```bash
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_DATABASE=app
MYSQL_USER=app
MYSQL_PASSWORD=app
TZ=America/Bogota
```

---

## 🐳 Despliegue con Docker

```bash
docker compose up -d --build
```

**Servicios disponibles:**
| Servicio | URL | Descripción |
|-----------|-----|-------------|
| App PHP   | [http://localhost:8080](http://localhost:8080) | Aplicación web |
| phpMyAdmin | [http://localhost:8081](http://localhost:8081) | Administración de BD |

**Credenciales phpMyAdmin**
- Servidor: `db`
- Usuario: `app`
- Contraseña: `app`

---

## 🔁 Migraciones automáticas

El servicio `migrate` aplica los scripts SQL de la carpeta `migrations/` en cada despliegue.  
Ejemplo:
```sql
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS celular VARCHAR(20) AFTER correo;
```

Esto garantiza que la estructura de la BD se mantenga sincronizada sin perder datos existentes.

---

## 🧮 Estructura de la tabla `mensajes`
```sql
CREATE TABLE mensajes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(180) NOT NULL,
  celular VARCHAR(20),
  mensaje TEXT NOT NULL,
  fecha DATETIME NOT NULL
);
```

---

## 💡 Características
- CRUD completo con edición en modal (Alpine.js).
- Campo *celular* con enlace directo a **WhatsApp** y **tel:**
- Notificaciones de éxito/error.
- Modo oscuro automático o manual.
- Seguridad básica con token **CSRF**.
- Scripts SQL idempotentes (`IF NOT EXISTS`).

---

## 🖼️ Capturas de Pantalla

### 💡 Interfaz principal (modo claro)
<p align="center">
  <img src="./assets/app-light.png" alt="Aplicación CRUD modo claro" width="80%">
</p>

### 🌙 Modo oscuro
<p align="center">
  <img src="./assets/app-dark.png" alt="Aplicación CRUD modo oscuro" width="80%">
</p>

### 🧰 Panel phpMyAdmin
<p align="center">
  <img src="./assets/phpmyadmin.png" alt="Panel phpMyAdmin" width="80%">
</p>



## 📜 Licencia
Proyecto de práctica - uso educativo libre.

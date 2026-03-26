# 🎬 VIDTREO for Moodle

![VIDTREO + Moodle](./assets/banner-v3b.png)

**Video recording for Moodle assignments. Record, submit, grade — without leaving Moodle.**

Students record video directly inside assignment submissions using VIDTREO Recorder. Teachers review recordings with VIDTREO Player in the grading view. Moodle handles the academic context. VIDTREO handles the video infrastructure.

No file uploads. No plugins to install on student devices. No downloads. Just click record.

---

## ⚡ What it does

- 🎥 **VIDTREO Recorder** embedded in the assignment submission form
- ▶️ **VIDTREO Player** embedded in the teacher's grading view
- 📱 Works on desktop and mobile browsers
- 🌐 Multilingual UI (English, Spanish — more coming)
- 🔒 GDPR-ready with full Moodle Privacy API implementation
- 💾 Automatic backup/restore support for course migrations

---

## 🎓 How it works

### Student submits a video

```
📝 Open assignment → 🎥 Record video → ☁️ Auto-upload → ✅ Submit
```

1. Student opens an assignment with VIDTREO enabled
2. VIDTREO Recorder appears inside the submission form
3. Student records from camera or screen — with pause, mute, and device switching
4. Video uploads automatically to VIDTREO Edge API (browser-native transcoding, no server relay)
5. Student clicks submit — Moodle saves the recording reference

### Teacher grades the video

```
📋 Open grading → ▶️ Watch video → ✏️ Grade
```

1. Teacher opens the submission in Moodle's grading view
2. VIDTREO Player loads inline — no external tabs, no downloads
3. Teacher watches, grades, and moves on

### Architecture split

Moodle stores **submission metadata** (recording ID, duration, status). VIDTREO stores **the actual video** on Cloudflare's global edge network. This keeps your Moodle server lean — no video files eating disk space.

---

## 🚀 Installation

### Step 1: Install the plugin

Copy the plugin folder into your Moodle installation:

```
{moodle_root}/mod/assign/submission/vidtreo/
```

Or upload the `.zip` file through **Site administration → Plugins → Install plugins**.

### Step 2: Complete setup

1. Visit Moodle as admin — the installation wizard runs automatically
2. Go to **Site administration → Plugins → Assignment submissions → VIDTREO Recorder**
3. Configure your settings:

| Setting | Description | Default |
|---------|-------------|---------|
| 🔑 **API Key** | Your VIDTREO API key ([get one free](https://app.vidtreo.com)) | — |
| 🌐 **Backend URL** | VIDTREO Edge API endpoint | `https://core.vidtreo.com` |
| 📦 **Recorder CDN URL** | Web Component source | `https://cdn.jsdelivr.net/npm/@vidtreo/recorder-wc@latest/dist/vidtreo-recorder.js` |
| 📦 **Player CDN URL** | Player Web Component source | jsDelivr CDN |
| ⏱️ **Max recording time** | Default limit in seconds | `300` (5 min) |
| 🔄 **Source switching** | Allow camera ↔ screen toggle | ✅ Enabled |
| ⏸️ **Pause** | Allow pause/resume during recording | ✅ Enabled |

### Step 3: Enable on an assignment

1. Create or edit an assignment
2. Under **Submission types**, check **VIDTREO Recorder**
3. Optionally override max recording time, source switching, and pause per assignment
4. Save — students can now record video submissions

---

## 🐳 Development with Docker

### Quick start

```bash
# Levantar el entorno completo
docker-compose up -d

# Acceder a Moodle
http://localhost:8080

# Ver emails capturados (MailHog)
http://localhost:8025
```

### Servicios incluidos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **Moodle** | 8080 | Aplicación principal |
| **MariaDB** | 3307 | Base de datos |
| **MailHog** | 8025 (web), 1025 (SMTP) | Servidor SMTP de prueba |

### MailHog - Servidor de email para desarrollo

MailHog captura todos los emails enviados por Moodle sin enviarlos realmente. Perfecto para:

- ✅ Probar notificaciones de entrega de tareas
- ✅ Ver emails de confirmación a estudiantes
- ✅ Verificar formato y contenido de mensajes
- ✅ No necesita configuración SMTP real

**Interfaz web:** http://localhost:8025

### Configuración automática

El archivo `docker-config.php` configura automáticamente:
- Conexión a base de datos
- Servidor SMTP (MailHog)
- Modo debug para desarrollo
- Dirección de email no-reply

### Comandos útiles

```bash
# Ver logs de Moodle
docker logs -f moodle_app

# Ver logs de la base de datos
docker logs -f moodle_db

# Reiniciar servicios
docker-compose restart

# Detener todo
docker-compose down

# Limpiar todo (incluyendo datos)
docker-compose down -v
```

---

## 📁 Plugin structure

```
moodle-assignsubmission-vidtreo/
├── version.php                  # v1.0.0 — Moodle 4.4+
├── locallib.php                 # 🧠 Core plugin logic (recording + playback)
├── settings.php                 # ⚙️ Admin settings (API key, URLs, defaults)
├── lib.php                      # Moodle hooks
├── styles.css                   # Plugin styles
├── thirdpartylibs.xml           # External dependency declaration
│
├── amd/src/
│   ├── recorder.js              # 🎥 Loads <vidtreo-recorder> Web Component
│   └── player.js                # ▶️ Loads <vidtreo-player> Web Component
│
├── templates/
│   ├── recorder.mustache        # Recorder mount point + hidden fields
│   └── player.mustache          # Player mount point for grading
│
├── db/
│   ├── install.xml              # Database schema (assignsubmission_vidtreo)
│   └── access.php               # Capability definitions
│
├── classes/
│   ├── event/                   # Moodle events (submission_created, submission_updated)
│   └── privacy/
│       └── provider.php         # 🔒 GDPR: metadata declaration, export, deletion
│
├── backup/moodle2/              # Course backup/restore support
├── tests/                       # PHPUnit tests
│
└── lang/
    ├── en/                      # 🇬🇧 English strings
    └── es/                      # 🇪🇸 Spanish strings
```

---

## 🔐 Data and privacy

| What | Where | Details |
|------|-------|---------|
| Recording ID, duration, status | **Moodle database** | `assignsubmission_vidtreo` table |
| Video files | **VIDTREO cloud** | Cloudflare R2 storage, encrypted at rest |
| Privacy API | **Fully implemented** | Export and deletion hooks for GDPR compliance |

The Privacy API declares the external system (VIDTREO cloud) and what data is sent. Deleting from Moodle removes Moodle-side records. Video files on VIDTREO infrastructure are managed through the [VIDTREO Dashboard](https://app.vidtreo.com).

---

## 🏗️ Part of the VIDTREO Platform

This plugin is the first entry in VIDTREO's **integrations** product line — bringing video recording into the platforms where people already work.

```
VIDTREO Platform
├── VIDTREO Recorder        → Capture (browser-native recording + transcoding)
├── VIDTREO Edge API        → Process + Store + Manage (Cloudflare edge network)
├── VIDTREO AI              → Understand (transcription, summaries, key moments)
├── VIDTREO Player          → Deliver (playback component)
│
└── 🔌 VIDTREO Integrations → Connect
    └── ✅ Moodle           → This plugin
    └── 🔜 Canvas LMS
    └── 🔜 Google Classroom
    └── 🔜 WordPress
```

**Why integrations matter:** Video recording shouldn't require students or teachers to leave their LMS. The best video infrastructure is the one you don't notice — it just works where you already are.

---

## 🔧 Requirements

- **Moodle 4.4+** (version 2024042200)
- A **VIDTREO account** with an API key — [sign up free](https://app.vidtreo.com)
- Modern browser: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## 📄 License

GNU General Public License v3 or later (GPL-3.0-or-later).

This plugin is open source. Study it, modify it, distribute it — under the terms of the GPL.

The VIDTREO Recorder and Player Web Components loaded from CDN are proprietary and require a valid VIDTREO API key.

---

**Built by [VIDTREO](https://vidtreo.com)** · Video recording for the modern web · [$0.01/minute](https://vidtreo.com/pricing)

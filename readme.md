## Project Management

A full-featured project management application built with Laravel + Inertia.js + Vue 3.

### Features

- **Project Management** — Create, manage, and track projects with custom statuses, priorities, and roles
- **Task Management** — Kanban board, backlog, sprints, subtasks, epics, and table view
- **Sprint Planning** — Sprint creation, backlog grooming, sprint reports, and burndown charts
- **Team & Members** — Role-based access control, team management, and member assignments
- **Workload Management** — Visual workload overview per user across projects
- **Reports & Analytics** — Task reports, sprint reports, project reports with filtering
- **Comments & Reactions** — Threaded comments with emoji reactions and mentions
- **Activity Log** — Full audit trail of all project and task changes
- **Media Library** — File attachments with image cropping and video playback
- **Notifications** — Real-time notifications for task assignments and updates
- **Tags & Categories** — Flexible tagging and categorization system
- **Gantt Chart** — Visual project timeline with Gantt chart view
- **Import/Export** — Excel import/export for projects and tasks

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.3, MySQL |
| Frontend | Vue 3, TypeScript, Inertia.js (SPA) |
| UI | PrimeVue 4, TailwindCSS, Radix Vue, Headless UI |
| Charts | Highcharts, Highcharts Gantt |
| Maps | Leaflet, Vue Leaflet |
| Media | Video.js, Vue Advanced Cropper |
| Editor | Quill rich text editor |
| Icons | Lucide, PrimeIcons |
| Build | Vite, ESLint, Prettier, Sass |
| Auth | Laravel authentication (session-based) |
| Storage | Redis (cache/queue), Media Library |

### Requirements

- PHP 8.3
- NodeJS 22
- Composer 2
- Apache 2
- Redis
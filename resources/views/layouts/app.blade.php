<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'IMNA IT Asset Manager') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1B1F23;
            --steel: #5B6670;
            --steel-line: rgba(27, 31, 35, 0.10);
            --paper: #F1F3F1;
            --surface: #FFFFFF;
            --teal: #0E7C86;
            --teal-dark: #0A5F67;
            --ochre: #B5741F;
            --ochre-dark: #8F5A16;
            --brick: #AF402C;
            --brick-dark: #8D3323;
            --moss: #3F7D5C;
            --moss-dark: #326349;
            --radius: 6px;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, sans-serif;
            background-color: var(--paper) !important;
            color: var(--ink);
            min-height: 100vh;
        }

        code, .font-mono, .badge, .text-monospace,
        td:has(> strong), .card-header, th {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
        }

        h1, h2, h3, h4, h5, .h3, .h4, .h5 {
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--ink);
        }

        a { color: var(--teal); }
        a:hover { color: var(--teal-dark); }

        /* ---------- Console header ---------- */
        .imna-header {
            background-color: var(--ink);
            border-bottom: 3px solid var(--teal);
        }
        .imna-brand {
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: #fff;
            letter-spacing: -0.01em;
            text-decoration: none;
        }
        .imna-brand span {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 400;
            font-size: 0.8rem;
            color: #9FB0B3;
            margin-left: 0.5rem;
        }
        .imna-role-tag {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            font-weight: 500;
            padding: 0.25rem 0.55rem;
            border-radius: var(--radius);
            background: rgba(14, 124, 134, 0.25);
            color: #6FD3DB;
            border: 1px solid rgba(111, 211, 219, 0.3);
        }
        .imna-user { color: #D7DBDC; font-size: 0.9rem; }

        /* ---------- Notification bell ---------- */
        .imna-bell {
            position: relative;
            background: transparent;
            border: none;
            color: #D7DBDC;
            padding: 0.4rem;
            border-radius: var(--radius);
        }
        .imna-bell:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .imna-bell .dot {
            position: absolute;
            top: 2px; right: 2px;
            width: 9px; height: 9px;
            background: var(--ochre);
            border: 2px solid var(--ink);
            border-radius: 50%;
        }
        .imna-notif-panel {
            width: 340px;
            max-height: 420px;
            overflow-y: auto;
            padding: 0;
            border: 1px solid var(--steel-line);
            border-radius: var(--radius);
            box-shadow: 0 8px 24px rgba(27,31,35,0.14);
        }
        .imna-notif-panel .dropdown-header {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            color: var(--steel);
            padding: 0.65rem 0.9rem;
            border-bottom: 1px solid var(--steel-line);
        }
        .notif-item {
            display: block;
            width: 100%;
            text-align: left;
            background: #fff;
            border: none;
            border-bottom: 1px solid var(--steel-line);
            padding: 0.65rem 0.9rem;
        }
        .notif-item:hover { background: var(--paper); }
        .notif-titre { display: block; font-weight: 600; font-size: 0.85rem; color: var(--ink); }
        .notif-message { display: block; font-size: 0.8rem; color: var(--steel); margin-top: 2px; }
        .notif-time { display: block; font-family: 'IBM Plex Mono', monospace; font-size: 0.7rem; color: var(--teal); margin-top: 4px; }
        .imna-notif-empty { padding: 1.5rem 0.9rem; text-align: center; color: var(--steel); font-size: 0.85rem; }

        /* ---------- Tabs: flat underline, not browser-tab ---------- */
        .nav-tabs { border-bottom: 1px solid var(--steel-line); }
        .nav-tabs .nav-link {
            background: transparent;
            border: none;
            border-radius: 0;
            color: var(--steel);
            font-weight: 600;
            font-size: 0.92rem;
            padding: 0.75rem 0.25rem;
            margin-right: 1.75rem;
            border-bottom: 2px solid transparent;
            transition: color .15s ease, border-color .15s ease;
        }
        .nav-tabs .nav-link:hover { color: var(--ink); background: transparent; }
        .nav-tabs .nav-link.active {
            color: var(--ink) !important;
            background: transparent !important;
            border-bottom: 2px solid var(--teal) !important;
            box-shadow: none;
        }

        /* ---------- Cards: hairline, not shadow-soup ---------- */
        .card {
            border: 1px solid var(--steel-line);
            border-radius: var(--radius);
            box-shadow: none;
        }
        .card-header {
            font-size: 0.8rem;
            letter-spacing: 0.02em;
            font-weight: 600;
            border-bottom: 1px solid var(--steel-line);
        }

        /* ---------- Buttons ---------- */
        .btn { border-radius: var(--radius); font-weight: 600; font-size: 0.88rem; }
        .btn-primary { background-color: var(--teal); border-color: var(--teal); }
        .btn-primary:hover, .btn-primary:focus { background-color: var(--teal-dark); border-color: var(--teal-dark); }
        .btn-success { background-color: var(--moss); border-color: var(--moss); }
        .btn-success:hover { background-color: var(--moss-dark); border-color: var(--moss-dark); }
        .btn-danger, .btn-outline-danger:hover { background-color: var(--brick); border-color: var(--brick); }
        .btn-outline-danger { color: var(--brick); border-color: var(--brick); }
        .btn-warning { background-color: var(--ochre); border-color: var(--ochre); color: #fff; }
        .btn-secondary { background-color: var(--steel); border-color: var(--steel); }
        .btn:focus-visible, a:focus-visible, .form-select:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 2px;
        }

        /* ---------- Badges: status tags, not pills ---------- */
        .badge {
            font-weight: 500;
            font-size: 0.72rem;
            border-radius: 4px;
            padding: 0.35em 0.55em;
        }
        .bg-primary   { background-color: var(--teal) !important; }
        .bg-success   { background-color: var(--moss) !important; }
        .bg-danger    { background-color: var(--brick) !important; }
        .bg-warning   { background-color: var(--ochre) !important; color: #fff !important; }
        .bg-dark      { background-color: var(--ink) !important; }
        .bg-secondary { background-color: var(--steel) !important; }
        .bg-info      { background-color: #3D7A9E !important; }
        .text-primary   { color: var(--teal) !important; }
        .text-success   { color: var(--moss) !important; }
        .border-primary { border-color: var(--teal) !important; }
        .border-danger  { border-color: var(--brick) !important; }
        .border-dark    { border-color: var(--ink) !important; }
        .border-warning { border-color: var(--ochre) !important; }

        /* ---------- Alerts ---------- */
        .alert-success { background-color: #E9F3EE; border-color: #C7E1D3; color: var(--moss-dark); }
        .alert-info    { background-color: #E9F1F5; border-color: #C6DCE6; color: #2E5E78; }

        /* ---------- Tables ---------- */
        .table thead.table-light th {
            background-color: var(--paper);
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--steel);
            letter-spacing: 0.02em;
            border-bottom: 1px solid var(--steel-line);
        }
        .table td, .table th { border-color: var(--steel-line); font-size: 0.9rem; }

        /* ---------- Forms ---------- */
        .form-control, .form-select {
            border-color: var(--steel-line);
            border-radius: var(--radius);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(14, 124, 134, 0.15);
        }
        .form-label { font-size: 0.85rem; color: var(--steel); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg imna-header">
        <div class="container">
            <a class="imna-brand" href="#">IMNA<span>IT Asset Manager</span></a>
            @auth
                <div class="d-flex align-items-center gap-3">
                    <span class="imna-user">{{ Auth::user()->prenom }} {{ Auth::user()->nom }}</span>
                    <span class="imna-role-tag">{{ Auth::user()->role }}</span>

                    <div class="dropdown">
                        @php $unread = Auth::user()->unreadNotifications; @endphp
                        <button class="imna-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 8a6 6 0 0 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6Z"/>
                                <path d="M9.5 18a2.5 2.5 0 0 0 5 0"/>
                            </svg>
                            @if($unread->count() > 0)
                                <span class="dot"></span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end imna-notif-panel">
                            <div class="dropdown-header">
                                {{ $unread->count() }} notification(s) non lue(s)
                            </div>
                            @forelse($unread as $notification)
                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="notif-item">
                                        <span class="notif-titre">{{ $notification->data['titre'] ?? 'Notification' }}</span>
                                        <span class="notif-message">{{ $notification->data['message'] ?? '' }}</span>
                                        <span class="notif-time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </button>
                                </form>
                            @empty
                                <div class="imna-notif-empty">Rien à signaler pour le moment.</div>
                            @endforelse
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Déconnexion</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <div class="container py-4">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart CDS — Clinical Decision Support</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        /* ===== CSS Custom Properties ===== */
        :root {
            --primary: #1A2B4A;
            --primary-light: #2A3F6A;
            --primary-dark: #0F1B2D;
            --white: #FFFFFF;
            --bg: #F0F4F8;
            --bg-card: rgba(255,255,255,0.85);
            --risk-low: #2ECC71;
            --risk-low-bg: rgba(46,204,113,0.12);
            --risk-medium: #F39C12;
            --risk-medium-bg: rgba(243,156,18,0.12);
            --risk-high: #E74C3C;
            --risk-high-bg: rgba(231,76,60,0.12);
            --accent: #3498DB;
            --accent-light: rgba(52,152,219,0.12);
            --sidebar-bg: #0F1B2D;
            --sidebar-width: 260px;
            --header-height: 64px;
            --text-primary: #1A2B4A;
            --text-secondary: #5A6B8A;
            --text-muted: #8A9BBF;
            --border: rgba(26,43,74,0.08);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 32px rgba(0,0,0,0.1);
            --shadow-lg: 0 16px 48px rgba(0,0,0,0.14);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            --glass-bg: rgba(255,255,255,0.72);
            --glass-border: rgba(255,255,255,0.25);
            --glass-shadow: 0 8px 32px rgba(15,27,45,0.10);
        }

        /* ===== Reset & Base ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; }
        button { cursor: pointer; font-family: inherit; }
        input, select, textarea { font-family: inherit; }

        /* ===== Keyframe Animations ===== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-32px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(32px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(100%) scale(0.9); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0) scale(1); }
            to { opacity: 0; transform: translateX(100%) scale(0.9); }
        }
        @keyframes countUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes borderPulse {
            0%, 100% { border-color: rgba(52,152,219,0.3); }
            50% { border-color: rgba(52,152,219,0.7); }
        }

        /* ===== Sidebar ===== */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #0F1B2D 0%, #162340 50%, #0F1B2D 100%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            overflow: hidden;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse at top left, rgba(52,152,219,0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .sidebar-brand {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            position: relative;
        }
        .sidebar-brand-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), #2ECC71);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(52,152,219,0.3);
        }
        .sidebar-brand-text {
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand-text h1 {
            color: var(--white);
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .sidebar-brand-text span {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
        }
        .sidebar-nav-label {
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 12px 16px 8px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            color: rgba(255,255,255,0.55);
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--accent);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: var(--transition);
        }
        .sidebar-nav a:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.06);
        }
        .sidebar-nav a.active {
            color: var(--white);
            background: linear-gradient(90deg, rgba(52,152,219,0.18), rgba(52,152,219,0.06));
        }
        .sidebar-nav a.active::before {
            transform: scaleY(1);
        }
        .sidebar-nav a .nav-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 18px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.04);
            transition: var(--transition);
        }
        .sidebar-nav a:hover .nav-icon,
        .sidebar-nav a.active .nav-icon {
            background: rgba(52,152,219,0.15);
        }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.3);
            font-size: 11px;
            text-align: center;
        }

        /* ===== Top Header ===== */
        .header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            z-index: 900;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            transition: var(--transition);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .hamburger {
            display: none;
            background: none;
            border: none;
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .hamburger:hover { background: var(--accent-light); }
        .hamburger svg { width: 22px; height: 22px; stroke: var(--text-primary); }
        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: var(--accent-light);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent);
        }
        .header-badge .dot {
            width: 8px; height: 8px;
            background: var(--risk-low);
            border-radius: 50%;
            animation: pulse 2s ease infinite;
        }

        /* ===== Main Content ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 32px;
            min-height: calc(100vh - var(--header-height));
            transition: var(--transition);
            animation: fadeIn 0.4s ease;
        }

        /* ===== Card System ===== */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow);
            padding: 24px;
            transition: var(--transition);
            animation: fadeInUp 0.5s ease both;
        }
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title .icon {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: var(--accent-light);
        }

        /* ===== Badges ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: capitalize;
        }
        .badge-low, .badge-Low {
            background: var(--risk-low-bg);
            color: #1B9E52;
        }
        .badge-medium, .badge-Medium, .badge-moderate, .badge-Moderate {
            background: var(--risk-medium-bg);
            color: #C07E0A;
        }
        .badge-high, .badge-High, .badge-critical, .badge-Critical {
            background: var(--risk-high-bg);
            color: #C0392B;
        }
        .badge-info {
            background: var(--accent-light);
            color: var(--accent);
        }
        .badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* ===== Tables ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-md);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }
        thead th {
            background: rgba(26,43,74,0.04);
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }
        tbody tr {
            transition: var(--transition);
        }
        tbody tr:hover {
            background: rgba(52,152,219,0.03);
        }
        tbody tr:last-child td {
            border-bottom: none;
        }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.15);
            opacity: 0;
            transition: var(--transition);
        }
        .btn:hover::after { opacity: 1; }
        .btn:active { transform: scale(0.97); }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            box-shadow: 0 4px 16px rgba(26,43,74,0.25);
        }
        .btn-primary:hover {
            box-shadow: 0 6px 24px rgba(26,43,74,0.35);
            transform: translateY(-1px);
        }
        .btn-accent {
            background: linear-gradient(135deg, var(--accent), #2980B9);
            color: var(--white);
            box-shadow: 0 4px 16px rgba(52,152,219,0.25);
        }
        .btn-accent:hover {
            box-shadow: 0 6px 24px rgba(52,152,219,0.35);
            transform: translateY(-1px);
        }
        .btn-success {
            background: linear-gradient(135deg, var(--risk-low), #27AE60);
            color: var(--white);
            box-shadow: 0 4px 16px rgba(46,204,113,0.25);
        }
        .btn-danger {
            background: linear-gradient(135deg, var(--risk-high), #C0392B);
            color: var(--white);
            box-shadow: 0 4px 16px rgba(231,76,60,0.25);
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--border);
            color: var(--text-primary);
        }
        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-light);
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 6px;
        }
        .btn-lg {
            padding: 14px 28px;
            font-size: 16px;
            border-radius: var(--radius-md);
        }
        .btn-icon {
            width: 36px; height: 36px;
            padding: 0;
            border-radius: var(--radius-sm);
        }

        /* ===== Forms ===== */
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--text-primary);
            background: var(--white);
            transition: var(--transition);
            outline: none;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
        }
        .form-input::placeholder { color: var(--text-muted); }
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        .search-box {
            position: relative;
        }
        .search-box .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            pointer-events: none;
        }
        .search-box .form-input {
            padding-left: 44px;
        }

        /* ===== Modals ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,27,45,0.5);
            backdrop-filter: blur(6px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 32px;
            max-width: 560px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease;
        }

        /* ===== Loading Spinner ===== */
        .spinner-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,27,45,0.25);
            backdrop-filter: blur(4px);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }
        .spinner-overlay.active { display: flex; }
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid rgba(52,152,219,0.15);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .spinner-text {
            color: var(--white);
            font-size: 14px;
            font-weight: 500;
        }
        .spinner-inline {
            width: 20px; height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: inline-block;
        }

        /* ===== Skeleton Loading ===== */
        .skeleton {
            background: linear-gradient(90deg, rgba(26,43,74,0.06) 25%, rgba(26,43,74,0.12) 50%, rgba(26,43,74,0.06) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s ease infinite;
            border-radius: var(--radius-sm);
        }
        .skeleton-line {
            height: 16px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .skeleton-line:last-child { width: 70%; }
        .skeleton-card {
            height: 120px;
            border-radius: var(--radius-lg);
        }
        .skeleton-row {
            height: 52px;
            margin-bottom: 4px;
        }

        /* ===== Toast Notifications ===== */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 5000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
        }
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            background: var(--white);
            box-shadow: var(--shadow-lg);
            border-left: 4px solid;
            animation: toastIn 0.4s cubic-bezier(0.4,0,0.2,1) both;
            position: relative;
            overflow: hidden;
        }
        .toast.removing { animation: toastOut 0.3s ease both; }
        .toast-success { border-color: var(--risk-low); }
        .toast-error { border-color: var(--risk-high); }
        .toast-warning { border-color: var(--risk-medium); }
        .toast-info { border-color: var(--accent); }
        .toast-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
        .toast-body { flex: 1; }
        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .toast-message {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .toast-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            padding: 0;
            cursor: pointer;
            transition: var(--transition);
        }
        .toast-close:hover { color: var(--text-primary); }
        .toast-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            background: currentColor;
            opacity: 0.3;
            transition: width linear;
        }

        /* ===== Page Header ===== */
        .page-header {
            margin-bottom: 28px;
            animation: fadeInDown 0.4s ease;
        }
        .page-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        .page-header p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 4px;
        }
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        /* ===== Grid System ===== */
        .grid {
            display: grid;
            gap: 24px;
        }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        .flex-row {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .flex-wrap { flex-wrap: wrap; }

        /* ===== Empty State ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state h3 { font-size: 18px; color: var(--text-secondary); margin-bottom: 8px; }
        .empty-state p { font-size: 14px; }

        /* ===== Pagination ===== */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
        }
        .pagination button {
            width: 36px; height: 36px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--white);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .pagination button:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        .pagination button.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }
        .pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* ===== Mobile Overlay ===== */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 999;
            display: none;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1200px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-overlay.active { display: block; }
            .header {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 20px 16px;
            }
            .hamburger { display: flex; }
            .grid-2, .grid-3, .grid-4 {
                grid-template-columns: 1fr;
            }
            .page-header h2 { font-size: 22px; }
            .page-header-row {
                flex-direction: column;
                align-items: stretch;
            }
            .card { padding: 18px; }
        }
        @media (max-width: 480px) {
            .main-content { padding: 16px 12px; }
            .header { padding: 0 16px; }
            .btn { padding: 8px 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">✚</div>
        <div class="sidebar-brand-text">
            <h1>Smart CDS</h1>
            <span>Clinical Decision Support</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Main Menu</div>
        <a href="?page=dashboard" class="<?= ($page ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>
        <a href="?page=patients" class="<?= ($page ?? '') === 'patients' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            Patients
        </a>
        <a href="?page=diagnosis" class="<?= ($page ?? '') === 'diagnosis' ? 'active' : '' ?>">
            <span class="nav-icon">🩺</span>
            Diagnosis
        </a>
        <div class="sidebar-nav-label">Analytics</div>
        <a href="?page=inference" class="<?= ($page ?? '') === 'inference' ? 'active' : '' ?>">
            <span class="nav-icon">🧠</span>
            Inference Results
        </a>
        <a href="?page=sparql" class="<?= ($page ?? '') === 'sparql' ? 'active' : '' ?>">
            <span class="nav-icon">🔍</span>
            SPARQL Explorer
        </a>
    </nav>
    <div class="sidebar-footer">
        &copy; <?= date('Y') ?> Smart CDS v2.0
    </div>
</aside>

<!-- Top Header -->
<header class="header" id="header">
    <div class="header-left">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <span class="header-title">
            <?php
                $titles = [
                    'dashboard' => 'Dashboard',
                    'patients' => 'Patient Management',
                    'patient_detail' => 'Patient Detail',
                    'diagnosis' => 'Clinical Diagnosis',
                    'inference' => 'Inference Engine',
                    'sparql' => 'SPARQL Explorer'
                ];
                echo $titles[$page ?? 'dashboard'] ?? 'Smart CDS';
            ?>
        </span>
    </div>
    <div class="header-right">
        <div class="header-badge">
            <span class="dot"></span>
            System Online
        </div>
    </div>
</header>

<!-- Loading Spinner -->
<div class="spinner-overlay" id="globalSpinner">
    <div class="spinner"></div>
    <div class="spinner-text" id="spinnerText">Processing...</div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Connection Error Modal -->
<div class="modal-overlay" id="connectionErrorModal" style="display:none; align-items:center; justify-content:center;">
    <div class="modal" style="background:var(--white); border-radius:var(--radius-xl); padding:32px; max-width:500px; width:90%; box-shadow:var(--shadow-xl);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; color:var(--danger); display:flex; align-items:center; gap:10px;"><span style="font-size:24px;">⚠️</span> Database Connection Failed</h3>
            <button onclick="document.getElementById('connectionErrorModal').style.display='none'" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <p style="margin-bottom:12px; line-height:1.6;">The system was unable to connect to the Apache Jena Fuseki database.</p>
        <p style="margin-bottom:24px; color:var(--text-muted); font-size:14px; line-height:1.6;">Please make sure the <code>fuseki-server.bat</code> is running and the database is online on port 3030.</p>
        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <button onclick="document.getElementById('connectionErrorModal').style.display='none'" class="btn btn-outline">Dismiss</button>
            <button onclick="window.location.reload()" class="btn btn-primary">Retry Connection</button>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="main-content" id="mainContent">
    <?php
        $page = $page ?? 'dashboard';
        $viewFile = __DIR__ . '/' . match($page) {
            'dashboard' => 'dashboard.php',
            'patients' => 'patient_list.php',
            'patient_detail' => 'patient_detail.php',
            'diagnosis' => 'diagnosis.php',
            'inference' => 'inference_results.php',
            'sparql' => 'sparql_query.php',
            default => 'dashboard.php'
        };
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<div class="empty-state"><div class="icon">📄</div><h3>Page Not Found</h3><p>The requested view could not be loaded.</p></div>';
        }
    ?>
</main>

<script>
/* ===== Global JavaScript ===== */

// --- Sidebar Toggle ---
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

// --- Toast Notifications ---
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toastContainer');
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="removeToast(this.parentElement)">&times;</button>
        <div class="toast-progress" style="width:100%"></div>
    `;
    container.appendChild(toast);

    const progress = toast.querySelector('.toast-progress');
    progress.style.transitionDuration = duration + 'ms';
    requestAnimationFrame(() => { progress.style.width = '0%'; });

    setTimeout(() => removeToast(toast), duration);
}

function removeToast(toast) {
    if (!toast || toast.classList.contains('removing')) return;
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 300);
}

// --- Loading Spinner ---
function showSpinner(text = 'Processing...') {
    document.getElementById('spinnerText').textContent = text;
    document.getElementById('globalSpinner').classList.add('active');
}
function hideSpinner() {
    document.getElementById('globalSpinner').classList.remove('active');
}

// --- API Fetch Wrapper ---
async function apiFetch(action, options = {}) {
    const { method = 'GET', body = null, params = {}, spinnerText = 'Loading...' } = options;

    let url = `api/api.php?action=${encodeURIComponent(action)}`;
    Object.entries(params).forEach(([key, val]) => {
        url += `&${encodeURIComponent(key)}=${encodeURIComponent(val)}`;
    });

    const fetchOptions = {
        method,
        headers: {}
    };

    if (body) {
        if (typeof body === 'string') {
            fetchOptions.body = body;
            fetchOptions.headers['Content-Type'] = 'text/plain';
        } else {
            fetchOptions.body = JSON.stringify(body);
            fetchOptions.headers['Content-Type'] = 'application/json';
        }
    }

    try {
        const response = await fetch(url, fetchOptions);
        
        let data;
        try {
            data = await response.json();
        } catch (e) {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            throw new Error('Invalid JSON response from API');
        }

        // Check if API returned a cURL connection error (Fuseki is down)
        if (data && data.success === false && data.error && (data.error.includes('Connection refused') || data.error.includes('Failed to connect'))) {
            throw new Error('DATABASE_CONNECTION_FAILED');
        }

        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}: ${response.statusText}`);
        }

        return data;
    } catch (err) {
        console.error('API Error:', err);
        
        if (err.message === 'DATABASE_CONNECTION_FAILED' || err.message.includes('Failed to fetch') || err.message.includes('NetworkError')) {
            // Show modal
            document.getElementById('connectionErrorModal').style.display = 'flex';
            
            // Update system status indicator to red
            const indicator = document.querySelector('.status-indicator');
            if (indicator) {
                indicator.style.background = 'var(--danger)';
                indicator.nextSibling.textContent = ' System Offline';
            }
        } else {
            showToast('error', 'API Error', err.message || 'An unexpected error occurred.');
        }
        
        throw err;
    }
}

// --- Number Counter Animation ---
function animateCounter(element, target, duration = 1200) {
    const start = 0;
    const startTime = performance.now();
    target = parseInt(target) || 0;

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (target - start) * eased);
        element.textContent = current.toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

// --- Risk Badge Helper ---
function riskBadge(level) {
    if (!level) return '<span class="badge badge-info">N/A</span>';
    const l = level.toString().toLowerCase();
    let cls = 'info';
    if (l === 'low') cls = 'low';
    else if (l === 'medium' || l === 'moderate') cls = 'medium';
    else if (l === 'high' || l === 'critical') cls = 'high';
    return `<span class="badge badge-${cls}">${level}</span>`;
}

// --- Debounce ---
function debounce(func, wait = 300) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// --- Format Date ---
function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return dateStr;
    }
}

// --- Escape HTML ---
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
</body>
</html>

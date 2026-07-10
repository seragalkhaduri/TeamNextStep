<?php
    $locale = session('locale', 'en');
    $isAr = ($locale === 'ar');

    $t = function($en, $ar) use ($isAr) {
        return $isAr ? $ar : $en;
    };

    $totalStudents   = \App\Domain\Students\Models\Student::count();
    $totalEmployees  = \App\Domain\Employees\Models\Employee::count();
    $totalFaculties  = \App\Domain\Organization\Models\Faculty::count();
    $totalRooms      = \App\Domain\Facilities\Models\Room::count();

    // RGMS stats
    try {
        $totalResearchGroups = \App\Domain\ResearchGroups\Models\ResearchGroup::count();
        $totalProjects       = \App\Domain\ResearchGroups\Models\ResearchProject::count();
        $totalPublications   = \App\Domain\ResearchGroups\Models\Publication::count();
        $totalPatents        = \App\Domain\ResearchGroups\Models\Patent::count();
    } catch (\Exception $e) {
        $totalResearchGroups = 0;
        $totalProjects = 0;
        $totalPublications = 0;
        $totalPatents = 0;
    }

    $students  = \App\Domain\Students\Models\Student::with('programs')->get();
    $employees = \App\Domain\Employees\Models\Employee::with('departments')->get();
    $faculties = \App\Domain\Organization\Models\Faculty::with('departments.programs')->get();
    $campuses  = \App\Domain\Facilities\Models\Campus::with('buildings.rooms')->get();
    $allUsers = collect();
    if (auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])) {
        $allUsers = \App\Domain\Auth\Models\User::with('roles')->get();
    }

    try {
        $researchGroups = \App\Domain\ResearchGroups\Models\ResearchGroup::with('groupMemberships')->get();
        $projects       = \App\Domain\ResearchGroups\Models\ResearchProject::with(['researchGroup', 'projectContributors'])->get();
        $publications   = \App\Domain\ResearchGroups\Models\Publication::with('publicationAuthors')->get();
        $allMemberships = \App\Domain\ResearchGroups\Models\GroupMembership::with('researchGroup')->get();
        $allPubAuthors  = \App\Domain\ResearchGroups\Models\PublicationAuthor::all();
        $allContributors = \App\Domain\ResearchGroups\Models\ProjectContributor::all();
    } catch (\Exception $e) {
        $researchGroups = collect();
        $projects = collect();
        $publications = collect();
        $allMemberships = collect();
        $allPubAuthors = collect();
        $allContributors = collect();
    }

    // Pre-cache employees and students by their institutional_id for quick lookups
    $employeeByInstId = $employees->keyBy('institutional_id');
    $studentByInstId  = $students->keyBy('institutional_id');

    $membershipsByMemberId = $allMemberships->groupBy('member_uimp_id');
    $pubAuthorsByPubId     = $allPubAuthors->groupBy('publication_id');
    $contributorsByProjId  = $allContributors->groupBy('project_id');

    $getMemberName = function($uimpId, $type = null) use ($employeeByInstId, $studentByInstId, $t) {
        if (isset($employeeByInstId[$uimpId])) {
            return session('locale') === 'ar' ? $employeeByInstId[$uimpId]->name_ar : $employeeByInstId[$uimpId]->name_en;
        }
        if (isset($studentByInstId[$uimpId])) {
            return session('locale') === 'ar' ? $studentByInstId[$uimpId]->name_ar : $studentByInstId[$uimpId]->name_en;
        }
        return $uimpId;
    };

    $allPrograms    = [];
    $allDepartments = [];
    foreach ($faculties as $fac) {
        foreach ($fac->departments as $dept) {
            $allDepartments[] = $dept;
            foreach ($dept->programs as $prog) {
                $allPrograms[] = $prog;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="<?php echo e($locale); ?>" dir="<?php echo e($isAr ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($t('UIMP + RGMS — Unified University Platform', 'منصة الجامعة الموحدة وإدارة المجموعات البحثية')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 280px;
            --bg: #f8fafc;
            --sidebar: #0f172a;
            --sidebar-hover: rgba(255,255,255,0.06);
            --sidebar-active: linear-gradient(135deg, #6366f1, #4f46e5);
            --accent: #6366f1;
            --accent-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --accent-2: #10b981;
            --accent-3: #f59e0b;
            --accent-4: #ef4444;
            --accent-5: #8b5cf6;
            --white: #ffffff;
            --card: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            --font-display: 'Outfit', 'Inter', system-ui, sans-serif;
            --font-sans: 'Inter', system-ui, sans-serif;
        }

        body {
            font-family: <?php echo e($isAr ? "'Cairo', sans-serif" : "var(--font-sans)"); ?>;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
            font-size: 14px;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0;
            <?php echo e($isAr ? 'right: 0;' : 'left: 0;'); ?>

            overflow-y: auto;
            z-index: 100;
            box-shadow: <?php echo e($isAr ? '-4px' : '4px'); ?> 0 25px rgba(0,0,0,0.15);
        }
        .sidebar-logo {
            padding: 2rem 1.75rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-logo h2 {
            color: #fff;
            font-family: <?php echo e($isAr ? "'Cairo', sans-serif" : "var(--font-display)"); ?>;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-logo span {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        .sidebar-section {
            padding: 1.25rem 1.25rem 0.5rem;
        }
        .sidebar-section-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.25);
            padding: 0 0.75rem;
            margin-bottom: 0.6rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            background: none;
            width: 100%;
            text-align: <?php echo e($isAr ? 'right' : 'left'); ?>;
            margin-bottom: 4px;
            position: relative;
        }
        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(<?php echo e($isAr ? '-3px' : '3px'); ?>);
        }
        .nav-item.active {
            background: var(--sidebar-active);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }
        .nav-item .icon { font-size: 1.15rem; width: 1.3rem; text-align: center; flex-shrink: 0; }
        .nav-badge {
            margin-<?php echo e($isAr ? 'right' : 'left'); ?>: auto;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 99px;
            transition: background 0.2s;
        }
        .nav-item.active .nav-badge {
            background: rgba(255,255,255,0.2);
        }
        .sidebar-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 1rem 1.75rem; }

        /* ── MAIN CONTENT ── */
        .main {
            margin-<?php echo e($isAr ? 'right' : 'left'); ?>: var(--sidebar-w);
            margin-<?php echo e($isAr ? 'left' : 'right'); ?>: 0;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-title {
            font-family: <?php echo e($isAr ? "'Cairo', sans-serif" : "var(--font-display)"); ?>;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.3px;
        }
        .topbar-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
        }
        .topbar-actions { display: flex; gap: 0.75rem; align-items: center; }

        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn:active {
            transform: scale(0.97);
        }
        .btn-primary {
            background: var(--accent-gradient);
            color: #fff;
        }
        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            filter: brightness(1.05);
        }
        .btn-outline {
            background: var(--white);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-outline:hover {
            background: var(--bg);
            border-color: #cbd5e1;
        }
        
        .btn-sm { padding: 5px 10px; font-size: 0.78rem; border-radius: 8px; }
        .btn-green { background: var(--accent-2); color: white; }
        .btn-green:hover { background: #059669; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25); }
        .btn-red { background: var(--accent-4); color: white; }
        .btn-red:hover { background: #dc2626; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25); }

        .content { padding: 2.25rem 2.5rem; flex: 1; }

        /* ── SECTIONS ── */
        .page-section { display: none; }
        .page-section.active { display: block; }

        /* ── STATS GRID ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--card);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--accent-gradient);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #cbd5e1;
        }
        .stat-card:hover::before {
            opacity: 1;
        }
        .stat-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; }
        .stat-value { font-size: 2.25rem; font-weight: 800; color: var(--text); margin-top: 0.4rem; line-height: 1; font-family: var(--font-display); }
        .stat-icon {
            font-size: 1.8rem;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 12px;
            transition: background 0.3s, transform 0.3s;
        }
        .stat-card:hover .stat-icon {
            background: #e2e8f0;
            transform: scale(1.08) rotate(5deg);
        }
        .stat-trend { font-size: 0.72rem; color: var(--accent-2); margin-top: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 3px; }

        /* ── CARD ── */
        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .card-header {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .card-title {
            font-family: <?php echo e($isAr ? "'Cairo', sans-serif" : "var(--font-display)"); ?>;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text);
        }
        .card-subtitle { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
        .card-body { padding: 1.75rem; }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        table.tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        table.tbl th {
            padding: 0.9rem 1.25rem;
            background: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            border-bottom: 2px solid var(--border);
            text-align: <?php echo e($isAr ? 'right' : 'left'); ?>;
            white-space: nowrap;
        }
        table.tbl td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
            vertical-align: middle;
            transition: background-color 0.15s;
            text-align: <?php echo e($isAr ? 'right' : 'left'); ?>;
        }
        table.tbl tbody tr:hover td {
            background-color: #f8fafc;
        }
        table.tbl tr:last-child td { border-bottom: none; }
        .mono { font-family: monospace; font-size: 0.82rem; background: #e2e8f0; color: #334155; padding: 3px 7px; border-radius: 6px; font-weight: 600; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 99px;
            line-height: 1;
        }
        .badge-green  { background: #e6fcf5; color: #087f5b; border: 1px solid #c3fae8; }
        .badge-blue   { background: #e7f5ff; color: #1c7ed6; border: 1px solid #d0ebff; }
        .badge-purple { background: #f3f0ff; color: #7048e8; border: 1px solid #e5dbff; }
        .badge-amber  { background: #fff9db; color: #f08c00; border: 1px solid #fff3bf; }
        .badge-rose   { background: #fff5f5; color: #fa5252; border: 1px solid #ffe3e3; }
        .badge-gray   { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .badge-indigo { background: #edf2ff; color: #4c6ef5; border: 1px solid #dbe4ff; }

        /* ── TABS ── */
        .tabs-bar {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem 0;
            border-bottom: 1px solid var(--border);
            background: var(--card);
            border-radius: 16px 16px 0 0;
            overflow-x: auto;
        }
        .tab-btn {
            padding: 0.75rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--accent); }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── FORMS ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group label { font-size: 0.8rem; font-weight: 700; color: #475569; }
        .form-input, .form-select {
            padding: 0.65rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.88rem;
            color: var(--text);
            background: var(--white);
            font-family: inherit;
            transition: all 0.2s;
            width: 100%;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }
        .form-full { grid-column: 1 / -1; }
        .btn-submit {
            background: var(--accent-gradient);
            color: #fff;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .btn-submit:hover { filter: brightness(1.05); }
        .btn-submit.green { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }

        /* ── ALERT ── */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            animation: slide-down 0.3s;
        }
        @keyframes slide-down { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── SYSTEM STATUS GLOW ── */
        @keyframes pulse-glow {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* ── TREE VIEW ── */
        .tree { list-style: none; padding: 0; }
        .tree li { padding: 0.4rem 0; }
        .tree .tree-parent { font-weight: 800; color: var(--text); font-size: 0.95rem; }
        .tree .tree-children { list-style: none; padding-left: 1.5rem; padding-right: 1.5rem; margin-top: 0.3rem; border-<?php echo e($isAr ? 'right' : 'left'); ?>: 1px dashed var(--border); }
        .tree .tree-children li { color: var(--text-muted); font-size: 0.85rem; padding: 0.25rem 0.5rem; }
        .tree .tree-leaf { padding-left: 1.5rem; padding-right: 1.5rem; font-size: 0.8rem; color: #94a3b8; }
        .tree .tree-leaf li { padding: 0.2rem 0; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state p { font-size: 0.9rem; font-weight: 500; }

        /* ── TWO COLS ── */
        .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 1024px) { .two-cols { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }

        /* ── DYNAMIC EDIT MODAL ZOOM ── */
        @keyframes modal-zoom {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        #edit-modal > .card {
            animation: modal-zoom 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════
     SIDEBAR
════════════════════════════════════ -->
<nav class="sidebar">
    <div class="sidebar-logo">
        <h2>🔬 <?php echo e($t('UIMP + RGMS', 'بوابة الجامعة الموحدة')); ?></h2>
        <span><?php echo e($t('Unified University Platform', 'المنصة الأكاديمية والبحثية الموحدة')); ?></span>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label"><?php echo e($t('Main', 'الرئيسية')); ?></div>
        <button class="nav-item active" onclick="showSection('dashboard', this)">
            <span class="icon">🏠</span> <?php echo e($t('Dashboard', 'لوحة التحكم')); ?>

        </button>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-section">
        <div class="sidebar-section-label"><?php echo e($t('University', 'الجامعة')); ?></div>
        <button class="nav-item" onclick="showSection('students', this)">
            <span class="icon">🎓</span> <?php echo e($t('Students', 'الطلاب')); ?>

            <span class="nav-badge"><?php echo e($totalStudents); ?></span>
        </button>
        <button class="nav-item" onclick="showSection('employees', this)">
            <span class="icon">💼</span> <?php echo e($t('Employees', 'الموظفون')); ?>

            <span class="nav-badge"><?php echo e($totalEmployees); ?></span>
        </button>
        <button class="nav-item" onclick="showSection('structure', this)">
            <span class="icon">🏛️</span> <?php echo e($t('Structure', 'الهيكل التنظيمي')); ?>

        </button>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-section">
        <div class="sidebar-section-label"><?php echo e($t('Research (RGMS)', 'البحث العلمي (RGMS)')); ?></div>
        <button class="nav-item" onclick="showSection('research-groups', this)">
            <span class="icon">🔬</span> <?php echo e($t('Research Groups', 'المجموعات البحثية')); ?>

            <span class="nav-badge"><?php echo e($totalResearchGroups); ?></span>
        </button>
        <button class="nav-item" onclick="showSection('projects', this)">
            <span class="icon">📁</span> <?php echo e($t('Projects', 'المشاريع البحثية')); ?>

            <span class="nav-badge"><?php echo e($totalProjects); ?></span>
        </button>
        <button class="nav-item" onclick="showSection('publications', this)">
            <span class="icon">📚</span> <?php echo e($t('Publications', 'المنشورات العلمية')); ?>

            <span class="nav-badge"><?php echo e($totalPublications); ?></span>
        </button>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-section">
        <div class="sidebar-section-label"><?php echo e($t('Management', 'الإدارة')); ?></div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR', 'HR_STAFF'])): ?>
        <button class="nav-item" id="nav-item-add-records" onclick="showSection('add-records', this)">
            <span class="icon">➕</span> <?php echo e($t('Add Records', 'إضافة سجلات')); ?>

        </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'AUDITOR'])): ?>
        <button class="nav-item" id="nav-item-audit-logs" onclick="showSection('audit-logs', this)">
            <span class="icon">📋</span> <?php echo e($t('Audit Logs', 'سجلات التدقيق')); ?>

        </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
        <button class="nav-item" id="nav-item-users-management" onclick="showSection('users-management', this)">
            <span class="icon">🔑</span> <?php echo e($t('Users Manager', 'إدارة الصلاحيات')); ?>

            <span class="nav-badge" style="background:var(--accent-gradient);"><?php echo e($allUsers->count()); ?></span>
        </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <button class="nav-item" id="nav-item-api-docs" onclick="showSection('api-docs', this)">
            <span class="icon">📖</span> <?php echo e($t('API Documentation', 'توثيق الـ API')); ?>

        </button>
    </div>

    <!-- User Profile & Logout -->
    <div class="sidebar-divider" style="margin-top: auto;"></div>
    <div class="sidebar-section" style="padding-bottom: 1.5rem;">
        <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 14px; border: 1px solid rgba(255,255,255,0.06);">
            <div style="font-weight: 700; color: #fff; font-size: 0.88rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo e(auth()->user()->username); ?>">
                👤 <?php echo e(auth()->user()->username); ?>

            </div>
            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.45); text-transform: uppercase; font-weight: 700; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                <?php echo e(auth()->user()->roles->pluck('name')->implode(', ') ?: 'USER'); ?>

            </div>
            <form action="<?php echo e(route('logout')); ?>" method="POST" style="margin: 0;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-item" style="color: #f43f5e; padding: 0.4rem 0.5rem; font-size: 0.8rem; background: none; border: none; width: 100%; display: flex; align-items: center; gap: 0.4rem; cursor: pointer; margin: 0;">
                    <span class="icon" style="color: #f43f5e; font-size: 0.9rem;">🚪</span> <?php echo e($t('Logout', 'تسجيل الخروج')); ?>

                </button>
            </form>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════
     MAIN CONTENT
════════════════════════════════════ -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <div class="topbar-title" id="topbar-title"><?php echo e($t('Dashboard', 'لوحة التحكم')); ?></div>
            <div class="topbar-subtitle" id="topbar-subtitle"><?php echo e($t('University Information Management Platform + Research Groups', 'منصة إدارة معلومات الجامعة الموحدة وإدارة المجموعات البحثية')); ?></div>
        </div>
        <div class="topbar-actions">
            <!-- Language Switcher -->
            <a href="/lang/<?php echo e($isAr ? 'en' : 'ar'); ?>" class="btn btn-outline btn-sm" style="font-weight: 700; gap: 4px; font-family: inherit;">
                🌐 <?php echo e($isAr ? 'English' : 'العربية'); ?>

            </a>

            <!-- Glow status -->
            <div style="display: flex; align-items: center; gap: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 5px 12px; border-radius: 99px;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse-glow 1.5s infinite;"></span>
                <span style="font-size: 0.72rem; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo e($t('System Active', 'النظام نشط وآمن')); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'AUDITOR'])): ?>
            <button class="btn btn-outline" onclick="showSection('audit-logs', document.getElementById('nav-item-audit-logs'))">📋 <?php echo e($t('Audit Logs', 'سجلات التدقيق')); ?></button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR', 'HR_STAFF'])): ?>
            <button class="btn btn-primary" onclick="showSection('add-records', document.getElementById('nav-item-add-records'))">➕ <?php echo e($t('Add Record', 'إضافة سجل')); ?></button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="content">

        <!-- ALERTS -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="alert alert-error"><?php echo e($errors->first()); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- ══ DASHBOARD ══ -->
        <div id="section-dashboard" class="page-section active">

            <!-- Stats Row 1: UIMP -->
            <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;color:var(--text-muted);margin-bottom:0.85rem;font-family:var(--font-display);">
                <?php echo e($t('University Registries', 'السجلات الجامعية الأكاديمية')); ?>

            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Students', 'الطلاب المسجلين')); ?></div>
                        <div class="stat-value"><?php echo e($totalStudents); ?></div>
                        <div class="stat-trend">↑ <?php echo e($t('Enrolled', 'نشط حالياً')); ?></div>
                    </div>
                    <div class="stat-icon">🎓</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Employees', 'أعضاء هيئة التدريس')); ?></div>
                        <div class="stat-value"><?php echo e($totalEmployees); ?></div>
                        <div class="stat-trend"><?php echo e($t('Academic & Admin', 'كادر أكاديمي وإداري')); ?></div>
                    </div>
                    <div class="stat-icon">💼</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Faculties', 'الكليات والأقسام')); ?></div>
                        <div class="stat-value"><?php echo e($totalFaculties); ?></div>
                        <div class="stat-trend"><?php echo e($t('Departments & Programs', 'الأقسام والبرامج الدراسية')); ?></div>
                    </div>
                    <div class="stat-icon">🏛️</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Rooms', 'القاعات والمختبرات')); ?></div>
                        <div class="stat-value"><?php echo e($totalRooms); ?></div>
                        <div class="stat-trend"><?php echo e($t('Halls & Labs', 'مواقع وقاعات تدريبية')); ?></div>
                    </div>
                    <div class="stat-icon">🔑</div>
                </div>
            </div>

            <!-- Stats Row 2: RGMS -->
            <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;color:var(--text-muted);margin-bottom:0.85rem;font-family:var(--font-display);">
                <?php echo e($t('Research Management (RGMS)', 'إدارة البحوث والمجموعات البحثية (RGMS)')); ?>

            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Research Groups', 'المجموعات البحثية')); ?></div>
                        <div class="stat-value"><?php echo e($totalResearchGroups); ?></div>
                        <div class="stat-trend"><?php echo e($t('Active Groups', 'مجموعة بحثية نشطة')); ?></div>
                    </div>
                    <div class="stat-icon">🔬</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Projects', 'المشاريع الممولة')); ?></div>
                        <div class="stat-value"><?php echo e($totalProjects); ?></div>
                        <div class="stat-trend"><?php echo e($t('Research Projects', 'مشاريع قيد العمل')); ?></div>
                    </div>
                    <div class="stat-icon">📁</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Publications', 'الأبحاث والمنشورات')); ?></div>
                        <div class="stat-value"><?php echo e($totalPublications); ?></div>
                        <div class="stat-trend"><?php echo e($t('Journals & Conferences', 'منشورة دولياً ومحلياً')); ?></div>
                    </div>
                    <div class="stat-icon">📚</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label"><?php echo e($t('Patents', 'براءات الاختراع')); ?></div>
                        <div class="stat-value"><?php echo e($totalPatents); ?></div>
                        <div class="stat-trend"><?php echo e($t('Filed & Granted', 'مسجلة وممنوحة')); ?></div>
                    </div>
                    <div class="stat-icon">🏅</div>
                </div>
            </div>

            <!-- Quick Overview Tables -->
            <div class="two-cols">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo e($t('Recent Students', 'أحدث الطلاب المسجلين')); ?></div>
                            <div class="card-subtitle"><?php echo e($t('Latest registrations', 'آخر السجلات الأكاديمية المضافة')); ?></div>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="showSection('students', document.querySelector('[onclick*=\"students\"]'))"><?php echo e($t('View All', 'عرض الكل')); ?></button>
                    </div>
                    <div class="table-wrap">
                        <table class="tbl">
                            <thead><tr><th><?php echo e($t('ID', 'الرقم الجامعي')); ?></th><th><?php echo e($t('Name', 'الاسم بالكامل')); ?></th><th><?php echo e($t('Status', 'الحالة الأكاديمية')); ?></th></tr></thead>
                            <tbody>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $students->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><span class="mono"><?php echo e($s->institutional_id); ?></span></td>
                                    <td style="font-weight: 600"><?php echo e($isAr ? $s->name_ar : $s->name_en); ?></td>
                                    <td><span class="badge <?php echo e($s->enrollment_status?->value === 'ACTIVE' ? 'badge-green' : 'badge-gray'); ?>"><?php echo e($s->enrollment_status?->value); ?></span></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo e($t('Research Groups', 'المجموعات البحثية القائمة')); ?></div>
                            <div class="card-subtitle"><?php echo e($t('RGMS overview', 'نظرة عامة على المجموعات النشطة')); ?></div>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="showSection('research-groups', document.querySelector('[onclick*=\"research-groups\"]'))"><?php echo e($t('View All', 'عرض الكل')); ?></button>
                    </div>
                    <div class="table-wrap">
                        <table class="tbl">
                            <thead><tr><th><?php echo e($t('Name', 'اسم المجموعة')); ?></th><th><?php echo e($t('Status', 'الحالة')); ?></th><th><?php echo e($t('Members', 'الأعضاء')); ?></th></tr></thead>
                            <tbody>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $researchGroups->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td style="font-weight:700; color:var(--accent);"><?php echo e(Str::limit($rg->group_name ?? 'N/A', 30)); ?></td>
                                    <td><span class="badge badge-indigo"><?php echo e($rg->status?->value ?? 'ACTIVE'); ?></span></td>
                                    <td><span class="badge badge-gray" style="font-size:0.75rem"><?php echo e($rg->groupMemberships->count()); ?> <?php echo e($t('members', 'أعضاء')); ?></span></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">No research groups yet</td></tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ STUDENTS ══ -->
        <div id="section-students" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">🎓 <?php echo e($t('Students Registry', 'سجل الطلاب الأكاديمي')); ?></div>
                        <div class="card-subtitle"><?php echo e($totalStudents); ?> <?php echo e($t('students enrolled — integrated with Research Groups', 'طالباً مسجلاً وموزعاً على المجموعات البحثية بالجامعة')); ?></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" placeholder="<?php echo e($t('🔍 Search Students...', '🔍 ابحث عن طالب...')); ?>" class="form-input" style="max-width:200px; padding:0.4rem 0.75rem; font-size:0.8rem;" onkeyup="searchTable(this, 'tbl-students')">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="showSection('add-records', null); switchTab2('add-student-tab')">➕ <?php echo e($t('Add Student', 'تسجيل طالب جديد')); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl" id="tbl-students">
                        <thead>
                            <tr>
                                <th><?php echo e($t('Institutional ID', 'الرقم الجامعي')); ?></th>
                                <th><?php echo e($t('Name (EN)', 'الاسم بالإنجليزية')); ?></th>
                                <th><?php echo e($t('Name (AR)', 'الاسم بالعربية')); ?></th>
                                <th><?php echo e($t('Gender', 'الجنس')); ?></th>
                                <th><?php echo e($t('Status', 'حالة القيد')); ?></th>
                                <th><?php echo e($t('Academic Programs', 'البرامج الأكاديمية')); ?></th>
                                <th><?php echo e($t('Research Group Role', 'العضوية البحثية')); ?></th>
                                <th style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>"><?php echo e($t('Actions', 'الإجراءات')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="mono"><?php echo e($student->institutional_id); ?></span></td>
                                <td style="font-weight:600"><?php echo e($student->name_en); ?></td>
                                <td style="direction:rtl;font-family:inherit"><?php echo e($student->name_ar); ?></td>
                                <td><?php echo e($student->gender?->value === 'MALE' ? $t('Male', 'ذكر') : $t('Female', 'أنثى')); ?></td>
                                <td>
                                    <span class="badge <?php echo e($student->enrollment_status?->value === 'ACTIVE' ? 'badge-green' : 'badge-gray'); ?>">
                                        <?php echo e($student->enrollment_status?->value); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $student->programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge badge-blue" style="margin-right:3px"><?php echo e($isAr ? $prog->name_ar : $prog->name_en); ?></span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($membershipsByMemberId->has($student->institutional_id)): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $membershipsByMemberId->get($student->institutional_id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="badge badge-indigo" style="margin-bottom:2px" title="<?php echo e($ms->researchGroup->group_name ?? 'Group'); ?>">
                                                🔬 <?php echo e(Str::limit($ms->researchGroup->group_name ?? 'Group', 15)); ?> (<?php echo e($ms->role); ?>)
                                            </span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>; white-space:nowrap;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR'])): ?>
                                    <button class="btn btn-outline btn-sm btn-green" onclick="openEditStudentModal('<?php echo e($student->id); ?>', '<?php echo e(addslashes($student->institutional_id)); ?>', '<?php echo e(addslashes($student->national_id)); ?>', '<?php echo e(addslashes($student->name_en)); ?>', '<?php echo e(addslashes($student->name_ar)); ?>', '<?php echo e($student->date_of_birth); ?>', '<?php echo e($student->gender?->value); ?>', '<?php echo e($student->admission_date); ?>', '<?php echo e($student->enrollment_status?->value ?? 'ACTIVE'); ?>')">✏️ <?php echo e($t('Edit', 'تعديل')); ?></button>
                                    <button class="btn btn-outline btn-sm btn-red" onclick="confirmDelete('/students/<?php echo e($student->id); ?>/delete')">🗑️ <?php echo e($t('Delete', 'حذف')); ?></button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🎓</div><p>No students found</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ EMPLOYEES ══ -->
        <div id="section-employees" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">💼 <?php echo e($t('Employees Registry', 'سجل أعضاء هيئة التدريس والموظفين')); ?></div>
                        <div class="card-subtitle"><?php echo e($totalEmployees); ?> <?php echo e($t('staff members — academic & administrative roles', 'عضو هيئة تدريس وإداري مسجلين بالمنظومة')); ?></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" placeholder="<?php echo e($t('🔍 Search Staff...', '🔍 ابحث عن موظف...')); ?>" class="form-input" style="max-width:200px; padding:0.4rem 0.75rem; font-size:0.8rem;" onkeyup="searchTable(this, 'tbl-employees')">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'HR_STAFF'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="showSection('add-records', null); switchTab2('add-employee-tab')">➕ <?php echo e($t('Add Employee', 'إضافة موظف جديد')); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl" id="tbl-employees">
                        <thead>
                            <tr>
                                <th><?php echo e($t('Staff ID', 'الرقم الوظيفي')); ?></th>
                                <th><?php echo e($t('Type', 'التصنيف الوظيفي')); ?></th>
                                <th><?php echo e($t('Name (EN)', 'الاسم بالإنجليزية')); ?></th>
                                <th><?php echo e($t('Name (AR)', 'الاسم بالعربية')); ?></th>
                                <th><?php echo e($t('Academic Rank', 'الدرجة العلمية')); ?></th>
                                <th><?php echo e($t('Departments', 'الأقسام التابع لها')); ?></th>
                                <th><?php echo e($t('Research Group Assignments', 'النشاط البحثي والمجموعات')); ?></th>
                                <th style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>"><?php echo e($t('Actions', 'الإجراءات')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="mono"><?php echo e($emp->institutional_id); ?></span></td>
                                <td>
                                    <span class="badge <?php echo e($emp->staff_type?->value === 'ACADEMIC' ? 'badge-purple' : 'badge-gray'); ?>">
                                        <?php echo e($emp->staff_type?->value === 'ACADEMIC' ? $t('Academic', 'أكاديمي') : $t('Admin', 'إداري')); ?>

                                    </span>
                                </td>
                                <td style="font-weight:600"><?php echo e($emp->name_en); ?></td>
                                <td style="direction:rtl"><?php echo e($emp->name_ar); ?></td>
                                <td><?php echo e($emp->academic_rank?->value ?? '—'); ?></td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $emp->departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge badge-blue" style="margin-right:3px"><?php echo e($isAr ? $dept->name_ar : $dept->name_en); ?></span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $piGroups = $researchGroups->where('pi_staff_id', $emp->institutional_id);
                                        $empMems  = $allMemberships->where('member_uimp_id', $emp->institutional_id)->where('member_type', 'Staff')->where('role', '!=', 'PI');
                                    ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($piGroups->isNotEmpty() || $empMems->isNotEmpty()): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $piGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="badge badge-purple" style="margin-bottom:2px" title="Principal Investigator">👑 PI: <?php echo e($g->group_name); ?></span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $empMems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="badge badge-indigo" style="margin-bottom:2px">🔬 <?php echo e(Str::limit($m->researchGroup->group_name ?? 'Group', 15)); ?> (<?php echo e($m->role); ?>)</span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>; white-space:nowrap;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'HR_STAFF'])): ?>
                                    <button class="btn btn-outline btn-sm btn-green" onclick="openEditEmployeeModal('<?php echo e($emp->id); ?>', '<?php echo e(addslashes($emp->institutional_id)); ?>', '<?php echo e($emp->staff_type?->value); ?>', '<?php echo e(addslashes($emp->name_en)); ?>', '<?php echo e(addslashes($emp->name_ar)); ?>', '<?php echo e($emp->academic_rank?->value); ?>', '<?php echo e($emp->hire_date); ?>')">✏️ <?php echo e($t('Edit', 'تعديل')); ?></button>
                                    <button class="btn btn-outline btn-sm btn-red" onclick="confirmDelete('/employees/<?php echo e($emp->id); ?>/delete')">🗑️ <?php echo e($t('Delete', 'حذف')); ?></button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">💼</div><p>No employees found</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ STRUCTURE ══ -->
        <div id="section-structure" class="page-section">
            <div class="two-cols">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🏛️ <?php echo e($t('Academic Structure', 'الهيكل الأكاديمي للجامعة')); ?></div>
                    </div>
                    <div class="card-body">
                        <ul class="tree">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $faculties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fac): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <div class="tree-parent">🏛️ <?php echo e($isAr ? $fac->name_ar : $fac->name_en); ?> <span class="badge badge-gray" style="font-size:0.65rem"><?php echo e($fac->code); ?></span></div>
                                <ul class="tree-children">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $fac->departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        🔹 <?php echo e($isAr ? $dept->name_ar : $dept->name_en); ?>

                                        <ul class="tree-leaf">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $dept->programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>📄 <?php echo e($isAr ? $prog->name_ar : $prog->name_en); ?> <span class="badge badge-amber" style="font-size:0.6rem"><?php echo e($prog->degree_level); ?></span></li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </ul>
                                    </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </ul>
                            </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📍 <?php echo e($t('Campuses & Facilities', 'الحرم الجامعي والمواقع')); ?></div>
                    </div>
                    <div class="card-body">
                        <ul class="tree">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $campuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <div class="tree-parent">📌 <?php echo e($isAr ? $cam->name_ar : $cam->name_en); ?></div>
                                <ul class="tree-children">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $cam->buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bld): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        🏢 <?php echo e($isAr ? $bld->name_ar : $bld->name_en); ?> <span class="badge badge-gray" style="font-size:0.6rem"><?php echo e($bld->code); ?></span>
                                        <ul class="tree-leaf">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $bld->rooms->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>🔑 <?php echo e($room->name); ?> — <?php echo e($room->room_type?->value); ?> (Cap: <?php echo e($room->capacity); ?>)</li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bld->rooms->count() > 3): ?>
                                            <li style="color:#94a3b8">... +<?php echo e($bld->rooms->count() - 3); ?> more rooms</li>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </ul>
                                    </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </ul>
                            </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ RESEARCH GROUPS ══ -->
        <div id="section-research-groups" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">🔬 <?php echo e($t('Research Groups (RGMS)', 'المجموعات البحثية (RGMS)')); ?></div>
                        <div class="card-subtitle"><?php echo e($totalResearchGroups); ?> <?php echo e($t('groups — displaying PIs, members, projects & publications', 'مجموعة بحثية قائمة — الباحثون الرئيسيون، المساعدون والمنشورات')); ?></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" placeholder="<?php echo e($t('🔍 Search Groups...', '🔍 ابحث عن مجموعة...')); ?>" class="form-input" style="max-width:200px; padding:0.4rem 0.75rem; font-size:0.8rem;" onkeyup="searchTable(this, 'tbl-groups')">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="showSection('add-records', null); switchTab2('add-group-tab')">➕ <?php echo e($t('Add Group', 'إنشاء مجموعة جديدة')); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl" id="tbl-groups">
                        <thead>
                            <tr>
                                <th><?php echo e($t('Group Name', 'اسم المجموعة البحثية')); ?></th>
                                <th><?php echo e($t('Research Field', 'حقل البحث')); ?></th>
                                <th><?php echo e($t('Status', 'الحالة')); ?></th>
                                <th><?php echo e($t('Principal Investigator (PI)', 'رئيس المجموعة (PI)')); ?></th>
                                <th><?php echo e($t('Group Members (Staff / Students)', 'أعضاء المجموعة (أساتذة وطلاب)')); ?></th>
                                <th><?php echo e($t('Linked Projects & Publications', 'المشاريع والأبحاث المرتبطة')); ?></th>
                                <th style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>"><?php echo e($t('Actions', 'الإجراءات')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td style="font-weight:700; color:var(--accent);"><?php echo e($rg->group_name ?? 'N/A'); ?></td>
                                <td><?php echo e($rg->research_field); ?></td>
                                <td><span class="badge badge-indigo"><?php echo e($rg->status?->value ?? $rg->status); ?></span></td>
                                <td>
                                    <span style="font-weight:600">👑 <?php echo e($employeeByInstId->get($rg->pi_staff_id)?->name_en ?? $rg->pi_staff_id); ?></span><br>
                                    <small style="color:var(--text-muted)">Staff ID: <?php echo e($rg->pi_staff_id); ?></small>
                                </td>
                                <td>
                                    <?php $hasMems = false; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rg->groupMemberships; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $hasMems = true; ?>
                                        <div style="font-size:0.8rem; margin-top:3px; display:flex; align-items:center; justify-content:space-between; gap:0.5rem; background:#f8fafc; padding:4px 8px; border-radius:8px; border:1px solid #e2e8f0;">
                                            <span>
                                                👤 <?php echo e($getMemberName($m->member_uimp_id, $m->member_type)); ?> 
                                                <span class="badge badge-gray" style="font-size:0.6rem; padding:1px 4px;"><?php echo e($m->role); ?></span>
                                            </span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                                            <button onclick="confirmDelete('/group-memberships/<?php echo e($m->id); ?>/delete')" style="background:none; border:none; color:var(--accent-4); cursor:pointer; font-size:0.9rem;" title="Remove Member">&times;</button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hasMems): ?>
                                        <span style="color:var(--text-muted)"><?php echo e($t('Only PI registered', 'رئيس المجموعة فقط')); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td>
                                    <!-- Projects -->
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $projects->where('research_group_id', $rg->id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge badge-amber" style="margin-bottom:2px" title="Project: <?php echo e($p->title); ?>">📁 <?php echo e(Str::limit($p->title, 20)); ?></span><br>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <!-- Publications -->
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $publications->where('research_group_id', $rg->id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge badge-indigo" style="margin-bottom:2px" title="Publication: <?php echo e($pub->title); ?>">📚 <?php echo e(Str::limit($pub->title, 20)); ?></span><br>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>; white-space:nowrap;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                                    <button class="btn btn-outline btn-sm btn-green" onclick="openEditGroupModal('<?php echo e($rg->id); ?>', '<?php echo e(addslashes($rg->group_name)); ?>', '<?php echo e(addslashes($rg->research_field)); ?>', '<?php echo e(addslashes($rg->research_area)); ?>', '<?php echo e($rg->status?->value ?? $rg->status); ?>', '<?php echo e($rg->pi_staff_id); ?>', '<?php echo e($rg->department_ref_id); ?>', '<?php echo e($rg->budget_allocation); ?>', <?php echo e(json_encode($rg->groupMemberships->toArray())); ?>)">✏️ <?php echo e($t('Edit', 'تعديل')); ?></button>
                                    <button class="btn btn-outline btn-sm btn-red" onclick="confirmDelete('/research-groups/<?php echo e($rg->id); ?>/delete')">🗑️ <?php echo e($t('Delete', 'حذف')); ?></button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">🔬</div><p>No research groups yet</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ PROJECTS ══ -->
        <div id="section-projects" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">📁 <?php echo e($t('Research Projects', 'المشاريع البحثية الممولة')); ?></div>
                        <div class="card-subtitle"><?php echo e($totalProjects); ?> <?php echo e($t('projects registered across all research groups', 'مشروعاً بحثياً مسجلاً تحت المجموعات القائمة')); ?></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" placeholder="<?php echo e($t('🔍 Search Projects...', '🔍 ابحث عن مشروع...')); ?>" class="form-input" style="max-width:200px; padding:0.4rem 0.75rem; font-size:0.8rem;" onkeyup="searchTable(this, 'tbl-projects')">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="showSection('add-records', null); switchTab2('add-project-tab')">➕ <?php echo e($t('Add Project', 'تسجيل مشروع جديد')); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl" id="tbl-projects">
                        <thead>
                            <tr>
                                <th><?php echo e($t('Project Title', 'عنوان المشروع')); ?></th>
                                <th><?php echo e($t('Research Group / PI', 'المجموعة البحثية / رئيسها')); ?></th>
                                <th><?php echo e($t('Funding Agency', 'الجهة الممولة')); ?></th>
                                <th><?php echo e($t('Budget', 'الميزانية المخصصة')); ?></th>
                                <th><?php echo e($t('Contributors', 'المساهمون بالمشروع')); ?></th>
                                <th><?php echo e($t('Status', 'الحالة')); ?></th>
                                <th style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>"><?php echo e($t('Actions', 'الإجراءات')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text)"><?php echo e($proj->title); ?></td>
                                <td>
                                    <span style="font-weight:600">🔬 <?php echo e($proj->researchGroup?->group_name ?? '—'); ?></span><br>
                                    <small style="color:var(--text-muted)">PI: <?php echo e($employeeByInstId->get($proj->researchGroup?->pi_staff_id)?->name_en ?? '—'); ?></small>
                                </td>
                                <td><?php echo e($proj->funding_agency); ?></td>
                                <td><span class="mono"><?php echo e(number_format($proj->budget, 2)); ?> LYD</span></td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($proj->projectContributors->isNotEmpty()): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $proj->projectContributors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="badge badge-gray" style="margin-bottom:2px;" title="<?php echo e($c->contributor_role); ?>">
                                                👤 <?php echo e($getMemberName($c->member_uimp_id)); ?> <small>(<?php echo e($c->contributor_role); ?>)</small>
                                            </span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td><span class="badge badge-amber"><?php echo e($proj->status?->value ?? $proj->status); ?></span></td>
                                <td style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>; white-space:nowrap;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                                    <button class="btn btn-outline btn-sm btn-green" onclick="openEditProjectModal('<?php echo e($proj->id); ?>', '<?php echo e(addslashes($proj->title)); ?>', '<?php echo e(addslashes($proj->funding_agency)); ?>', '<?php echo e($proj->budget); ?>', '<?php echo e($proj->start_date?->format('Y-m-d')); ?>', '<?php echo e($proj->end_date?->format('Y-m-d')); ?>', '<?php echo e($proj->status?->value ?? $proj->status); ?>', <?php echo e(json_encode($proj->projectContributors->toArray())); ?>)">✏️ <?php echo e($t('Edit', 'تعديل')); ?></button>
                                    <button class="btn btn-outline btn-sm btn-red" onclick="confirmDelete('/projects/<?php echo e($proj->id); ?>/delete')">🗑️ <?php echo e($t('Delete', 'حذف')); ?></button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📁</div><p>No projects yet</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ PUBLICATIONS ══ -->
        <div id="section-publications" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">📚 <?php echo e($t('Publications', 'الأبحاث والمنشورات العلمية')); ?></div>
                        <div class="card-subtitle"><?php echo e($totalPublications); ?> <?php echo e($t('scientific publications with linked group authors', 'منشوراً علمياً وورقة بحثية موثقة في المجلات العالمية')); ?></div>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" placeholder="<?php echo e($t('🔍 Search Publications...', '🔍 ابحث عن منشور علمي...')); ?>" class="form-input" style="max-width:200px; padding:0.4rem 0.75rem; font-size:0.8rem;" onkeyup="searchTable(this, 'tbl-publications')">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="showSection('add-records', null); switchTab2('add-publication-tab')">➕ <?php echo e($t('Add Publication', 'تسجيل بحث علمي')); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl" id="tbl-publications">
                        <thead>
                            <tr>
                                <th><?php echo e($t('Title', 'عنوان البحث')); ?></th>
                                <th><?php echo e($t('Type', 'نوع المنشور')); ?></th>
                                <th><?php echo e($t('Research Group', 'المجموعة البحثية')); ?></th>
                                <th><?php echo e($t('Publication Authors', 'المؤلفون والمشاركون')); ?></th>
                                <th><?php echo e($t('DOI / Venue', 'رابط المعرف / دار النشر')); ?></th>
                                <th><?php echo e($t('Year', 'سنة النشر')); ?></th>
                                <th><?php echo e($t('Status', 'الحالة')); ?></th>
                                <th style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>"><?php echo e($t('Actions', 'الإجراءات')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $publications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text)"><?php echo e(Str::limit($pub->title, 55)); ?></td>
                                <td><span class="badge badge-purple"><?php echo e($pub->publication_type?->value ?? $pub->publication_type); ?></span></td>
                                <td><?php echo e($pub->researchGroup?->group_name ?? '—'); ?></td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pubAuthorsByPubId->has($pub->id)): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $pubAuthorsByPubId->get($pub->id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $auth): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="badge badge-gray" style="margin-bottom:2px;">
                                                👤 <?php echo e($getMemberName($auth->member_uimp_id)); ?> 
                                                <small>(#<?php echo e($auth->author_order); ?> - <?php echo e($auth->contribution_type); ?>)</small>
                                            </span><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pub->doi): ?>
                                    <span class="mono"><?php echo e($pub->doi); ?></span><br>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <small style="color:var(--text-muted)"><?php echo e($pub->journal_name ?? $pub->publisher); ?></small>
                                </td>
                                <td><?php echo e($pub->publication_year); ?></td>
                                <td><span class="badge badge-indigo"><?php echo e($pub->status?->value ?? $pub->status); ?></span></td>
                                <td style="text-align:<?php echo e($isAr ? 'left' : 'right'); ?>; white-space:nowrap;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                                    <button class="btn btn-outline btn-sm btn-green" onclick="openEditPublicationModal('<?php echo e($pub->id); ?>', '<?php echo e(addslashes($pub->title)); ?>', '<?php echo e($pub->publication_type?->value ?? $pub->publication_type); ?>', '<?php echo e($pub->publication_year); ?>', '<?php echo e($pub->status?->value ?? $pub->status); ?>', '<?php echo e(addslashes($pub->doi)); ?>', '<?php echo e(addslashes($pub->journal_name)); ?>', '<?php echo e(addslashes($pub->publisher)); ?>', <?php echo e(json_encode($pub->publicationAuthors->toArray())); ?>)">✏️ <?php echo e($t('Edit', 'تعديل')); ?></button>
                                    <button class="btn btn-outline btn-sm btn-red" onclick="confirmDelete('/publications/<?php echo e($pub->id); ?>/delete')">🗑️ <?php echo e($t('Delete', 'حذف')); ?></button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📚</div><p>No publications yet</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ ADD RECORDS ══ -->
        <div id="section-add-records" class="page-section">
            <div class="tabs-bar" id="add-tabs-bar">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR'])): ?>
                <button class="tab-btn active" onclick="switchTab2('add-student-tab', this)">🎓 <?php echo e($t('Add Student', 'إضافة طالب')); ?></button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'HR_STAFF'])): ?>
                <button class="tab-btn <?php echo e(!auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR']) ? 'active' : ''); ?>" onclick="switchTab2('add-employee-tab', this)">💼 <?php echo e($t('Add Employee', 'إضافة موظف')); ?></button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
                <button class="tab-btn" onclick="switchTab2('add-group-tab', this)">🔬 <?php echo e($t('Add Research Group', 'إضافة مجموعة بحثية')); ?></button>
                <button class="tab-btn" onclick="switchTab2('add-member-tab', this)">👥 <?php echo e($t('Add Group Member', 'ربط عضو')); ?></button>
                <button class="tab-btn" onclick="switchTab2('add-project-tab', this)">📁 <?php echo e($t('Add Project', 'إضافة مشروع')); ?></button>
                <button class="tab-btn" onclick="switchTab2('add-publication-tab', this)">📚 <?php echo e($t('Add Publication', 'تسجيل بحث')); ?></button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <!-- Add Student -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR'])): ?>
            <div id="add-student-tab" class="tab-pane active card" style="border-radius:0 0 16px 16px; border-top:none;">
                <div class="card-body">
                    <form action="/students/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><?php echo e($t('Institutional Student ID *', 'الرقم الجامعي للطالب *')); ?></label>
                                <input type="text" name="institutionalId" class="form-input" placeholder="e.g. STU-2024-1009" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('National ID *', 'الرقم الوطني *')); ?></label>
                                <input type="text" name="nationalId" class="form-input" placeholder="e.g. LY-100200300" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Full Name (English) *', 'الاسم الكامل بالإنجليزية *')); ?></label>
                                <input type="text" name="nameEn" class="form-input" placeholder="e.g. Salem Al-Hadi" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Full Name (Arabic) *', 'الاسم الكامل بالعربية *')); ?></label>
                                <input type="text" name="nameAr" class="form-input" placeholder="e.g. سالم الهادي" required style="direction:rtl">
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Date of Birth *', 'تاريخ الميلاد *')); ?></label>
                                <input type="date" name="dateOfBirth" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Gender *', 'الجنس *')); ?></label>
                                <select name="gender" class="form-select" required>
                                    <option value="MALE">Male / ذكر</option>
                                    <option value="FEMALE">Female / أنثى</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Admission Date *', 'تاريخ القبول والانتساب *')); ?></label>
                                <input type="date" name="admissionDate" class="form-input" value="<?php echo e(date('Y-m-d')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Academic Program *', 'البرنامج الدراسي والدرجة العلمية *')); ?></label>
                                <select name="programIds[]" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allPrograms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($prog->id); ?>"><?php echo e($isAr ? $prog->name_ar : $prog->name_en); ?> (<?php echo e($prog->degree_level); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>

                            <!-- Optional Research Group Assignment -->
                            <div class="form-full" style="font-weight:700; color:var(--accent); margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1.5rem; font-family:var(--font-display);">
                                🔬 <?php echo e($t('Initial Research Group Assignment (Optional)', 'تنسيب فوري لمجموعة بحثية (اختياري)')); ?>

                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Select Research Group', 'اختر المجموعة البحثية')); ?></label>
                                <select name="research_group_id" class="form-select">
                                    <option value="">None / لا شيء</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($rg->id); ?>"><?php echo e($rg->group_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Role inside Group', 'دور الطالب داخل المجموعة')); ?></label>
                                <select name="research_group_role" class="form-select">
                                    <option value="Graduate-Researcher">Graduate Researcher</option>
                                    <option value="Research-Assistant">Research Assistant</option>
                                    <option value="Co-I">Co-Investigator (Co-I)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Workload Allocation (%)', 'نسبة العمل المخصصة (%)')); ?></label>
                                <input type="number" name="research_group_workload" class="form-input" value="20" min="0" max="100">
                            </div>

                            <div class="form-full">
                                <button type="submit" class="btn-submit">✅ <?php echo e($t('Register Student & Log Audit', 'تسجيل الطالب وتوثيق الحركة')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Add Employee -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'HR_STAFF'])): ?>
            <div id="add-employee-tab" class="tab-pane <?php echo e(!auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR']) ? 'active' : ''); ?> card" style="border-radius:0 0 16px 16px; border-top:none; <?php echo e(!auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR']) ? 'display:block;' : 'display:none;'); ?>">
                <div class="card-body">
                    <form action="/employees/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><?php echo e($t('Institutional Staff ID *', 'الرقم الوظيفي للمعادلة *')); ?></label>
                                <input type="text" name="institutionalId" class="form-input" placeholder="e.g. EMP-2024-001" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Staff Type *', 'نوع التوظيف والصفة *')); ?></label>
                                <select name="staffType" class="form-select" required>
                                    <option value="ACADEMIC">Academic Staff / هيئة تدريس</option>
                                    <option value="NON_ACADEMIC">Administrative Staff / إداري</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Full Name (English) *', 'الاسم الكامل بالإنجليزية *')); ?></label>
                                <input type="text" name="nameEn" class="form-input" placeholder="e.g. Dr. Hisham Ali" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Full Name (Arabic) *', 'الاسم الكامل بالعربية *')); ?></label>
                                <input type="text" name="nameAr" class="form-input" placeholder="e.g. د. هشام علي" required style="direction:rtl">
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Academic Rank (Academic Staff Only)', 'الدرجة الأكاديمية (لهيئة التدريس فقط)')); ?></label>
                                <select name="academicRank" class="form-select">
                                    <option value="">None</option>
                                    <option value="PROFESSOR">Professor / أستاذ</option>
                                    <option value="ASSOCIATE_PROFESSOR">Associate Professor / أستاذ مشارك</option>
                                    <option value="ASSISTANT_PROFESSOR">Assistant Professor / أستاذ مساعد</option>
                                    <option value="LECTURER">Lecturer / محاضر</option>
                                    <option value="ASSISTANT_LECTURER">Assistant Lecturer / معيد</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Hiring Date *', 'تاريخ مباشرة العمل *')); ?></label>
                                <input type="date" name="hireDate" class="form-input" value="<?php echo e(date('Y-m-d')); ?>" required>
                            </div>
                            <div class="form-group form-full">
                                <label><?php echo e($t('Primary Department *', 'القسم الأكاديمي الرئيسي *')); ?></label>
                                <select name="departmentIds[]" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allDepartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($dept->id); ?>"><?php echo e($isAr ? $dept->name_ar : $dept->name_en); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>

                            <!-- Optional Research Group Assignment -->
                            <div class="form-full" style="font-weight:700; color:var(--accent); margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1.5rem; font-family:var(--font-display);">
                                🔬 <?php echo e($t('Initial Research Group Assignment (Optional)', 'تنسيب فوري لمجموعة بحثية (اختياري)')); ?>

                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Select Research Group', 'اختر المجموعة البحثية')); ?></label>
                                <select name="research_group_id" class="form-select">
                                    <option value="">None / لا شيء</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($rg->id); ?>"><?php echo e($rg->group_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Role inside Group', 'دور العضو بالمجموعة')); ?></label>
                                <select name="research_group_role" class="form-select">
                                    <option value="Co-I">Co-Investigator (Co-I)</option>
                                    <option value="Research-Assistant">Research Assistant</option>
                                    <option value="Graduate-Researcher">Graduate Researcher</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Workload Allocation (%)', 'عبء العمل البحثي المخصص (%)')); ?></label>
                                <input type="number" name="research_group_workload" class="form-input" value="20" min="0" max="100">
                            </div>

                            <div class="form-full">
                                <button type="submit" class="btn-submit green">✅ <?php echo e($t('Register Employee & Log Audit', 'تسجيل الموظف وتوثيق الحركة')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Add Group (RGMS) -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
            <div id="add-group-tab" class="tab-pane card" style="border-radius:0 0 16px 16px; border-top:none; display:none;">
                <div class="card-body">
                    <form action="/research-groups/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><?php echo e($t('Research Group Name *', 'اسم المجموعة البحثية *')); ?></label>
                                <input type="text" name="group_name" class="form-input" placeholder="e.g. Advanced Material Science Group" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Research Field *', 'مجال التخصص والبحث *')); ?></label>
                                <input type="text" name="research_field" class="form-input" placeholder="e.g. Chemistry & Nanotechnology" required>
                            </div>
                            <div class="form-group form-full">
                                <label><?php echo e($t('Research Area Summary *', 'ملخص أهداف وأعمال المجموعة *')); ?></label>
                                <input type="text" name="research_area" class="form-input" placeholder="e.g. Graphene synthesis, polymer nano-composites" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Lifecycle Status *', 'حالة المجموعة في المنظومة *')); ?></label>
                                <select name="status" class="form-select" required>
                                    <option value="Active">Active / نشط</option>
                                    <option value="Draft">Draft / مسودة</option>
                                    <option value="Suspended">Suspended / معلق</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Principal Investigator (PI) *', 'الباحث الرئيسي ورئيس المجموعة (PI) *')); ?></label>
                                <select name="pi_staff_id" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($emp->institutional_id); ?>"><?php echo e($isAr ? $emp->name_ar : $emp->name_en); ?> (<?php echo e($emp->institutional_id); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Primary Department Reference', 'القسم الأكاديمي الراعي')); ?></label>
                                <select name="department_ref_id" class="form-select">
                                    <option value="">None</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allDepartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($dept->id); ?>"><?php echo e($isAr ? $dept->name_ar : $dept->name_en); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Initial Budget Allocation (LYD)', 'الميزانية المالية المبدئية (دينار ليبي)')); ?></label>
                                <input type="number" step="0.01" name="budget_allocation" class="form-input" placeholder="0.00">
                            </div>

                            <!-- Initial Members Checkboxes -->
                            <div class="form-group form-full">
                                <label><?php echo e($t('Assign Initial Members (Optional)', 'تحديد الأعضاء التأسيسيين للمجموعة (اختياري)')); ?></label>
                                <div style="max-height:150px; overflow-y:auto; border:1.5px solid var(--border); border-radius:10px; padding:0.75rem; background:#fafafa;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_members[]" value="Staff|<?php echo e($emp->institutional_id); ?>|Co-I">
                                            <span style="font-size:0.82rem; font-weight:500;">💼 <?php echo e($t('Staff: ', 'موظف: ')); ?> <?php echo e($isAr ? $emp->name_ar : $emp->name_en); ?> (Co-I)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_members[]" value="Student|<?php echo e($stu->institutional_id); ?>|Graduate-Researcher">
                                            <span style="font-size:0.82rem; font-weight:500;">🎓 <?php echo e($t('Student: ', 'طالب: ')); ?> <?php echo e($isAr ? $stu->name_ar : $stu->name_en); ?> (Graduate Researcher)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            <div class="form-full">
                                <button type="submit" class="btn-submit">✅ <?php echo e($t('Register Research Group', 'تسجيل وإنشاء المجموعة')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Group Member (RGMS Link) -->
            <div id="add-member-tab" class="tab-pane card" style="border-radius:0 0 16px 16px; border-top:none; display:none;">
                <div class="card-body">
                    <form action="/group-memberships/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><?php echo e($t('Parent Research Group *', 'المجموعة البحثية المحتضنة *')); ?></label>
                                <select name="group_id" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($rg->id); ?>"><?php echo e($rg->group_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Member Type *', 'تصنيف عضو العضوية *')); ?></label>
                                <select name="member_type" class="form-select" onchange="toggleMemberSelection(this.value)" required>
                                    <option value="Staff"><?php echo e($t('University Staff / Employee', 'كادر أكاديمي وموظف بالجامعة')); ?></option>
                                    <option value="Student"><?php echo e($t('University Student', 'طالب بالجامعة')); ?></option>
                                    <option value="External"><?php echo e($t('External Collaborator', 'باحث متعاون خارجي')); ?></option>
                                </select>
                            </div>
                            
                            <!-- Staff Member Select -->
                            <div class="form-group" id="member-select-staff-group">
                                <label><?php echo e($t('Select Employee *', 'اختر الموظف أو الأكاديمي *')); ?></label>
                                <select id="member-select-staff" name="member_uimp_id" class="form-select">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($emp->institutional_id); ?>"><?php echo e($isAr ? $emp->name_ar : $emp->name_en); ?> (<?php echo e($emp->institutional_id); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>

                            <!-- Student Member Select (hidden by default) -->
                            <div class="form-group" id="member-select-student-group" style="display:none;">
                                <label><?php echo e($t('Select Student *', 'اختر الطالب الباحث *')); ?></label>
                                <select id="member-select-student" class="form-select">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($stu->institutional_id); ?>"><?php echo e($isAr ? $stu->name_ar : $stu->name_en); ?> (<?php echo e($stu->institutional_id); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>

                            <!-- External Member Input (hidden by default) -->
                            <div class="form-group" id="member-select-external-group" style="display:none;">
                                <label><?php echo e($t('External Member Identifier *', 'معرف الباحث الخارجي *')); ?></label>
                                <input type="text" id="member-select-external" class="form-input" placeholder="e.g. EXT-Dr-Salem">
                            </div>

                            <div class="form-group">
                                <label><?php echo e($t('Research Role *', 'دور العضو في البحث *')); ?></label>
                                <select name="role" class="form-select" required>
                                    <option value="Co-I">Co-Investigator (Co-I)</option>
                                    <option value="Research-Assistant">Research Assistant</option>
                                    <option value="Graduate-Researcher">Graduate Researcher</option>
                                    <option value="External-Collaborator">External Collaborator</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Workload Allocation (%) *', 'نسبة مساهمة العضو (%) *')); ?></label>
                                <input type="number" name="workload_percentage" class="form-input" min="0" max="100" value="20" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Start Date *', 'تاريخ بدء النشاط *')); ?></label>
                                <input type="date" name="start_date" class="form-input" value="<?php echo e(date('Y-m-d')); ?>" required>
                            </div>
                            <div class="form-full">
                                <button type="submit" class="btn-submit green">✅ <?php echo e($t('Assign Member to Group', 'تسجيل العضو في المجموعة')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Project (RGMS) -->
            <div id="add-project-tab" class="tab-pane card" style="border-radius:0 0 16px 16px; border-top:none; display:none;">
                <div class="card-body">
                    <form action="/projects/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group form-full">
                                <label><?php echo e($t('Project Title *', 'عنوان المشروع المشروع البحثي المقترح *')); ?></label>
                                <input type="text" name="title" class="form-input" placeholder="e.g. Synthesis of Bio-degradable Nano-plastics" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Parent Research Group *', 'المجموعة البحثية الحاضنة *')); ?></label>
                                <select name="research_group_id" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($rg->id); ?>"><?php echo e($rg->group_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Funding Agency *', 'الجهة الممولة والراعية *')); ?></label>
                                <input type="text" name="funding_agency" class="form-input" placeholder="e.g. Ministry of Higher Education" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Project Budget (LYD) *', 'ميزانية المشروع المالية (دينار ليبي) *')); ?></label>
                                <input type="number" step="0.01" name="budget" class="form-input" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Start Date *', 'تاريخ انطلاق المشروع *')); ?></label>
                                <input type="date" name="start_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('End Date *', 'تاريخ انتهاء المشروع المتوقع *')); ?></label>
                                <input type="date" name="end_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Lifecycle Status *', 'حالة المشروع الحالية *')); ?></label>
                                <select name="status" class="form-select" required>
                                    <option value="Active">Active / نشط وممول</option>
                                    <option value="Planning">Planning / قيد التخطيط</option>
                                    <option value="On-Hold">On-Hold / معلق مؤقتاً</option>
                                    <option value="Completed">Completed / منجز بالكامل</option>
                                </select>
                            </div>

                            <!-- Initial Contributors Checkboxes -->
                            <div class="form-group form-full">
                                <label><?php echo e($t('Select Initial Contributors (Optional)', 'تحديد المساهمين الأوائل بالمشروع (اختياري)')); ?></label>
                                <div style="max-height:150px; overflow-y:auto; border:1.5px solid var(--border); border-radius:10px; padding:0.75rem; background:#fafafa;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_contributors[]" value="Staff|<?php echo e($emp->institutional_id); ?>|Lead-Researcher">
                                            <span style="font-size:0.82rem; font-weight:500;">💼 <?php echo e($t('Staff: ', 'موظف: ')); ?> <?php echo e($isAr ? $emp->name_ar : $emp->name_en); ?> (Lead Researcher)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_contributors[]" value="Student|<?php echo e($stu->institutional_id); ?>|Assistant-Researcher">
                                            <span style="font-size:0.82rem; font-weight:500;">🎓 <?php echo e($t('Student: ', 'طالب: ')); ?> <?php echo e($isAr ? $stu->name_ar : $stu->name_en); ?> (Assistant Researcher)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            <div class="form-full">
                                <button type="submit" class="btn-submit">✅ <?php echo e($t('Register Research Project', 'تسجيل وإطلاق المشروع')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Publication (RGMS) -->
            <div id="add-publication-tab" class="tab-pane card" style="border-radius:0 0 16px 16px; border-top:none; display:none;">
                <div class="card-body">
                    <form action="/publications/create" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-grid">
                            <div class="form-group form-full">
                                <label><?php echo e($t('Publication Title *', 'عنوان البحث العلمي الموثق *')); ?></label>
                                <input type="text" name="title" class="form-input" placeholder="e.g. Graphene Composites under Thermal Stress" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Parent Research Group *', 'المجموعة البحثية المشرفة *')); ?></label>
                                <select name="research_group_id" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $researchGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($rg->id); ?>"><?php echo e($rg->group_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Publication Type *', 'نوع ومكان النشر *')); ?></label>
                                <select name="publication_type" class="form-select" required>
                                    <option value="Journal-Article">Journal Article / مجلة علمية</option>
                                    <option value="Conference-Paper">Conference Paper / مؤتمر علمي</option>
                                    <option value="Book-Chapter">Book Chapter / كتاب مخصص</option>
                                    <option value="Technical-Report">Technical Report / تقرير فني</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Publication Year *', 'سنة النشر وتوثيق البحث *')); ?></label>
                                <input type="number" name="publication_year" class="form-input" value="<?php echo e(date('Y')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Lifecycle Status *', 'الحالة الحالية للورقة العلمية *')); ?></label>
                                <select name="status" class="form-select" required>
                                    <option value="Published">Published / منشور بالكامل</option>
                                    <option value="Accepted">Accepted / مقبول للنشر</option>
                                    <option value="Submitted">Submitted / تم الإرسال</option>
                                    <option value="Under-Review">Under-Review / قيد المراجعة والتحكيم</option>
                                    <option value="In-Preparation">In-Preparation / قيد الصياغة والإعداد</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('DOI Identifier', 'معرف الغرض الرقمي الدولي (DOI)')); ?></label>
                                <input type="text" name="doi" class="form-input" placeholder="e.g. 10.1007/s11042-026-x">
                            </div>
                            <div class="form-group">
                                <label><?php echo e($t('Journal / Conference Name', 'اسم المجلة العلمية أو المؤتمر')); ?></label>
                                <input type="text" name="journal_name" class="form-input" placeholder="e.g. International Journal of Energy">
                            </div>
                            <div class="form-group form-full">
                                <label><?php echo e($t('Publisher', 'دار النشر الأكاديمية')); ?></label>
                                <input type="text" name="publisher" class="form-input" placeholder="e.g. Springer, IEEE, Elsevier">
                            </div>

                            <!-- Initial Authors Checkboxes -->
                            <div class="form-group form-full">
                                <label><?php echo e($t('Select Publication Authors (Optional)', 'تحديد المؤلفين والأقلام المساهمة (اختياري)')); ?></label>
                                <div style="max-height:150px; overflow-y:auto; border:1.5px solid var(--border); border-radius:10px; padding:0.75rem; background:#fafafa;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_authors[]" value="Staff|<?php echo e($emp->institutional_id); ?>|Co-Author">
                                            <span style="font-size:0.82rem; font-weight:500;">💼 <?php echo e($t('Staff: ', 'موظف: ')); ?> <?php echo e($isAr ? $emp->name_ar : $emp->name_en); ?> (Co-Author)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                                            <input type="checkbox" name="initial_authors[]" value="Student|<?php echo e($stu->institutional_id); ?>|Co-Author">
                                            <span style="font-size:0.82rem; font-weight:500;">🎓 <?php echo e($t('Student: ', 'طالب: ')); ?> <?php echo e($isAr ? $stu->name_ar : $stu->name_en); ?> (Co-Author)</span>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            <div class="form-full">
                                <button type="submit" class="btn-submit">✅ <?php echo e($t('Register Publication', 'تسجيل وحفظ المنشور العلمي')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- ══ AUDIT LOGS ══ -->
        <div id="section-audit-logs" class="page-section">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'AUDITOR'])): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('audit-log-viewer', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1539824748-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-icon">🔒</div>
                            <p>Unauthorized Access. You do not have permission to view compliance audit logs.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- ══ USERS MANAGEMENT ══ -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN'])): ?>
        <div id="section-users-management" class="page-section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">🔑 <?php echo e($t('Users & Roles Management', 'إدارة المستخدمين وصلاحيات الدخول')); ?></div>
                        <div class="card-subtitle"><?php echo e($t('Assign and update user system privileges dynamically with compliance audit trailing', 'تعديل وتحديث صلاحيات المستخدمين والأدوار الأمنية في المنظومة فورياً مع التوثيق الكامل للحركات')); ?></div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th><?php echo e($t('User ID (UUID)', 'معرف المستخدم الفريد')); ?></th>
                                <th><?php echo e($t('Username', 'اسم المستخدم')); ?></th>
                                <th><?php echo e($t('Current Role / Rank', 'الصلاحية / الرتبة الحالية')); ?></th>
                                <th><?php echo e($t('Change Role', 'تعديل وتغيير الصلاحية')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $allUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="mono" style="font-size: 0.75rem;"><?php echo e($usr->id); ?></span></td>
                                <td style="font-weight:700; color:var(--accent);">👤 <?php echo e($usr->username); ?></td>
                                <td>
                                    <?php
                                        $role = $usr->roles->first()?->name ?? 'None';
                                        $badgeClass = 'badge-gray';
                                        if ($role === 'SYSTEM_ADMIN') $badgeClass = 'badge-rose';
                                        elseif ($role === 'UNIVERSITY_ADMIN') $badgeClass = 'badge-purple';
                                        elseif (in_array($role, ['REGISTRAR', 'HR_STAFF'])) $badgeClass = 'badge-blue';
                                        elseif ($role === 'AUDITOR') $badgeClass = 'badge-amber';
                                        elseif ($role === 'FACULTY') $badgeClass = 'badge-indigo';
                                        elseif ($role === 'STUDENT') $badgeClass = 'badge-green';
                                    ?>
                                    <span class="badge <?php echo e($badgeClass); ?>" style="font-size:0.8rem; padding: 4px 10px;">
                                        <?php echo e($role); ?>

                                    </span>
                                </td>
                                <td>
                                    <form action="/users/<?php echo e($usr->id); ?>/role" method="POST" style="display:flex; gap:0.5rem; align-items:center; max-width: 320px; margin: 0;">
                                        <?php echo csrf_field(); ?>
                                        <select name="role" class="form-select" style="padding:0.4rem 0.60rem; font-size:0.8rem; height:auto; width: auto; min-width: 180px;">
                                            <option value="SYSTEM_ADMIN" <?php echo e($role === 'SYSTEM_ADMIN' ? 'selected' : ''); ?>>SYSTEM_ADMIN / المسؤول العام</option>
                                            <option value="UNIVERSITY_ADMIN" <?php echo e($role === 'UNIVERSITY_ADMIN' ? 'selected' : ''); ?>>UNIVERSITY_ADMIN / رئيس الجامعة</option>
                                            <option value="REGISTRAR" <?php echo e($role === 'REGISTRAR' ? 'selected' : ''); ?>>REGISTRAR / مسجل الكلية</option>
                                            <option value="HR_STAFF" <?php echo e($role === 'HR_STAFF' ? 'selected' : ''); ?>>HR_STAFF / الموارد البشرية</option>
                                            <option value="AUDITOR" <?php echo e($role === 'AUDITOR' ? 'selected' : ''); ?>>AUDITOR / مدقق النظام</option>
                                            <option value="FACULTY" <?php echo e($role === 'FACULTY' ? 'selected' : ''); ?>>FACULTY / عضو هيئة التدريس</option>
                                            <option value="STUDENT" <?php echo e($role === 'STUDENT' ? 'selected' : ''); ?>>STUDENT / طالب باحث</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm btn-green" style="padding: 6px 12px; font-weight:800;" title="<?php echo e($t('Save Changes', 'حفظ التغييرات')); ?>">
                                            💾 <?php echo e($t('Save', 'حفظ')); ?>

                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon">🔑</div><p>No users registered</p></div></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- ══ API DOCUMENTATION ══ -->
        <div id="section-api-docs" class="page-section">
            <div class="card" style="height: calc(100vh - 200px); overflow: hidden; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
                <iframe src="/docs" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

<!-- ═══════════════════════════════════
     DYNAMIC EDIT MODAL
════════════════════════════════════ -->
<div id="edit-modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; margin-bottom: 0; box-shadow: var(--shadow-lg); border-radius: 20px;">
        <div class="card-header" style="background:#f8fafc;">
            <div class="card-title" id="modal-title" style="font-family:var(--font-display); font-size:1.15rem; font-weight:800;"><?php echo e($t('Edit Record', 'تعديل السجل')); ?></div>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <form id="edit-form" method="POST" action="">
                <?php echo csrf_field(); ?>
                <div id="modal-fields-container" class="form-grid">
                    <!-- Fields dynamically populated by JS -->
                </div>
                <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()"><?php echo e($t('Cancel', 'إلغاء')); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo e($t('Save Changes', 'حفظ التغييرات')); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HIDDEN DELETE FORM -->
<form id="delete-form" method="POST" action="" style="display: none;">
    <?php echo csrf_field(); ?>
</form>

<script>
    const allEmployeesJson = <?php echo json_encode($employees->map(fn($e) => ['id' => $e->id, 'inst_id' => $e->institutional_id, 'name' => session('locale') === 'ar' ? $e->name_ar : $e->name_en])->toArray()) ?>;
    const allStudentsJson = <?php echo json_encode($students->map(fn($s) => ['id' => $s->id, 'inst_id' => $s->institutional_id, 'name' => session('locale') === 'ar' ? $s->name_ar : $s->name_en])->toArray()) ?>;
    const allDepartmentsJson = <?php echo json_encode($allDepartments, 15, 512) ?>;

    const currentLocale = '<?php echo e($locale); ?>';
    const isAr = (currentLocale === 'ar');

    function jsT(en, ar) {
        return isAr ? ar : en;
    }

    const sectionTitles = {
        'dashboard':       ['<?php echo e($t("Dashboard", "لوحة التحكم")); ?>', '<?php echo e($t("UIMP + RGMS Unified University Platform", "منصة الجامعة الموحدة وإدارة المجموعات البحثية")); ?>'],
        'students':        ['<?php echo e($t("Students Registry", "سجل الطلاب")); ?>', '<?php echo e($t("Enrolled students — bilingual profiles", "الطلاب المسجلون — ملفات ثنائية اللغة")); ?>'],
        'employees':       ['<?php echo e($t("Employees Registry", "سجل الموظفين")); ?>', '<?php echo e($t("Academic & administrative staff", "أعضاء هيئة التدريس والموظفون الإداريون")); ?>'],
        'structure':       ['<?php echo e($t("University Structure", "الهيكل التنظيمي")); ?>', '<?php echo e($t("Faculties, departments, programs & facilities", "الكليات، الأقسام، البرامج والمرافق")); ?>'],
        'research-groups': ['<?php echo e($t("Research Groups", "المجموعات البحثية")); ?>', '<?php echo e($t("RGMS — Research group management & allocations", "إدارة وتوزيع المجموعات البحثية")); ?>'],
        'projects':        ['<?php echo e($t("Research Projects", "المشاريع البحثية")); ?>', '<?php echo e($t("RGMS — Active and completed projects", "المشاريع النشطة والمنجزة")); ?>'],
        'publications':    ['<?php echo e($t("Publications", "المنشورات العلمية")); ?>', '<?php echo e($t("RGMS — Journals, conferences & books", "المجلات العلمية والمؤتمرات والكتب")); ?>'],
        'add-records':     ['<?php echo e($t("Add Records", "إضافة سجلات جديدة")); ?>', '<?php echo e($t("Register new students, staff, groups, members, or projects", "تسجيل طالب، موظف، مجموعة، مشروع جديد")); ?>'],
        'audit-logs':      ['<?php echo e($t("Compliance Audit Trails", "سجلات التدقيق الأمني")); ?>', '<?php echo e($t("System-wide immutable logs", "سجلات غير قابلة للتعديل لكامل النظام")); ?>'],
        'users-management': ['<?php echo e($t("Users & Roles Manager", "إدارة المستخدمين والصلاحيات")); ?>', '<?php echo e($t("Modify user security privileges dynamically with audit trails", "تعديل الصلاحيات الأمنية للمستخدمين وتغيير رتبهم فورياً")); ?>'],
        'api-docs':        ['<?php echo e($t("API Documentation", "توثيق الـ API المدمج")); ?>', '<?php echo e($t("Interactive Scribe API endpoints reference", "مرجع واجهة التطبيق التفاعلية")); ?>'],
    };

    function showSection(id, clickedEl) {
        // Hide all sections
        document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
        // Show target
        const target = document.getElementById('section-' + id);
        if (target) target.classList.add('active');
        // Update topbar
        const titles = sectionTitles[id] || [id, ''];
        document.getElementById('topbar-title').textContent = titles[0];
        document.getElementById('topbar-subtitle').textContent = titles[1];
        // Update sidebar active
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        if (clickedEl) clickedEl.classList.add('active');

        // Dynamically hide 'Add Record' button if already on the Add Records page
        const addRecordBtn = document.querySelector('.topbar-actions .btn-primary');
        if (addRecordBtn) {
            if (id === 'add-records') {
                addRecordBtn.style.display = 'none';
            } else {
                addRecordBtn.style.display = 'inline-flex';
            }
        }
    }

    function switchTab2(tabId, clickedBtn) {
        document.querySelectorAll('#section-add-records .tab-pane').forEach(p => {
            p.style.display = 'none';
            p.classList.remove('active');
        });
        const target = document.getElementById(tabId);
        if (target) { target.style.display = 'block'; target.classList.add('active'); }

        if (clickedBtn) {
            document.querySelectorAll('#add-tabs-bar .tab-btn').forEach(b => b.classList.remove('active'));
            clickedBtn.classList.add('active');
        } else {
            // find correct button
            document.querySelectorAll('#add-tabs-bar .tab-btn').forEach(b => {
                if (b.getAttribute('onclick') && b.getAttribute('onclick').includes(tabId)) {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });
        }
    }

    // ── MEMBER SELECTION SWITCHER ──────────────────────────────
    function toggleMemberSelection(val) {
        const staffGroup = document.getElementById('member-select-staff-group');
        const studentGroup = document.getElementById('member-select-student-group');
        const externalGroup = document.getElementById('member-select-external-group');

        const staffSel = document.getElementById('member-select-staff');
        const studentSel = document.getElementById('member-select-student');
        const externalInput = document.getElementById('member-select-external');

        // Reset name attributes to ensure only the selected input is sent
        staffSel.removeAttribute('name');
        studentSel.removeAttribute('name');
        externalInput.removeAttribute('name');

        if (val === 'Staff') {
            staffGroup.style.display = 'block';
            studentGroup.style.display = 'none';
            externalGroup.style.display = 'none';
            staffSel.setAttribute('name', 'member_uimp_id');
        } else if (val === 'Student') {
            staffGroup.style.display = 'none';
            studentGroup.style.display = 'block';
            externalGroup.style.display = 'none';
            studentSel.setAttribute('name', 'member_uimp_id');
        } else {
            staffGroup.style.display = 'none';
            studentGroup.style.display = 'none';
            externalGroup.style.display = 'block';
            externalInput.setAttribute('name', 'member_uimp_id');
        }
    }

    // Helper to get name
    function getMemberName(instId, type) {
        if (type === 'Staff' || !type) {
            const match = allEmployeesJson.find(e => e.inst_id === instId);
            if (match) return match.name;
        }
        if (type === 'Student' || !type) {
            const match = allStudentsJson.find(s => s.inst_id === instId);
            if (match) return match.name;
        }
        return instId;
    }

    // ── TABLE SEARCH ────────────────────────────────────────────
    function searchTable(input, tableId) {
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll(`#${tableId} tbody tr`);
        rows.forEach(row => {
            // Check if it's the empty state row
            if (row.querySelector('.empty-state')) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    // ── CRUD HANDLERS ───────────────────────────────────────────
    function confirmDelete(url) {
        const msg = jsT(
            '🔒 Are you sure you want to permanently delete this record? This action will write to audit logs.',
            '🔒 هل أنت متأكد من رغبتك في حذف هذا السجل نهائياً؟ سيتم توثيق هذه الحركة في سجلات التدقيق الأمني.'
        );
        if (confirm(msg)) {
            const form = document.getElementById('delete-form');
            form.action = url;
            form.submit();
        }
    }

    function closeEditModal() {
        document.getElementById('edit-modal').style.display = 'none';
    }

    function openEditStudentModal(id, instId, natId, nameEn, nameAr, dob, gender, admDate, status) {
        document.getElementById('modal-title').textContent = jsT('🎓 Edit Student Profile', '🎓 تعديل ملف الطالب');
        document.getElementById('edit-form').action = `/students/${id}/edit`;
        
        const container = document.getElementById('modal-fields-container');
        container.innerHTML = `
            <div class="form-group">
                <label>${jsT('Institutional ID *', 'الرقم الجامعي *')}</label>
                <input type="text" name="institutionalId" class="form-input" value="${instId}" required>
            </div>
            <div class="form-group">
                <label>${jsT('National ID *', 'الرقم الوطني *')}</label>
                <input type="text" name="nationalId" class="form-input" value="${natId}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Full Name (English) *', 'الاسم الكامل بالإنجليزية *')}</label>
                <input type="text" name="nameEn" class="form-input" value="${nameEn}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Full Name (Arabic) *', 'الاسم الكامل بالعربية *')}</label>
                <input type="text" name="nameAr" class="form-input" value="${nameAr}" style="direction:rtl" required>
            </div>
            <div class="form-group">
                <label>${jsT('Date of Birth *', 'تاريخ الميلاد *')}</label>
                <input type="date" name="dateOfBirth" class="form-input" value="${dob}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Gender *', 'الجنس *')}</label>
                <select name="gender" class="form-select" required>
                    <option value="MALE" ${gender === 'MALE' ? 'selected' : ''}>${jsT('Male / ذكر', 'ذكر')}</option>
                    <option value="FEMALE" ${gender === 'FEMALE' ? 'selected' : ''}>${jsT('Female / أنثى', 'أنثى')}</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Admission Date *', 'تاريخ القبول والانتساب *')}</label>
                <input type="date" name="admissionDate" class="form-input" value="${admDate}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Enrollment Status *', 'حالة قيد الطالب *')}</label>
                <select name="enrollmentStatus" class="form-select" required>
                    <option value="ACTIVE" ${status === 'ACTIVE' ? 'selected' : ''}>${jsT('Active / نشط', 'نشط')}</option>
                    <option value="SUSPENDED" ${status === 'SUSPENDED' ? 'selected' : ''}>${jsT('Suspended / موقوف مؤقتاً', 'موقوف مؤقتاً')}</option>
                    <option value="GRADUATED" ${status === 'GRADUATED' ? 'selected' : ''}>${jsT('Graduated / خريج', 'خريج')}</option>
                    <option value="WITHDRAWN" ${status === 'WITHDRAWN' ? 'selected' : ''}>${jsT('Withdrawn / منسحب', 'منسحب')}</option>
                </select>
            </div>
        `;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function openEditEmployeeModal(id, instId, staffType, nameEn, nameAr, academicRank, hireDate) {
        document.getElementById('modal-title').textContent = jsT('💼 Edit Employee Profile', '💼 تعديل ملف الموظف');
        document.getElementById('edit-form').action = `/employees/${id}/edit`;
        
        const container = document.getElementById('modal-fields-container');
        container.innerHTML = `
            <div class="form-group">
                <label>${jsT('Institutional Staff ID *', 'الرقم الوظيفي *')}</label>
                <input type="text" name="institutionalId" class="form-input" value="${instId}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Staff Type *', 'نوع الوظيفة والصفة *')}</label>
                <select name="staffType" class="form-select" required>
                    <option value="ACADEMIC" ${staffType === 'ACADEMIC' ? 'selected' : ''}>${jsT('Academic Staff', 'هيئة تدريس')}</option>
                    <option value="NON_ACADEMIC" ${staffType === 'NON_ACADEMIC' ? 'selected' : ''}>${jsT('Administrative Staff', 'إداري')}</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Full Name (English) *', 'الاسم الكامل بالإنجليزية *')}</label>
                <input type="text" name="nameEn" class="form-input" value="${nameEn}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Full Name (Arabic) *', 'الاسم الكامل بالعربية *')}</label>
                <input type="text" name="nameAr" class="form-input" value="${nameAr}" style="direction:rtl" required>
            </div>
            <div class="form-group">
                <label>${jsT('Academic Rank (Academics Only)', 'الدرجة العلمية الأكاديمية')}</label>
                <select name="academicRank" class="form-select">
                    <option value="" ${!academicRank ? 'selected' : ''}>None</option>
                    <option value="PROFESSOR" ${academicRank === 'PROFESSOR' ? 'selected' : ''}>Professor / أستاذ</option>
                    <option value="ASSOCIATE_PROFESSOR" ${academicRank === 'ASSOCIATE_PROFESSOR' ? 'selected' : ''}>Associate Professor / أستاذ مشارك</option>
                    <option value="ASSISTANT_PROFESSOR" ${academicRank === 'ASSISTANT_PROFESSOR' ? 'selected' : ''}>Assistant Professor / أستاذ مساعد</option>
                    <option value="LECTURER" ${academicRank === 'LECTURER' ? 'selected' : ''}>Lecturer / محاضر</option>
                    <option value="ASSISTANT_LECTURER" ${academicRank === 'ASSISTANT_LECTURER' ? 'selected' : ''}>Assistant Lecturer / معيد</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Hiring Date *', 'تاريخ التعيين والمباشرة *')}</label>
                <input type="date" name="hireDate" class="form-input" value="${hireDate}" required>
            </div>
        `;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function openEditGroupModal(id, groupName, field, area, status, piId, deptRef, budget, memberships) {
        document.getElementById('modal-title').textContent = jsT('🔬 Edit Research Group & Members', '🔬 تعديل المجموعة البحثية والأعضاء');
        document.getElementById('edit-form').action = `/research-groups/${id}/edit`;
        
        // Generate employees option list
        let piOptions = '';
        allEmployeesJson.forEach(emp => {
            piOptions += `<option value="${emp.inst_id}" ${emp.inst_id === piId ? 'selected' : ''}>${emp.name} (${emp.inst_id})</option>`;
        });

        // Generate department option list
        let deptOptions = `<option value="">${jsT('None', 'لا شيء')}</option>`;
        allDepartmentsJson.forEach(dept => {
            deptOptions += `<option value="${dept.id}" ${dept.id === deptRef ? 'selected' : ''}>${isAr ? dept.name_ar : dept.name_en}</option>`;
        });

        const container = document.getElementById('modal-fields-container');
        
        let membersHtml = '';
        if (memberships && memberships.length > 0) {
            memberships.forEach(m => {
                let mName = getMemberName(m.member_uimp_id, m.member_type);
                membersHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:6px; grid-column: 1 / -1;">
                        <div style="flex:1; font-weight:600; font-size:0.82rem; color:var(--text);">
                            👤 ${mName} (${m.member_type})
                        </div>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <select id="role-select-${m.id}" class="form-select" style="padding:0.25rem; font-size:0.75rem; width:120px; height:auto;">
                                <option value="PI" ${m.role === 'PI' ? 'selected' : ''}>PI</option>
                                <option value="Co-I" ${m.role === 'Co-I' ? 'selected' : ''}>Co-I</option>
                                <option value="Research-Assistant" ${m.role === 'Research-Assistant' ? 'selected' : ''}>Research Assistant</option>
                                <option value="Graduate-Researcher" ${m.role === 'Graduate-Researcher' ? 'selected' : ''}>Graduate Researcher</option>
                                <option value="External-Collaborator" ${m.role === 'External-Collaborator' ? 'selected' : ''}>External</option>
                            </select>
                            <input type="number" id="workload-${m.id}" class="form-input" style="padding:0.25rem; font-size:0.75rem; width:55px; height:auto;" value="${m.workload_percentage}" min="0" max="100">
                            <button type="button" class="btn btn-primary btn-sm" style="padding:4px 8px;" onclick="updateMemberDetails('${m.id}')" title="Save changes">💾</button>
                            <button type="button" class="btn btn-red btn-sm" style="padding:4px 8px;" onclick="confirmDelete('/group-memberships/${m.id}/delete')" title="Remove Member">🗑️</button>
                        </div>
                    </div>
                `;
            });
        }

        // Inline Add Member inputs
        let memberAddOptions = '';
        allEmployeesJson.forEach(emp => {
            memberAddOptions += `<option value="Staff|${emp.inst_id}">💼 Staff: ${emp.name}</option>`;
        });
        allStudentsJson.forEach(stu => {
            memberAddOptions += `<option value="Student|${stu.inst_id}">🎓 Student: ${stu.name}</option>`;
        });

        container.innerHTML = `
            <!-- Group Basics -->
            <div class="form-full" style="font-weight:700; border-bottom:1px solid var(--border); padding-bottom:0.5rem; margin-bottom:0.5rem; color:var(--accent);">
                ⚙️ ${jsT('Group Settings', 'إعدادات المجموعة')}
            </div>
            <div class="form-group">
                <label>${jsT('Group Name *', 'اسم المجموعة *')}</label>
                <input type="text" name="group_name" class="form-input" value="${groupName}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Research Field *', 'حقل البحث والتخصص *')}</label>
                <input type="text" name="research_field" class="form-input" value="${field}" required>
            </div>
            <div class="form-group form-full">
                <label>${jsT('Research Area *', 'ملخص أهداف المجموعة *')}</label>
                <input type="text" name="research_area" class="form-input" value="${area}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Lifecycle Status *', 'حالة المجموعة *')}</label>
                <select name="status" class="form-select" required>
                    <option value="Active" ${status === 'Active' ? 'selected' : ''}>Active / نشط</option>
                    <option value="Draft" ${status === 'Draft' ? 'selected' : ''}>Draft / مسودة</option>
                    <option value="Suspended" ${status === 'Suspended' ? 'selected' : ''}>Suspended / معلق</option>
                    <option value="Archived" ${status === 'Archived' ? 'selected' : ''}>Archived / مؤرشف</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Group Leader (PI) *', 'رئيس المجموعة (PI) *')}</label>
                <select name="pi_staff_id" class="form-select" required>
                    ${piOptions}
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Primary Department Reference', 'القسم الأكاديمي الراعي')}</label>
                <select name="department_ref_id" class="form-select">
                    ${deptOptions}
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Budget Allocation (LYD)', 'الميزانية المالية (دينار ليبي)')}</label>
                <input type="number" step="0.01" name="budget_allocation" class="form-input" value="${budget || ''}">
            </div>

            <!-- Group Members list & Inline management -->
            <div class="form-full" style="font-weight:700; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem; color:var(--accent);">
                👥 ${jsT('Group Members', 'أعضاء المجموعة')}
            </div>
            ${membersHtml || `<div class="form-full" style="color:var(--text-muted); font-size:0.85rem; padding:0.5rem 0;">${jsT('No extra members assigned. Use the form below to add members.', 'لا يوجد أعضاء مضافين حالياً. استخدم النموذج أدناه لإضافة الأعضاء.')}</div>`}

            <!-- Quick Add Member -->
            <div class="form-full" style="font-weight:600; font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-top:0.5rem; margin-bottom:0.3rem;">
                ➕ ${jsT('Add Member to this Group', 'إضافة عضو جديد إلى المجموعة')}
            </div>
            <div class="form-full" style="background:#f1f5f9; padding:0.85rem; border-radius:10px; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:grid; grid-template-columns:1.5fr 1fr 1fr; gap:0.5rem;">
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Select Person', 'اختر الشخص')}</label>
                        <select id="quick-add-member-select" class="form-select" style="padding:0.3rem; font-size:0.8rem; height:auto;">
                            ${memberAddOptions}
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Role', 'الدور البحثي')}</label>
                        <select id="quick-add-role-select" class="form-select" style="padding:0.3rem; font-size:0.8rem; height:auto;">
                            <option value="Co-I">Co-Investigator</option>
                            <option value="Research-Assistant">Research Assistant</option>
                            <option value="Graduate-Researcher">Graduate Researcher</option>
                            <option value="External-Collaborator">External Collaborator</option>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Workload %', 'عبء العمل %')}</label>
                        <input type="number" id="quick-add-workload" class="form-input" style="padding:0.3rem; font-size:0.8rem; height:auto;" value="20" min="0" max="100">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.72rem; color:var(--text-muted);">${jsT('Applies instantly.', 'تُطبق التغييرات فوراً.')}</span>
                    <button type="button" class="btn btn-primary btn-sm btn-green" onclick="submitQuickMemberAdd('${id}')">➕ ${jsT('Add Member', 'إضافة العضو')}</button>
                </div>
            </div>
        `;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function submitQuickMemberAdd(groupId) {
        const personVal = document.getElementById('quick-add-member-select').value;
        const parts = personVal.split('|');
        const mType = parts[0];
        const mId = parts[1];
        
        const role = document.getElementById('quick-add-role-select').value;
        const workload = document.getElementById('quick-add-workload').value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/group-memberships/create';
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="group_id" value="${groupId}">
            <input type="hidden" name="member_uimp_id" value="${mId}">
            <input type="hidden" name="member_type" value="${mType}">
            <input type="hidden" name="role" value="${role}">
            <input type="hidden" name="workload_percentage" value="${workload}">
            <input type="hidden" name="start_date" value="${new Date().toISOString().split('T')[0]}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }

    function updateMemberDetails(membershipId) {
        const role = document.getElementById('role-select-' + membershipId).value;
        const workload = document.getElementById('workload-' + membershipId).value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/group-memberships/${membershipId}/edit`;
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="role" value="${role}">
            <input type="hidden" name="workload_percentage" value="${workload}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }

    // ── PROJECTS EDIT & CONTRIBUTORS ────────────────────────────
    function openEditProjectModal(id, title, agency, budget, start, end, status, contributors) {
        document.getElementById('modal-title').textContent = jsT('📁 Edit Research Project & Contributors', '📁 تعديل المشروع البحثي والمساهمين');
        document.getElementById('edit-form').action = `/projects/${id}/edit`;
        
        const container = document.getElementById('modal-fields-container');
        
        let contribHtml = '';
        if (contributors && contributors.length > 0) {
            contributors.forEach(c => {
                let cName = getMemberName(c.member_uimp_id);
                contribHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:6px; grid-column: 1 / -1;">
                        <div style="flex:1; font-weight:600; font-size:0.8rem; color:var(--text);">
                            👤 ${cName}
                        </div>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="text" id="contrib-role-input-${c.id}" class="form-input" style="padding:0.25rem; font-size:0.75rem; width:150px; height:auto;" value="${c.contributor_role || ''}" placeholder="Role in project">
                            <button type="button" class="btn btn-primary btn-sm" style="padding:4px 8px;" onclick="updateContributorDetails('${c.id}')" title="Save changes">💾</button>
                            <button type="button" class="btn btn-red btn-sm" style="padding:4px 8px;" onclick="confirmDelete('/project-contributors/${c.id}/delete')" title="Remove Contributor">🗑️</button>
                        </div>
                    </div>
                `;
            });
        }

        let contributorAddOptions = '';
        allEmployeesJson.forEach(emp => {
            contributorAddOptions += `<option value="Staff|${emp.inst_id}">💼 Staff: ${emp.name}</option>`;
        });
        allStudentsJson.forEach(stu => {
            contributorAddOptions += `<option value="Student|${stu.inst_id}">🎓 Student: ${stu.name}</option>`;
        });

        container.innerHTML = `
            <!-- Project Settings -->
            <div class="form-full" style="font-weight:700; border-bottom:1px solid var(--border); padding-bottom:0.5rem; margin-bottom:0.5rem; color:var(--accent);">
                ⚙️ ${jsT('Project Settings', 'إعدادات المشروع')}
            </div>
            <div class="form-group form-full">
                <label>${jsT('Project Title *', 'عنوان المشروع *')}</label>
                <input type="text" name="title" class="form-input" value="${title}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Funding Agency *', 'الجهة الممولة *')}</label>
                <input type="text" name="funding_agency" class="form-input" value="${agency}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Budget (LYD) *', 'الميزانية (دينار ليبي) *')}</label>
                <input type="number" step="0.01" name="budget" class="form-input" value="${budget}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Start Date *', 'تاريخ البدء *')}</label>
                <input type="date" name="start_date" class="form-input" value="${start}" required>
            </div>
            <div class="form-group">
                <label>${jsT('End Date *', 'تاريخ الانتهاء *')}</label>
                <input type="date" name="end_date" class="form-input" value="${end}" required>
            </div>
            <div class="form-group form-full">
                <label>${jsT('Lifecycle Status *', 'حالة المشروع *')}</label>
                <select name="status" class="form-select" required>
                    <option value="Active" ${status === 'Active' ? 'selected' : ''}>Active / نشط</option>
                    <option value="Planning" ${status === 'Planning' ? 'selected' : ''}>Planning / تخطيط</option>
                    <option value="On-Hold" ${status === 'On-Hold' ? 'selected' : ''}>On-Hold / معلق</option>
                    <option value="Completed" ${status === 'Completed' ? 'selected' : ''}>Completed / منجز</option>
                    <option value="Terminated" ${status === 'Terminated' ? 'selected' : ''}>Terminated / ملغى</option>
                </select>
            </div>

            <!-- Project Contributors -->
            <div class="form-full" style="font-weight:700; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem; color:var(--accent);">
                👥 ${jsT('Project Contributors', 'المساهمون بالمشروع')}
            </div>
            ${contribHtml || `<div class="form-full" style="color:var(--text-muted); font-size:0.85rem; padding:0.5rem 0;">${jsT('No contributors assigned. Use the form below to assign them.', 'لا يوجد مساهمون معينون حالياً. استخدم النموذج أدناه.')}</div>`}

            <!-- Add Contributor -->
            <div class="form-full" style="font-weight:600; font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-top:0.5rem; margin-bottom:0.3rem;">
                ➕ ${jsT('Assign Contributor', 'تعيين مساهم جديد في المشروع')}
            </div>
            <div class="form-full" style="background:#f1f5f9; padding:0.85rem; border-radius:10px; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:grid; grid-template-columns:1.5fr 1.5fr; gap:0.5rem;">
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Select Person', 'اختر الشخص')}</label>
                        <select id="quick-add-contrib-select" class="form-select" style="padding:0.3rem; font-size:0.8rem; height:auto;">
                            ${contributorAddOptions}
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Role in Project', 'الدور في المشروع')}</label>
                        <input type="text" id="quick-add-contrib-role" class="form-input" style="padding:0.3rem; font-size:0.8rem; height:auto;" placeholder="e.g. Lead Researcher, Assistant">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.72rem; color:var(--text-muted);">${jsT('Applies instantly.', 'تُطبق التغييرات فوراً.')}</span>
                    <button type="button" class="btn btn-primary btn-sm btn-green" onclick="submitQuickContributorAdd('${id}')">➕ ${jsT('Add Contributor', 'تعيين المساهم')}</button>
                </div>
            </div>
        `;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function submitQuickContributorAdd(projectId) {
        const personVal = document.getElementById('quick-add-contrib-select').value;
        const parts = personVal.split('|');
        const cId = parts[1];
        const role = document.getElementById('quick-add-contrib-role').value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/project-contributors/create';
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="project_id" value="${projectId}">
            <input type="hidden" name="member_uimp_id" value="${cId}">
            <input type="hidden" name="contributor_role" value="${role}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function updateContributorDetails(contribId) {
        const role = document.getElementById('contrib-role-input-' + contribId).value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/project-contributors/${contribId}/edit`;
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="contributor_role" value="${role}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // ── PUBLICATIONS EDIT & AUTHORS ─────────────────────────────
    function openEditPublicationModal(id, title, type, year, status, doi, journal, publisher, authors) {
        document.getElementById('modal-title').textContent = jsT('📚 Edit Publication Info & Authors', '📚 تعديل المنشور العلمي والمؤلفين');
        document.getElementById('edit-form').action = `/publications/${id}/edit`;
        
        const container = document.getElementById('modal-fields-container');
        
        let authorsHtml = '';
        if (authors && authors.length > 0) {
            authors.forEach(a => {
                let aName = getMemberName(a.member_uimp_id);
                authorsHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:6px; grid-column: 1 / -1;">
                        <div style="flex:1; font-weight:600; font-size:0.8rem; color:var(--text);">
                            👤 ${aName}
                        </div>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="number" id="author-order-input-${a.id}" class="form-input" style="padding:0.25rem; font-size:0.75rem; width:50px; height:auto;" value="${a.author_order}" min="1">
                            <input type="text" id="author-contrib-input-${a.id}" class="form-input" style="padding:0.25rem; font-size:0.75rem; width:120px; height:auto;" value="${a.contribution_type || ''}" placeholder="Contribution">
                            <button type="button" class="btn btn-primary btn-sm" style="padding:4px 8px;" onclick="updateAuthorDetails('${a.id}')" title="Save changes">💾</button>
                            <button type="button" class="btn btn-red btn-sm" style="padding:4px 8px;" onclick="confirmDelete('/publication-authors/${a.id}/delete')" title="Remove Author">🗑️</button>
                        </div>
                    </div>
                `;
            });
        }

        let authorAddOptions = '';
        allEmployeesJson.forEach(emp => {
            authorAddOptions += `<option value="Staff|${emp.inst_id}">💼 Staff: ${emp.name}</option>`;
        });
        allStudentsJson.forEach(stu => {
            authorAddOptions += `<option value="Student|${stu.inst_id}">🎓 Student: ${stu.name}</option>`;
        });

        container.innerHTML = `
            <!-- Publication Settings -->
            <div class="form-full" style="font-weight:700; border-bottom:1px solid var(--border); padding-bottom:0.5rem; margin-bottom:0.5rem; color:var(--accent);">
                ⚙️ ${jsT('Publication Settings', 'إعدادات المنشور')}
            </div>
            <div class="form-group form-full">
                <label>${jsT('Publication Title *', 'عنوان المنشور العلمي *')}</label>
                <input type="text" name="title" class="form-input" value="${title}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Publication Type *', 'نوع ومكان النشر *')}</label>
                <select name="publication_type" class="form-select" required>
                    <option value="Journal-Article" ${type === 'Journal-Article' ? 'selected' : ''}>Journal Article / مجلة</option>
                    <option value="Conference-Paper" ${type === 'Conference-Paper' ? 'selected' : ''}>Conference Paper / مؤتمر</option>
                    <option value="Book-Chapter" ${type === 'Book-Chapter' ? 'selected' : ''}>Book Chapter / كتاب</option>
                    <option value="Technical-Report" ${type === 'Technical-Report' ? 'selected' : ''}>Technical Report / تقرير</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('Publication Year *', 'سنة النشر *')}</label>
                <input type="number" name="publication_year" class="form-input" value="${year}" required>
            </div>
            <div class="form-group">
                <label>${jsT('Lifecycle Status *', 'حالة النشر الحالية *')}</label>
                <select name="status" class="form-select" required>
                    <option value="Published" ${status === 'Published' ? 'selected' : ''}>Published / منشور</option>
                    <option value="Accepted" ${status === 'Accepted' ? 'selected' : ''}>Accepted / مقبول</option>
                    <option value="Submitted" ${status === 'Submitted' ? 'selected' : ''}>Submitted / مرسل</option>
                    <option value="Under-Review" ${status === 'Under-Review' ? 'selected' : ''}>Under-Review / تحكيم</option>
                    <option value="In-Preparation" ${status === 'In-Preparation' ? 'selected' : ''}>In-Preparation / إعداد</option>
                </select>
            </div>
            <div class="form-group">
                <label>${jsT('DOI Identifier', 'معرف الغرض الرقمي (DOI)')}</label>
                <input type="text" name="doi" class="form-input" value="${doi}">
            </div>
            <div class="form-group">
                <label>${jsT('Journal Name', 'اسم المجلة العلمية')}</label>
                <input type="text" name="journal_name" class="form-input" value="${journal}">
            </div>
            <div class="form-group form-full">
                <label>${jsT('Publisher', 'دار النشر')}</label>
                <input type="text" name="publisher" class="form-input" value="${publisher}">
            </div>

            <!-- Authors Manager -->
            <div class="form-full" style="font-weight:700; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem; color:var(--accent);">
                👥 ${jsT('Publication Authors', 'المؤلفون والمشاركون')}
            </div>
            ${authorsHtml || `<div class="form-full" style="color:var(--text-muted); font-size:0.85rem; padding:0.5rem 0;">${jsT('No authors assigned. Use the form below to add co-authors.', 'لا يوجد مؤلفون معينون حالياً. استخدم النموذج أدناه.')}</div>`}

            <!-- Add Author -->
            <div class="form-full" style="font-weight:600; font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-top:0.5rem; margin-bottom:0.3rem;">
                ➕ ${jsT('Assign Author', 'تعيين مؤلف أو قلم مشارك جديد')}
            </div>
            <div class="form-full" style="background:#f1f5f9; padding:0.85rem; border-radius:10px; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:grid; grid-template-columns:1.2fr 0.5fr 1fr; gap:0.5rem;">
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Select Person', 'اختر الشخص')}</label>
                        <select id="quick-add-author-select" class="form-select" style="padding:0.3rem; font-size:0.8rem; height:auto;">
                            ${authorAddOptions}
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Order', 'الترتيب')}</label>
                        <input type="number" id="quick-add-author-order" class="form-input" style="padding:0.3rem; font-size:0.8rem; height:auto;" value="1" min="1">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <label style="font-size:0.7rem; font-weight:600; color:var(--text-muted)">${jsT('Contribution', 'طبيعة المساهمة')}</label>
                        <input type="text" id="quick-add-author-contrib" class="form-input" style="padding:0.3rem; font-size:0.8rem; height:auto;" placeholder="e.g. Writer, Analysis">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.72rem; color:var(--text-muted);">${jsT('Applies instantly.', 'تُطبق التغييرات فوراً.')}</span>
                    <button type="button" class="btn btn-primary btn-sm btn-green" onclick="submitQuickAuthorAdd('${id}')">➕ ${jsT('Add Author', 'تعيين المؤلف')}</button>
                </div>
            </div>
        `;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function submitQuickAuthorAdd(pubId) {
        const personVal = document.getElementById('quick-add-author-select').value;
        const parts = personVal.split('|');
        const authId = parts[1];
        
        const order = document.getElementById('quick-add-author-order').value;
        const contrib = document.getElementById('quick-add-author-contrib').value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/publication-authors/create';
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="publication_id" value="${pubId}">
            <input type="hidden" name="member_uimp_id" value="${authId}">
            <input type="hidden" name="author_order" value="${order}">
            <input type="hidden" name="contribution_type" value="${contrib}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function updateAuthorDetails(authorId) {
        const order = document.getElementById('author-order-input-' + authorId).value;
        const contrib = document.getElementById('author-contrib-input-' + authorId).value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/publication-authors/${authorId}/edit`;
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('input[name="_token"]').value;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="author_order" value="${order}">
            <input type="hidden" name="contribution_type" value="${contrib}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Persist tab state after submission/validation errors
    window.onload = function() {
        <?php if(session('active_section')): ?>
            const activeSec = "<?php echo e(session('active_section')); ?>";
            const navBtn = document.querySelector(`[onclick*="${activeSec}"]`);
            showSection(activeSec, navBtn);
            <?php if(session('active_tab')): ?>
                switchTab2("<?php echo e(session('active_tab')); ?>");
            <?php endif; ?>
        <?php elseif($errors->any()): ?>
            const navBtn = document.querySelector(`[onclick*="add-records"]`);
            showSection('add-records', navBtn);
        <?php endif; ?>
    };
</script>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH /Users/qusay-diaf/projects/laravel-projects/laravel-v2/TeamNextStep/resources/views/welcome.blade.php ENDPATH**/ ?>
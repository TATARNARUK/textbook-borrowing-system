<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InsightHub</title>
    <style>
        :root {
            --primary-blue: #0d6efd;
            --bg-light: #f8f9fa;
            --text-dark: #333;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            --sidebar-active-bg: rgba(13, 110, 253, 0.1);
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            box-sizing: border-box;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* 🔥 สไตล์สำหรับแถมด้านข้าง (Sidebar) */
        .sidebar {
            width: 250px;
            background-color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            text-decoration: none;
            color: var(--text-dark);
            display: block;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .sidebar-nav li.active a {
            background-color: var(--sidebar-active-bg);
            color: var(--primary-blue);
            font-weight: bold;
        }

        .sidebar-nav a:hover {
            background-color: var(--bg-light);
        }

        .sub-menu {
            margin-top: 10px;
            margin-left: 20px;
        }

        .badge {
            background-color: #dc3545;
            color: #fff;
            padding: 3px 6px;
            border-radius: 12px;
            font-size: 0.8rem;
            float: right;
        }

        .user-profile-sidebar {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            background-color: var(--primary-blue);
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .user-name {
            font-weight: bold;
            margin: 0;
        }

        .user-date {
            font-size: 0.8rem;
            color: #888;
            margin: 0;
        }

        /* 🔥 สไตล์สำหรับพื้นที่เนื้อหาหลัก (Main Content Area) */
        .content {
            flex: 1;
            padding: 30px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-input {
            width: 400px;
            padding: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 20px;
            background-color: rgba(0, 0, 0, 0.02);
        }

        .header-icons {
            display: flex;
            gap: 15px;
        }

        .icon-placeholder {
            font-size: 1.2rem;
            cursor: pointer;
        }

        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-to {
            color: var(--text-dark);
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 0 10px;
        }

        .welcome-message {
            margin: 0;
            flex: 1;
        }

        .highlight {
            color: #dc3545;
            font-weight: bold;
        }

        .view-detail-btn {
            background-color: var(--primary-blue);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        .stats-section {
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stats-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
            font-size: 0.9rem;
            color: #888;
        }

        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-top: 5px;
        }

        .tasks-section {
            margin-bottom: 30px;
        }

        .task-table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .task-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .task-row.header {
            font-weight: bold;
            color: #888;
        }

        .team-avatars {
            display: flex;
            gap: 5px;
        }

        .team-avatar-placeholder {
            width: 25px;
            height: 25px;
            background-color: #ddd;
            border-radius: 50%;
        }

        .chart-section, .top-performance-section {
            margin-bottom: 30px;
        }

        .chart-placeholder, .top-performance-placeholder {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="main-container">
        <aside class="sidebar">
            <div class="logo">LOGO InsightHub</div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="#">Dashboard</a></li>
                    <li><a href="#">Inboxes</a></li>
                    <li><a href="#">Performances</a></li>
                    <li>
                        <a href="#">Projects</a>
                        <ul class="sub-menu">
                            <li><a href="#">Active Project</a></li>
                            <li><a href="#">Project Done</a></li>
                            <li><a href="#">Project On Hold</a></li>
                        </ul>
                    </li>
                    <li><a href="#">Employee Task</a></li>
                    <li><a href="#">Absence</a></li>
                    <li><a href="#">Analytics</a></li>
                    <li><a href="#">Client List</a></li>
                    <li><a href="#">Notification <span class="badge">4</span></a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </nav>
            <div class="user-profile-sidebar">
                <div class="user-avatar-placeholder">M</div>
                <div class="user-info">
                    <p class="user-name">Hey, Markus</p>
                    <p class="user-date">Sunday, June 25, 2024</p>
                </div>
            </div>
        </aside>

        <main class="content">
            <header class="content-header">
                <input type="text" placeholder="Start searching here..." class="search-input">
                <div class="header-icons">
                    <span class="icon-placeholder">🔔</span>
                    <span class="icon-placeholder">👤</span>
                </div>
            </header>

            <section class="welcome-section">
                <div class="welcome-card">
                    <p class="welcome-to">Dear Manager</p>
                    <p class="welcome-message">We have observed a decline in <span class="highlight">[Hermawan]</span>'s performance over the past 2 weeks.</p>
                    <button class="view-detail-btn">View Detail</button>
                </div>
            </section>

            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stats-card">Active Employees: <span class="stat-number">547</span></div>
                    <div class="stats-card">Number of Projects: <span class="stat-number">339</span></div>
                    <div class="stats-card">Number of Task: <span class="stat-number">147</span></div>
                    <div class="stats-card">Target Percentage Completed: <span class="stat-number">89.75%</span></div>
                </div>
            </section>

            <section class="tasks-section">
                <h4>On Going Task</h4>
                <div class="task-table-container">
                    <div class="task-row header">
                        <span>Project</span>
                        <span>Team</span>
                        <span>Status</span>
                        <span>Progress</span>
                        <span>Due Date</span>
                    </div>
                    <div class="task-row">
                        <span>Journey Scarves</span>
                        <span><div class="team-avatars"></div></span>
                        <span>Rebonding</span>
                        <span>100%</span>
                        <span>Aug 17, 2024</span>
                    </div>
                    <div class="task-row">
                        <span>Edifier</span>
                        <span><div class="team-avatars"></div></span>
                        <span>Web Design</span>
                        <span>50%</span>
                        <span>Aug 12, 2024</span>
                    </div>
                    </div>
            </section>

            <section class="chart-section">
                <h4>Graphs and Analysis</h4>
                <div class="chart-placeholder">Chart will be here (use JS library like Chart.js)</div>
            </section>

            <section class="top-performance-section">
                <h4>Top Performance</h4>
                <div class="top-performance-placeholder">Top Performance List here</div>
            </section>

        </main>
    </div>

</body>
</html>
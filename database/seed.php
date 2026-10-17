<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';

use PortalBackend\Config;
use PortalBackend\Database;

echo "=== Seeding Clipan Portal Database ===\n";

try {
    Config::load(dirname(__DIR__) . '/.env');
    $pdo = Database::getConnection();

    // 1. Seed Users
    echo "[1/2] Seeding Portal Users...\n";
    $users = [
        [
            'username' => 'Admin',
            'full_name' => 'Administrator',
            'email' => 'admin@clipan.co.id',
            'password' => 'Cl1p4nN4dm1n123!',
            'role' => 'Super User',
        ],
        [
            'username' => 'wahyu.aji',
            'full_name' => 'Wahyu Aji',
            'email' => 'wahyu.aji@clipan.co.id',
            'password' => 'Cl1p4nNUs3r123!',
            'role' => 'Admin',
        ],
        [
            'username' => 'dwi.salindri',
            'full_name' => 'Dwi Salindri',
            'email' => 'dwi.salindri@clipan.co.id',
            'password' => 'Cl1p4nNUs3r123!',
            'role' => 'Admin',
        ],
        [
            'username' => 'satria.finandar',
            'full_name' => 'Satria Finandar',
            'email' => 'satria.finandar@clipan.co.id',
            'password' => 'Cl1p4nNUs3r123!',
            'role' => 'Admin',
        ],
    ];

    $userStmt = $pdo->prepare("
        INSERT INTO portal_users 
            (username, full_name, email, password_hash, role, is_active)
        VALUES 
            (:username, :full_name, :email, :password_hash, :role, 1)
        ON DUPLICATE KEY UPDATE 
            full_name = VALUES(full_name),
            email = VALUES(email),
            password_hash = VALUES(password_hash),
            role = VALUES(role),
            is_active = 1,
            failed_attempts = 0,
            locked_until = NULL
    ");

    foreach ($users as $u) {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT);
        $userStmt->execute([
            'username' => $u['username'],
            'full_name' => $u['full_name'],
            'email' => $u['email'],
            'password_hash' => $hash,
            'role' => $u['role'],
        ]);
        echo "  - User: {$u['username']} ({$u['role']}) seeded.\n";
    }

    // 2. Seed Initial Applications
    echo "[2/2] Seeding Portal Applications Registry...\n";
    $apps = [
        // SECURITY (SOC)
        [
            'app_id' => 'sentinel-soc',
            'name' => 'Sentinel SOC Command Center',
            'description' => 'Unified Firewall, CrowdStrike EDR, CyberArk PAM, and DB Telemetry.',
            'url' => '/overview',
            'category' => 'security',
            'icon_name' => 'ShieldCheck',
            'badge' => 'NEW v2.0',
            'accent_color' => '#06b6d4',
            'bg_gradient' => 'linear-gradient(135deg, #06b6d4, #0284c7)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 42,
        ],
        [
            'app_id' => 'fmg',
            'name' => 'FortiManager Central Portal',
            'description' => 'Centralized firewall policy, firmware deployment & Fortinet device management.',
            'url' => '/fmg/',
            'category' => 'security',
            'icon_name' => 'ShieldAlert',
            'badge' => null,
            'accent_color' => '#f43f5e',
            'bg_gradient' => 'linear-gradient(135deg, #fb7185, #e11d48)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 28,
        ],
        [
            'app_id' => 'forti-isfw-detail',
            'name' => 'FortiGate ISFW Analytics',
            'description' => 'Internal segmentation firewall deep packet inspection & session audits.',
            'url' => '/forti-isfw-detail/',
            'category' => 'security',
            'icon_name' => 'Flame',
            'badge' => null,
            'accent_color' => '#ea580c',
            'bg_gradient' => 'linear-gradient(135deg, #f97316, #c2410c)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 19,
        ],
        [
            'app_id' => 'traffic-fgdc',
            'name' => 'Data Center Firewall (FGDC)',
            'description' => 'Core Data Center ingress & egress firewall traffic telemetry.',
            'url' => '/traffic-fgdc/',
            'category' => 'security',
            'icon_name' => 'Radio',
            'badge' => null,
            'accent_color' => '#d97706',
            'bg_gradient' => 'linear-gradient(135deg, #fbbf24, #b45309)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 15,
        ],
        [
            'app_id' => 'traffic-isfw',
            'name' => 'Traffic ISFW Telemetry',
            'description' => 'Bandwidth utilization & live session graphs for internal firewall zones.',
            'url' => '/traffic-isfw/',
            'category' => 'security',
            'icon_name' => 'Sliders',
            'badge' => null,
            'accent_color' => '#059669',
            'bg_gradient' => 'linear-gradient(135deg, #34d399, #059669)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 11,
        ],
        [
            'app_id' => 'ipsec-palo',
            'name' => 'IPSec Palo Alto Telemetry',
            'description' => 'Site-to-site VPN tunnel health, encryption status, and jitter telemetry.',
            'url' => '/ipsec-palo/',
            'category' => 'security',
            'icon_name' => 'Lock',
            'badge' => null,
            'accent_color' => '#6366f1',
            'bg_gradient' => 'linear-gradient(135deg, #818cf8, #4f46e5)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 22,
        ],
        [
            'app_id' => 'ipsec-branch',
            'name' => 'IPSec Branch Gateway',
            'description' => 'Regional branch office IPsec connection dashboard & tunnel latency.',
            'url' => '/ipsec-branch/',
            'category' => 'security',
            'icon_name' => 'Network',
            'badge' => null,
            'accent_color' => '#8b5cf6',
            'bg_gradient' => 'linear-gradient(135deg, #a78bfa, #7c3aed)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 14,
        ],

        // NETWORK (NOC)
        [
            'app_id' => 'api-nms',
            'name' => 'Network Management System (NMS)',
            'description' => 'Real-time telemetry, bandwidth saturation alerts, and router port stats.',
            'url' => '/api-nms/',
            'category' => 'network',
            'icon_name' => 'Activity',
            'badge' => 'LIVE',
            'accent_color' => '#0ea5e9',
            'bg_gradient' => 'linear-gradient(135deg, #38bdf8, #0284c7)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 36,
        ],
        [
            'app_id' => 'dashboard-librenms',
            'name' => 'LibreNMS Intelligence Portal',
            'description' => 'Auto-discovery network monitor via SNMP, syslog alerts, and device health.',
            'url' => '/dashboard-librenms/',
            'category' => 'network',
            'icon_name' => 'Gauge',
            'badge' => null,
            'accent_color' => '#10b981',
            'bg_gradient' => 'linear-gradient(135deg, #34d399, #059669)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 31,
        ],
        [
            'app_id' => 'weathermap',
            'name' => 'Network Weathermap Hub',
            'description' => 'Topological network traffic heatmaps & fiber link utilization.',
            'url' => '/weathermap/',
            'category' => 'network',
            'icon_name' => 'MapPin',
            'badge' => null,
            'accent_color' => '#ec4899',
            'bg_gradient' => 'linear-gradient(135deg, #f472b6, #db2777)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 25,
        ],
        [
            'app_id' => 'dashboard-noc',
            'name' => 'Dashboard NOC Command Center',
            'description' => 'Unified NOC wall dashboard for core infrastructure outages & ticket queue.',
            'url' => '/dashboard-noc/',
            'category' => 'network',
            'icon_name' => 'Monitor',
            'badge' => null,
            'accent_color' => '#3b82f6',
            'bg_gradient' => 'linear-gradient(135deg, #60a5fa, #2563eb)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 27,
        ],

        // MONITORING & TELEMETRY
        [
            'app_id' => 'zabbix-server-dashboard',
            'name' => 'Zabbix Enterprise Monitoring',
            'description' => 'Server hypervisors, VM metrics, storage pools, and host telemetry.',
            'url' => '/zabbix-server-dashboard/',
            'category' => 'monitoring',
            'icon_name' => 'Cpu',
            'badge' => 'CORE',
            'accent_color' => '#ef4444',
            'bg_gradient' => 'linear-gradient(135deg, #f87171, #dc2626)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 38,
        ],
        [
            'app_id' => 'grafana',
            'name' => 'Grafana Central Dashboards',
            'description' => 'Unified time-series visualizer, Prometheus metrics & custom SLO panels.',
            'url' => '/grafana/',
            'category' => 'monitoring',
            'icon_name' => 'BarChart3',
            'badge' => null,
            'accent_color' => '#f59e0b',
            'bg_gradient' => 'linear-gradient(135deg, #fbbf24, #d97706)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 33,
        ],
        [
            'app_id' => 'grafana-ap',
            'name' => 'Grafana Access Point Metrics',
            'description' => 'Wi-Fi AP controller telemetry, client count & radio interference analysis.',
            'url' => '/grafana-ap/',
            'category' => 'monitoring',
            'icon_name' => 'Wifi',
            'badge' => null,
            'accent_color' => '#14b8a6',
            'bg_gradient' => 'linear-gradient(135deg, #2dd4bf, #0d9488)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 12,
        ],
        [
            'app_id' => 'database',
            'name' => 'Database Performance Monitor',
            'description' => 'MySQL/MariaDB active queries, slow query logs, connection pools & locks.',
            'url' => '/database/',
            'category' => 'monitoring',
            'icon_name' => 'Database',
            'badge' => null,
            'accent_color' => '#06b6d4',
            'bg_gradient' => 'linear-gradient(135deg, #22d3ee, #0891b2)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 18,
        ],

        // OPERATIONS & ITOM
        [
            'app_id' => 'incident',
            'name' => 'Incident Response Center',
            'description' => 'Security incident escalation, evidence gathering & post-mortem tracker.',
            'url' => '/incident/',
            'category' => 'operations',
            'icon_name' => 'AlertOctagon',
            'badge' => null,
            'accent_color' => '#e11d48',
            'bg_gradient' => 'linear-gradient(135deg, #fb7185, #be123c)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 29,
        ],
        [
            'app_id' => 'tiket',
            'name' => 'IT Helpdesk & Tiket Sistem',
            'description' => 'Service request queue, SLA timer & internal troubleshooting ticketing.',
            'url' => '/tiket/',
            'category' => 'operations',
            'icon_name' => 'FileSpreadsheet',
            'badge' => null,
            'accent_color' => '#8b5cf6',
            'bg_gradient' => 'linear-gradient(135deg, #a78bfa, #7c3aed)',
            'status' => 'online',
            'is_favorite' => 0,
            'launch_count' => 17,
        ],
        [
            'app_id' => 'erp',
            'name' => 'Clipan ERP Production',
            'description' => 'Enterprise Resource Planning console & core financial database services.',
            'url' => '/erp/',
            'category' => 'operations',
            'icon_name' => 'Building',
            'badge' => null,
            'accent_color' => '#0284c7',
            'bg_gradient' => 'linear-gradient(135deg, #38bdf8, #0369a1)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 24,
        ],
        [
            'app_id' => 'wa-alert',
            'name' => 'WhatsApp Alert Gateway',
            'description' => 'Automated Telegram & WhatsApp notification dispatcher for high-priority incidents.',
            'url' => '/wa-alert/',
            'category' => 'operations',
            'icon_name' => 'BellRing',
            'badge' => null,
            'accent_color' => '#22c55e',
            'bg_gradient' => 'linear-gradient(135deg, #4ade80, #16a34a)',
            'status' => 'online',
            'is_favorite' => 1,
            'launch_count' => 23,
        ],
    ];

    $appStmt = $pdo->prepare("
        INSERT INTO portal_apps 
            (app_id, name, description, url, category, icon_name, badge, accent_color, bg_gradient, status, is_favorite, launch_count)
        VALUES 
            (:app_id, :name, :description, :url, :category, :icon_name, :badge, :accent_color, :bg_gradient, :status, :is_favorite, :launch_count)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            description = VALUES(description),
            url = VALUES(url),
            category = VALUES(category),
            icon_name = VALUES(icon_name),
            badge = VALUES(badge),
            accent_color = VALUES(accent_color),
            bg_gradient = VALUES(bg_gradient),
            status = VALUES(status)
    ");

    foreach ($apps as $a) {
        $appStmt->execute($a);
    }
    echo "  - Total " . count($apps) . " applications registered.\n";

    echo "SUCCESS: Portal database seeded with 4 users and applications registry!\n";
} catch (\Throwable $e) {
    echo "ERROR during seed: " . $e->getMessage() . "\n";
    exit(1);
}

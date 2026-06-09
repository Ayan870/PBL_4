<?php
/**
 * Header Include - PROVIA
 */
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?> – PROVIA</title>
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  
  <!-- Theme CSS -->
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  
  <style>
    body { background: #0f172a; }
    .sidebar { 
        min-height: 100vh; 
        background: #1e293b; 
        border-right: 1px solid #334155; 
        width: 240px; 
        position: fixed; 
        top: 0; 
        left: 0; 
        z-index: 1000;
    }
    .sidebar .nav-link { 
        color: #94a3b8; 
        font-size: 0.9rem; 
        padding: 12px 16px; 
        border-radius: 8px; 
        margin: 4px 12px; 
        transition: all 0.2s; 
    }
    .sidebar .nav-link:hover { background: rgba(79, 70, 229, 0.1); color: #818cf8; }
    .sidebar .nav-link.active { background: #4f46e5; color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
    .main { margin-left: 240px; padding: 32px; min-height: 100vh; }
    .profile-card { 
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); 
        border: none; 
        border-radius: 16px; 
        color: white; 
        overflow: hidden; 
    }
    .stat-card { 
        background: #1e293b; 
        border: 1px solid #334155; 
        border-radius: 16px; 
        padding: 20px; 
        transition: transform 0.2s; 
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon { 
        width: 44px; 
        height: 44px; 
        border-radius: 10px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 1.25rem; 
    }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; overflow: hidden; }
    .card-header { background: rgba(255,255,255,0.03); border-bottom: 1px solid #334155; padding: 16px 20px; }
    @media(max-width: 768px) { 
        .sidebar { display: none; } 
        .main { margin-left: 0; } 
    }
  </style>
</head>
<body>


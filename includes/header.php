<?php
// ===== Common HTML Head =====
// Usage: include this at the top of every PHP page
// Set $pageTitle before including
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'PBL Management System') ?> – PBLMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="<?= $rootPath ?? '../../' ?>assets/css/common.css" rel="stylesheet"/>
</head>
<body>

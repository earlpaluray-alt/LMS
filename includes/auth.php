<?php
require_once __DIR__.'/db.php';
function user(): ?array { return $_SESSION['user'] ?? null; }
function login_user(array $u): void { $_SESSION['user'] = ['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'id_number'=>$u['id_number']]; }
function require_login(): void { if (!user()) redirect('index.php?page=login'); }
function require_admin(): void { require_login(); if ((user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Administrator access required.'); } }
function logout_user(): void { unset($_SESSION['user']); }

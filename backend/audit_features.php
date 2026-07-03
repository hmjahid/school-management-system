<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$pass = 0; $fail = 0;
$cookieJar = [];

function send($kernel, $method, $url, $data = [], &$cookieJar = [], $files = []) {
    $server = ['HTTP_ACCEPT' => 'text/html'];
    foreach ($cookieJar as $n => $v) $server['HTTP_COOKIE'] = ($server['HTTP_COOKIE'] ?? '') . "$n=$v; ";
    $req = \Illuminate\Http\Request::create($url, $method, $data, [], $files, $server);
    $res = $kernel->handle($req);
    foreach ($res->headers->getCookies() as $c) $cookieJar[$c->getName()] = $c->getValue();
    $status = $res->getStatusCode();
    $body = $res->getContent();
    $loc = $res->headers->get('Location');
    $kernel->terminate($req, $res);
    return [$status, $body, $loc];
}

function ok($label, $cond, $detail = '') {
    global $pass, $fail;
    echo ($cond ? '✓ ' : '✗ ') . str_pad($label, 60) . ($detail ? " ($detail)" : '') . PHP_EOL;
    $cond ? $pass++ : $fail++;
    return $cond;
}

echo "=== Feature 1: Password Reset ===\n";

// GET forgot password form
[$s, $b] = send($kernel, 'GET', '/forgot-password', [], $cookieJar);
ok('GET /forgot-password', $s === 200, 'status '.$s);
ok('  contains "Reset your password"', str_contains($b, 'Reset your password'));
ok('  contains email input', str_contains($b, 'name="email"'));

// POST forgot password
preg_match('/name="_token" value="([^"]+)"/', $b, $m);
$tok = $m[1] ?? '';
[$s, $b, $loc] = send($kernel, 'POST', '/forgot-password', ['_token' => $tok, 'email' => 'admin@school.com'], $cookieJar);
ok('POST /forgot-password (valid email)', $s === 302, 'redirect '.$loc);

// Reset link should be in log
$logTail = file_get_contents(__DIR__ . '/storage/logs/laravel.log');
$hasResetLink = preg_match('/\/reset-password\/[a-f0-9]+/', $logTail) > 0;
ok('  reset link generated', $hasResetLink);

// Extract token from log
preg_match('/reset-password\/([a-f0-9]+)/', $logTail, $m);
$token = $m[1] ?? null;
ok('  token extracted', $token !== null, $token ? substr($token, 0, 12).'...' : 'none');

// GET reset form
if ($token) {
    [$s, $b] = send($kernel, 'GET', "/reset-password/{$token}?email=admin@school.com", [], $cookieJar);
    ok('GET /reset-password/{token}', $s === 200, 'status '.$s);
    ok('  contains password input', str_contains($b, 'name="password"'));
}

// POST reset with bad token → expect back with error
preg_match('/name="_token" value="([^"]+)"/', $b, $m);
$tok = $m[1] ?? '';
[$s] = send($kernel, 'POST', '/reset-password', [
    '_token' => $tok,
    'token' => 'invalid-token-xxxxx',
    'email' => 'admin@school.com',
    'password' => 'newpass123',
    'password_confirmation' => 'newpass123',
], $cookieJar);
ok('POST /reset-password (bad token)', $s === 302, 'redirect with error');

// 6 attempts should rate-limit
$j2 = [];
$blocked = false;
for ($i = 0; $i < 8; $i++) {
    [$s, $b] = send($kernel, 'GET', '/forgot-password', [], $j2);
    preg_match('/name="_token" value="([^"]+)"/', $b, $m);
    $t = $m[1] ?? '';
    $throttleKey = 'forgot-' . $i;
    [$s, $body] = send($kernel, 'POST', '/forgot-password', ['_token' => $t, 'email' => "test{$i}@x.com"], $j2);
    if (str_contains($body, 'Too many reset requests')) { $blocked = true; break; }
}
ok('  rate limit triggers after threshold', $blocked);

echo "\n=== Feature 2: Dark/Light Mode ===\n";

// GET any page, check that theme.js is loaded
[$s, $b] = send($kernel, 'GET', '/', [], $cookieJar);
ok('GET / (home)', $s === 200);
ok('  has FOUC-prevention inline script', str_contains($b, "localStorage.getItem('school-theme')"));
ok('  has theme toggle button (public)', str_contains($b, 'data-theme-toggle'));

// Login
send($kernel, 'GET', '/login', [], $cookieJar);
[$s, $h] = send($kernel, 'GET', '/login', [], $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $h, $m);
$tok = $m[1] ?? '';
[$s] = send($kernel, 'POST', '/login', ['_token' => $tok, 'email' => 'admin@school.com', 'password' => 'password'], $cookieJar);
ok('login as admin', $s === 302, 'redirect '.($cookieJar ? 'ok' : 'no cookie'));

// Dashboard has theme toggle
[$s, $b] = send($kernel, 'GET', '/dashboard', [], $cookieJar);
ok('GET /dashboard', $s === 200);
ok('  has theme toggle button (dashboard)', str_contains($b, 'data-theme-toggle'));
ok('  has dark: classes', str_contains($b, 'dark:bg-gray-') || str_contains($b, 'dark:'));
ok('  has FOUC script', str_contains($b, "localStorage.getItem('school-theme')"));

echo "\n=== Feature 3: Notifications ===\n";

[$s, $b] = send($kernel, 'GET', '/dashboard', [], $cookieJar);
ok('  bell visible (notifications-toggle)', str_contains($b, 'data-notifications-toggle'));
ok('  unread badge shows count', str_contains($b, 'data-notifications-badge'));
ok('  bell panel exists', str_contains($b, 'data-notifications-panel'));

// List endpoint
[$s, $b] = send($kernel, 'GET', '/dashboard/notifications/list', [], $cookieJar);
ok('GET /dashboard/notifications/list', $s === 200, 'status '.$s);
$json = json_decode($b, true);
ok('  JSON has items', is_array($json) && isset($json['items']));
ok('  JSON has unread_count', isset($json['unread_count']));
ok('  at least 2 unread (from seeder)', ($json['unread_count'] ?? 0) >= 2, 'count='.$json['unread_count']);

// Index page
[$s, $b] = send($kernel, 'GET', '/dashboard/notifications', [], $cookieJar);
ok('GET /dashboard/notifications', $s === 200);
ok('  shows "Notifications" heading', str_contains($b, 'Notifications'));
ok('  shows welcome notification', str_contains($b, 'Welcome to'));

// Mark all read
preg_match('/name="_token" value="([^"]+)"/', $b, $m);
$tok = $m[1] ?? '';
[$s, $b] = send($kernel, 'POST', '/dashboard/notifications/mark-all', ['_token' => $tok], $cookieJar);
ok('POST mark-all', $s === 200);
$json = json_decode($b, true);
ok('  unread_count = 0 after', ($json['unread_count'] ?? -1) === 0);

// Mark single read
$id = \App\Models\User::find(1)->notifications()->first()->id ?? null;
if ($id) {
    [$s] = send($kernel, 'GET', "/dashboard/notifications/{$id}/read", [], $cookieJar);
    ok('GET read/{id} (redirect)', $s === 302, 'status '.$s);
}

echo "\n=== Summary ===\n";
echo "PASS: $pass  FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);

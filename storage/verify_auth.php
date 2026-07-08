<?php
/**
 * End-to-end auth verification: login -> /me with the returned token.
 * Runs entirely through the kernel, so state can't leak between calls.
 */

$base = '/mnt/d/www/Wsaler';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function fire($kernel, $method, $uri, $body = null, $token = null): array
{
    $req = Illuminate\Http\Request::create($uri, $method, $body ?? []);
    $req->headers->set('Accept', 'application/json');
    if ($token) $req->headers->set('Authorization', 'Bearer ' . $token);
    $resp = $kernel->handle($req);
    return [$resp->getStatusCode(), $resp->getContent()];
}

// 1. Login
[$s, $b] = fire($kernel, 'POST', '/api/auth/login', [
    'email' => 'admin@example.com',
    'password' => 'password',
]);
$loginBody = json_decode($b, true);
$token = $loginBody['token'] ?? null;
echo "1) login:           status=$s  token=" . ($token ? substr($token, 0, 25) . '...' : 'NONE') .
     "  perms=" . count($loginBody['user']['permissions'] ?? []) . "\n";

if (!$token) {
    echo "LOGIN FAILED — body: $b\n";
    exit(1);
}

// 2. /api/auth/me WITH token
[$s, $b] = fire($kernel, 'GET', '/api/auth/me', null, $token);
$me = json_decode($b, true);
echo "2) /me (token):     status=$s  email=" . ($me['email'] ?? 'MISSING') .
     "  perms=" . count($me['permissions'] ?? []) . "\n";

// 3. /api/users WITH token
[$s, $b] = fire($kernel, 'GET', '/api/users', null, $token);
$users = json_decode($b, true);
echo "3) /users (token):  status=$s  user_count=" .
     (isset($users['data']) ? count($users['data']) : '?') . "\n";

// 4. /api/users WITHOUT token (should be 401)
[$s, $b] = fire($kernel, 'GET', '/api/users');
echo "4) /users (none):   status=$s  body=" . substr($b, 0, 80) . "\n";

// 5. /api/auth/me WITHOUT token (should be 401)
[$s, $b] = fire($kernel, 'GET', '/api/auth/me');
echo "5) /me (none):      status=$s  body=" . substr($b, 0, 80) . "\n";

// 6. logout with valid token
[$s, $b] = fire($kernel, 'POST', '/api/auth/logout', [], $token);
echo "6) /logout (token): status=$s  body=" . substr($b, 0, 80) . "\n";

echo "\nDONE\n";

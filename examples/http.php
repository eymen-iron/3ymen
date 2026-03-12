<?php

/**
 * 3ymen Framework - HTTP (Request/Response/Uri) Ornekleri
 *
 * Request olusturma, Response, Uri parse, Stream
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Http\Request;
use Eymen\Http\Response;
use Eymen\Http\Uri;
use Eymen\Http\Stream\StringStream;

// ============================================================================
// 1. Request Olusturma
// ============================================================================

// --- Manuel Request ---
$request = new Request(
    method: 'GET',
    uri: Uri::fromString('https://example.com/api/users?page=2&sort=name'),
    headers: [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer my-token',
    ],
);

echo "Method: {$request->getMethod()}\n";                    // GET
echo "URI: {$request->getUri()}\n";                           // https://example.com/api/users?page=2&sort=name
echo "Path: {$request->getUri()->getPath()}\n";               // /api/users
echo "Query: {$request->getUri()->getQuery()}\n";             // page=2&sort=name
echo "Auth: {$request->getHeaderLine('Authorization')}\n";    // Bearer my-token

// --- POST Request ---
$postRequest = new Request(
    method: 'POST',
    uri: Uri::fromString('https://example.com/api/users'),
    headers: [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    body: new StringStream(json_encode([
        'name' => 'Ali Yilmaz',
        'email' => 'ali@example.com',
    ])),
    parsedBody: [
        'name' => 'Ali Yilmaz',
        'email' => 'ali@example.com',
    ],
);

$body = $postRequest->getParsedBody();
echo "POST name: {$body['name']}\n";
echo "POST email: {$body['email']}\n";

// --- PHP Superglobals'dan Request ---
// $request = Request::fromGlobals();

// ============================================================================
// 2. Request Okuma Islemleri
// ============================================================================

// --- Method ---
echo "Method: {$request->getMethod()}\n";
echo "Protocol: {$request->getProtocolVersion()}\n";
echo "Target: {$request->getRequestTarget()}\n";

// --- Headers ---
$headers = $request->getHeaders();
foreach ($headers as $name => $values) {
    echo "Header [{$name}]: " . implode(', ', $values) . "\n";
}

// Tek header (string olarak)
$accept = $request->getHeaderLine('Accept');
echo "Accept: {$accept}\n";

// Header array olarak
$acceptArray = $request->getHeader('Accept');

// Header varlik kontrolu
if ($request->hasHeader('Authorization')) {
    echo "Authorization header mevcut.\n";
}

// --- Query Parameters ---
$queryParams = $request->getQueryParams();
echo "Query params: " . json_encode($queryParams) . "\n";

// --- Server Parameters ---
$serverParams = $request->getServerParams();

// --- Cookie Parameters ---
$cookies = $request->getCookieParams();

// --- Uploaded Files ---
$files = $request->getUploadedFiles();

// --- Body ---
$bodyStream = $request->getBody();
$bodyContent = (string) $bodyStream;
echo "Body: {$bodyContent}\n";

// --- Attributes (Route parametreleri vb.) ---
$request = $request->withAttribute('user_id', 42);
$request = $request->withAttribute('role', 'admin');

$userId = $request->getAttribute('user_id');
$role = $request->getAttribute('role', 'guest');
$allAttrs = $request->getAttributes();

echo "User ID attr: {$userId}\n";
echo "Role attr: {$role}\n";

// ============================================================================
// 3. Immutable Request Degisiklikleri (with*)
// ============================================================================

// PSR-7: Tum with* metodlari yeni instance dondurur

// Method degistirme
$putRequest = $request->withMethod('PUT');
echo "Yeni method: {$putRequest->getMethod()}\n"; // PUT
echo "Eski method: {$request->getMethod()}\n";     // GET (degismez)

// URI degistirme
$newRequest = $request->withUri(Uri::fromString('https://example.com/new-path'));

// Header ekleme/degistirme
$newRequest = $request
    ->withHeader('X-Custom', 'value')
    ->withAddedHeader('Accept', 'text/html')
    ->withoutHeader('Authorization');

// Query params degistirme
$newRequest = $request->withQueryParams(['page' => 3, 'limit' => 50]);

// Cookie degistirme
$newRequest = $request->withCookieParams(['session_id' => 'abc123']);

// Body degistirme
$newRequest = $request->withBody(new StringStream('Yeni body'));

// Parsed body
$newRequest = $request->withParsedBody(['key' => 'value']);

// Protocol version
$newRequest = $request->withProtocolVersion('2.0');

// Attribute ekleme/cikarma
$newRequest = $request
    ->withAttribute('timezone', 'Europe/Istanbul')
    ->withoutAttribute('role');

// ============================================================================
// 4. Response Olusturma
// ============================================================================

// --- Basit Response ---
$response = new Response(
    statusCode: 200,
    headers: ['Content-Type' => 'text/html; charset=UTF-8'],
    body: new StringStream('<h1>Merhaba Dunya!</h1>'),
);

echo "Status: {$response->getStatusCode()}\n";         // 200
echo "Reason: {$response->getReasonPhrase()}\n";         // OK
echo "Body: {$response->getBody()}\n";

// --- JSON Response ---
$jsonResponse = new Response(
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    body: new StringStream(json_encode([
        'data' => ['id' => 1, 'name' => 'Ali'],
        'status' => 'success',
    ])),
);

// --- 201 Created ---
$createdResponse = new Response(
    statusCode: 201,
    headers: [
        'Content-Type' => 'application/json',
        'Location' => '/api/users/42',
    ],
    body: new StringStream(json_encode(['id' => 42])),
);

// --- 204 No Content ---
$noContentResponse = new Response(statusCode: 204);

// --- 301 / 302 Redirect ---
$redirectResponse = new Response(
    statusCode: 302,
    headers: ['Location' => '/dashboard'],
);

// --- 404 Not Found ---
$notFoundResponse = new Response(
    statusCode: 404,
    headers: ['Content-Type' => 'application/json'],
    body: new StringStream(json_encode([
        'error' => true,
        'message' => 'Kaynak bulunamadi',
    ])),
);

// --- 500 Server Error ---
$errorResponse = new Response(
    statusCode: 500,
    headers: ['Content-Type' => 'application/json'],
    body: new StringStream(json_encode([
        'error' => true,
        'message' => 'Sunucu hatasi',
    ])),
);

// ============================================================================
// 5. Immutable Response Degisiklikleri
// ============================================================================

// Status degistirme
$newResponse = $response->withStatus(201, 'Created');
echo "New status: {$newResponse->getStatusCode()} {$newResponse->getReasonPhrase()}\n";

// Header islemleri
$newResponse = $response
    ->withHeader('X-Request-Id', 'abc-123')
    ->withHeader('Cache-Control', 'no-cache')
    ->withAddedHeader('Set-Cookie', 'session=xyz')
    ->withoutHeader('X-Old-Header');

// Body degistirme
$newResponse = $response->withBody(new StringStream('Yeni icerik'));

// Protocol version
$newResponse = $response->withProtocolVersion('1.0');

// ============================================================================
// 6. Helper Fonksiyonlariyla Response Olusturma
// ============================================================================

// response() helper
$r = response('Hello World', 200, ['X-Custom' => 'value']);

// json_response() helper
$r = json_response(['key' => 'value'], 200);

// redirect() helper
$r = redirect('/login', 302);

// ============================================================================
// 7. Uri Sinifi
// ============================================================================

// --- String'den Parse ---
$uri = Uri::fromString('https://user:pass@example.com:8080/path/to/page?q=search&page=1#section');

echo "Scheme: {$uri->getScheme()}\n";         // https
echo "Host: {$uri->getHost()}\n";               // example.com
echo "Port: {$uri->getPort()}\n";               // 8080
echo "Path: {$uri->getPath()}\n";               // /path/to/page
echo "Query: {$uri->getQuery()}\n";             // q=search&page=1
echo "Fragment: {$uri->getFragment()}\n";       // section
echo "Authority: {$uri->getAuthority()}\n";     // user:pass@example.com:8080
echo "UserInfo: {$uri->getUserInfo()}\n";       // user:pass
echo "Full URI: {$uri}\n";                       // __toString

// --- Manuel Olusturma ---
$uri = new Uri(
    scheme: 'https',
    host: 'api.example.com',
    port: 443,
    path: '/v1/users',
    query: 'limit=10',
);

// --- PHP Superglobals'dan ---
// $uri = Uri::fromGlobals();

// --- Immutable Degisiklikler ---
$newUri = $uri
    ->withScheme('http')
    ->withHost('localhost')
    ->withPort(8080)
    ->withPath('/api/posts')
    ->withQuery('page=2&sort=date')
    ->withFragment('top')
    ->withUserInfo('admin', 'secret');

echo "Yeni URI: {$newUri}\n";

// ============================================================================
// 8. Stream
// ============================================================================

// StringStream - basit string stream
$stream = new StringStream('Merhaba Dunya!');

echo "Stream icerik: {$stream}\n";            // __toString
echo "Boyut: {$stream->getSize()}\n";          // 14 (veya byte sayisi)
echo "Okunabilir mi: " . ($stream->isReadable() ? 'Evet' : 'Hayir') . "\n";
echo "Yazilabilir mi: " . ($stream->isWritable() ? 'Evet' : 'Hayir') . "\n";
echo "Seekable mi: " . ($stream->isSeekable() ? 'Evet' : 'Hayir') . "\n";
echo "EOF mu: " . ($stream->eof() ? 'Evet' : 'Hayir') . "\n";

// Okuma
$stream->rewind();
$chunk = $stream->read(7);  // "Merhaba"
echo "Chunk: {$chunk}\n";

$rest = $stream->getContents(); // " Dunya!"
echo "Rest: {$rest}\n";

// ============================================================================
// 9. Tam HTTP Akisi Ornegi
// ============================================================================

// 1. Request olustur
$request = new Request(
    method: 'POST',
    uri: Uri::fromString('http://localhost:8000/api/posts'),
    headers: [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer jwt-token-here',
        'Accept' => 'application/json',
    ],
    body: new StringStream(json_encode([
        'title'   => 'Yeni Yazi',
        'content' => 'Bu bir ornek yazidir.',
    ])),
    parsedBody: [
        'title'   => 'Yeni Yazi',
        'content' => 'Bu bir ornek yazidir.',
    ],
);

// 2. Isleme (normalde Kernel yapar)
$data = $request->getParsedBody();
echo "\nGelen veri:\n";
echo "  title: {$data['title']}\n";
echo "  content: {$data['content']}\n";

// 3. Response olustur
$responseData = [
    'data' => [
        'id'      => 1,
        'title'   => $data['title'],
        'content' => $data['content'],
    ],
    'message' => 'Yazi olusturuldu',
];

$response = new Response(
    statusCode: 201,
    headers: [
        'Content-Type' => 'application/json',
        'Location' => '/api/posts/1',
    ],
    body: new StringStream(json_encode($responseData)),
);

echo "Response: {$response->getStatusCode()} {$response->getReasonPhrase()}\n";
echo "Body: {$response->getBody()}\n";

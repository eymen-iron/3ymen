<?php

declare(strict_types=1);

use Eymen\View\VexLexer;
use Eymen\View\VexEngine;
use Eymen\View\Token;

// Lexer tests
test('lexer tokenizes plain text', function () {
    $lexer = new VexLexer();
    $stream = $lexer->tokenize('Hello World');
    $token = $stream->next();
    expect($token->type)->toBe(Token::TEXT);
    expect($token->value)->toBe('Hello World');
});

test('lexer tokenizes variable expression', function () {
    $lexer = new VexLexer();
    $stream = $lexer->tokenize('{{ name }}');
    $token = $stream->next();
    expect($token->type)->toBe(Token::VAR_START);
});

test('lexer tokenizes block tags', function () {
    $lexer = new VexLexer();
    $stream = $lexer->tokenize('{% if true %}yes{% endif %}');
    $token = $stream->next();
    expect($token->type)->toBe(Token::BLOCK_START);
});

// Engine tests - uses .vex extension
test('engine renders simple template', function () {
    $viewDir = sys_get_temp_dir() . '/vex_test_' . uniqid();
    $cacheDir = sys_get_temp_dir() . '/vex_cache_' . uniqid();
    mkdir($viewDir, 0755, true);
    mkdir($cacheDir, 0755, true);

    file_put_contents($viewDir . '/hello.vex', 'Hello {{ name }}!');

    $engine = new VexEngine($viewDir, $cacheDir);
    $result = $engine->render('hello', ['name' => 'Eymen']);
    expect($result)->toContain('Eymen');

    array_map('unlink', glob($viewDir . '/*') ?: []);
    array_map('unlink', glob($cacheDir . '/*') ?: []);
    @rmdir($viewDir);
    @rmdir($cacheDir);
});

test('engine checks template existence', function () {
    $viewDir = sys_get_temp_dir() . '/vex_test_' . uniqid();
    $cacheDir = sys_get_temp_dir() . '/vex_cache_' . uniqid();
    mkdir($viewDir, 0755, true);
    mkdir($cacheDir, 0755, true);

    file_put_contents($viewDir . '/exists.vex', 'content');

    $engine = new VexEngine($viewDir, $cacheDir);
    expect($engine->exists('exists'))->toBeTrue();
    expect($engine->exists('nonexistent'))->toBeFalse();

    array_map('unlink', glob($viewDir . '/*') ?: []);
    @rmdir($viewDir);
    @rmdir($cacheDir);
});

test('engine getViewPath returns configured path', function () {
    $viewDir = sys_get_temp_dir() . '/vex_test_' . uniqid();
    $cacheDir = sys_get_temp_dir() . '/vex_cache_' . uniqid();
    mkdir($viewDir, 0755, true);
    mkdir($cacheDir, 0755, true);

    $engine = new VexEngine($viewDir, $cacheDir);
    expect($engine->getViewPath())->toBe($viewDir);

    @rmdir($viewDir);
    @rmdir($cacheDir);
});

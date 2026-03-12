<?php

/**
 * 3ymen Framework - Validation Ornekleri
 *
 * required, email, min/max, custom rules, error messages
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Validation\Validator;
use Eymen\Validation\ValidationException;

// ============================================================================
// 1. Temel Validation
// ============================================================================

$data = [
    'name'  => 'Ali Yilmaz',
    'email' => 'ali@example.com',
    'age'   => 25,
];

$rules = [
    'name'  => 'required|string|min:2|max:100',
    'email' => 'required|email',
    'age'   => 'required|integer|between:18,100',
];

$validator = Validator::make($data, $rules);

if ($validator->passes()) {
    echo "Validation basarili!\n";
    $validated = $validator->validate();
    print_r($validated);
} else {
    echo "Validation basarisiz!\n";
    print_r($validator->errors());
}

// ============================================================================
// 2. Tum Kural Tipleri
// ============================================================================

// --- Varlik Kontrolleri ---
$rules = [
    'field1' => 'required',         // Bos olamaz
    'field2' => 'nullable',         // Null olabilir
    'field3' => 'sometimes',        // Varsa validate et, yoksa atla
];

// --- Tip Kontrolleri ---
$rules = [
    'name'     => 'string',         // String olmali
    'count'    => 'integer',        // Integer olmali
    'price'    => 'numeric',        // Sayisal (int veya float)
    'active'   => 'boolean',        // Boolean olmali
    'tags'     => 'array',          // Array olmali
    'metadata' => 'json',           // Gecerli JSON olmali
];

// --- Uzunluk / Boyut Kontrolleri ---
$rules = [
    'username' => 'min:3|max:20',        // 3-20 karakter arasi
    'password' => 'min:8',               // En az 8 karakter
    'bio'      => 'max:500',             // En fazla 500 karakter
    'age'      => 'between:18,100',      // 18-100 arasi
];

// --- Format Kontrolleri ---
$rules = [
    'email'   => 'email',                           // Gecerli email
    'website' => 'url',                              // Gecerli URL
    'ip_addr' => 'ip',                               // Gecerli IP adresi
    'date'    => 'date',                             // Gecerli tarih
    'time'    => 'date_format:H:i',                  // Belirli tarih formati
    'slug'    => 'alpha_dash',                       // Harf, rakam, tire, alt cizgi
    'code'    => 'alpha_num',                        // Sadece harf ve rakam
    'name'    => 'alpha',                            // Sadece harfler
    'pattern' => 'regex:/^[A-Z]{2}[0-9]{4}$/',      // Regex deseni
];

// --- Deger Kontrolleri ---
$rules = [
    'role'   => 'in:admin,user,moderator',    // Belirli degerlerden biri
    'status' => 'not_in:banned,suspended',     // Bu degerler haric
];

// --- Dogrulama Kontrolleri ---
$rules = [
    'password'              => 'required|min:8',
    'password_confirmation' => 'confirmed',     // password ile eslesmeli
];

// --- Veritabani Kontrolleri ---
$rules = [
    'email'       => 'unique:users,email',     // Tabloda benzersiz olmali
    'category_id' => 'exists:categories,id',   // Tabloda mevcut olmali
];

// ============================================================================
// 3. Hata Mesajlarini Alma
// ============================================================================

$data = [
    'name'  => '',
    'email' => 'gecersiz-email',
    'age'   => 15,
];

$rules = [
    'name'  => 'required|string|min:2',
    'email' => 'required|email',
    'age'   => 'required|integer|between:18,100',
];

$validator = Validator::make($data, $rules);

if ($validator->fails()) {
    $errors = $validator->errors();

    // Her alan icin hatalar
    foreach ($errors as $field => $messages) {
        echo "{$field}:\n";
        foreach ($messages as $message) {
            echo "  - {$message}\n";
        }
    }
}

// getErrors() ile de ayni sonuc
$errors = $validator->getErrors();

// ============================================================================
// 4. Ozel Hata Mesajlari
// ============================================================================

$data = [
    'name'     => '',
    'email'    => 'test',
    'password' => '123',
];

$rules = [
    'name'     => 'required|min:2',
    'email'    => 'required|email',
    'password' => 'required|min:8',
];

// 3. parametre olarak ozel mesajlar
$messages = [
    'name.required'    => 'Isim alani zorunludur.',
    'name.min'         => 'Isim en az :min karakter olmalidir.',
    'email.required'   => 'Email adresi gereklidir.',
    'email.email'      => 'Gecerli bir email adresi giriniz.',
    'password.required' => 'Sifre zorunludur.',
    'password.min'     => 'Sifre en az :min karakter olmalidir.',
];

$validator = Validator::make($data, $rules, $messages);

if ($validator->fails()) {
    foreach ($validator->errors() as $field => $msgs) {
        foreach ($msgs as $msg) {
            echo "HATA: {$msg}\n";
        }
    }
}

// ============================================================================
// 5. validate() ile Exception Yakalama
// ============================================================================

try {
    $data = ['email' => 'gecersiz'];
    $rules = ['email' => 'required|email'];

    $validated = Validator::make($data, $rules)->validate();

    // Buraya sadece validation gecerse ulasir
    echo "Gecerli email: {$validated['email']}\n";
} catch (ValidationException $e) {
    echo "Validation hatasi!\n";
    $errors = $e->getErrors();
    print_r($errors);
}

// ============================================================================
// 6. Ozel (Custom) Kurallar
// ============================================================================

// Global ozel kural ekleme
Validator::extend('turkish_phone', function (string $field, mixed $value, array $params, array $data): bool {
    // Turkiye telefon numarasi: +90 5XX XXX XX XX
    return (bool) preg_match('/^\+90\s?5\d{2}\s?\d{3}\s?\d{2}\s?\d{2}$/', (string) $value);
});

Validator::setMessage('turkish_phone', 'Gecerli bir Turkiye telefon numarasi giriniz (+90 5XX XXX XX XX).');

// Kullanim
$data = ['phone' => '+90 532 123 45 67'];
$rules = ['phone' => 'required|turkish_phone'];

$validator = Validator::make($data, $rules);
echo "Telefon gecerli mi: " . ($validator->passes() ? 'Evet' : 'Hayir') . "\n";

// Baska bir ozel kural: TC kimlik numarasi
Validator::extend('tc_kimlik', function (string $field, mixed $value, array $params, array $data): bool {
    $value = (string) $value;
    if (strlen($value) !== 11 || !ctype_digit($value) || $value[0] === '0') {
        return false;
    }

    $odds = 0;
    $evens = 0;
    for ($i = 0; $i < 9; $i++) {
        if ($i % 2 === 0) {
            $odds += (int) $value[$i];
        } else {
            $evens += (int) $value[$i];
        }
    }

    $d10 = ($odds * 7 - $evens) % 10;
    if ($d10 < 0) {
        $d10 += 10;
    }

    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += (int) $value[$i];
    }

    return (int) $value[9] === $d10 && (int) $value[10] === ($sum % 10);
});

Validator::setMessage('tc_kimlik', ':field alani gecerli bir TC Kimlik Numarasi olmalidir.');

$data = ['tc' => '10000000146'];
$rules = ['tc' => 'required|tc_kimlik'];
$validator = Validator::make($data, $rules);
echo "TC gecerli mi: " . ($validator->passes() ? 'Evet' : 'Hayir') . "\n";

// ============================================================================
// 7. Karmasik Form Validation Ornegi
// ============================================================================

$formData = [
    'name'                  => 'Ali Yilmaz',
    'email'                 => 'ali@example.com',
    'password'              => 'GucluSifre123!',
    'password_confirmation' => 'GucluSifre123!',
    'age'                   => 28,
    'role'                  => 'user',
    'bio'                   => 'PHP gelistirici',
    'website'               => 'https://ali.dev',
    'tags'                  => ['php', 'javascript'],
];

$rules = [
    'name'                  => 'required|string|min:2|max:100',
    'email'                 => 'required|email|max:255',
    'password'              => 'required|string|min:8|confirmed',
    'password_confirmation' => 'required',
    'age'                   => 'nullable|integer|between:13,120',
    'role'                  => 'required|in:user,admin,moderator',
    'bio'                   => 'nullable|string|max:500',
    'website'               => 'nullable|url',
    'tags'                  => 'nullable|array',
];

$messages = [
    'name.required'       => 'Adinizi giriniz.',
    'email.email'         => 'Gecerli bir email giriniz.',
    'password.min'        => 'Sifreniz en az 8 karakter olmalidir.',
    'password.confirmed'  => 'Sifreler eslesmeli.',
    'role.in'             => 'Gecersiz rol secimi.',
];

$validator = Validator::make($formData, $rules, $messages);

if ($validator->passes()) {
    $validated = $validator->validate();
    echo "Form gecerli! Kullanici olusturulabilir.\n";
} else {
    echo "Form hatalari:\n";
    foreach ($validator->errors() as $field => $errors) {
        foreach ($errors as $error) {
            echo "  [{$field}] {$error}\n";
        }
    }
}

// ============================================================================
// 8. API Controller'da Validation Kullanimi
// ============================================================================

// Tipik bir API controller metodu icinde:
function storeUser(array $requestBody): array
{
    $validator = Validator::make($requestBody, [
        'name'     => 'required|string|min:2|max:100',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'role'     => 'in:user,admin',
    ]);

    if ($validator->fails()) {
        return [
            'error'  => true,
            'status' => 422,
            'errors' => $validator->errors(),
        ];
    }

    // Validation gecti, kullaniciyi olustur
    $validated = $validator->validate();

    return [
        'data'    => ['id' => 1, 'name' => $validated['name']],
        'message' => 'Kullanici basariyla olusturuldu',
    ];
}

$result = storeUser(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'secret123']);
print_r($result);

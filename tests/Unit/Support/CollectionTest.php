<?php

declare(strict_types=1);

use Eymen\Support\Collection;

// Construction
test('make creates collection from array', function () {
    $c = Collection::make([1, 2, 3]);
    expect($c->count())->toBe(3);
    expect($c->all())->toBe([1, 2, 3]);
});

test('times creates collection by invoking callback n times', function () {
    $c = Collection::times(3, fn ($i) => $i * 2);
    expect($c->all())->toBe([2, 4, 6]);
});

// Transformations
test('map transforms each item', function () {
    $c = Collection::make([1, 2, 3])->map(fn ($v) => $v * 2);
    expect($c->all())->toBe([2, 4, 6]);
});

test('filter removes items not passing callback', function () {
    $c = Collection::make([1, 2, 3, 4])->filter(fn ($v) => $v % 2 === 0);
    expect($c->values()->all())->toBe([2, 4]);
});

test('filter without callback removes falsy values', function () {
    $c = Collection::make([0, 1, '', 'a', null, false])->filter();
    expect($c->values()->all())->toBe([1, 'a']);
});

test('reduce reduces collection to single value', function () {
    $sum = Collection::make([1, 2, 3, 4])->reduce(fn ($carry, $v) => $carry + $v, 0);
    expect($sum)->toBe(10);
});

test('each iterates over items', function () {
    $items = [];
    Collection::make([1, 2, 3])->each(function ($v) use (&$items) {
        $items[] = $v;
    });
    expect($items)->toBe([1, 2, 3]);
});

test('pluck extracts values by key', function () {
    $c = Collection::make([
        ['name' => 'Eymen', 'age' => 25],
        ['name' => 'Ali', 'age' => 30],
    ]);
    expect($c->pluck('name')->all())->toBe(['Eymen', 'Ali']);
});

test('pluck with key creates key-value pairs', function () {
    $c = Collection::make([
        ['id' => 1, 'name' => 'Eymen'],
        ['id' => 2, 'name' => 'Ali'],
    ]);
    expect($c->pluck('name', 'id')->all())->toBe([1 => 'Eymen', 2 => 'Ali']);
});

test('where filters by key-value', function () {
    $c = Collection::make([
        ['status' => 'active', 'name' => 'A'],
        ['status' => 'inactive', 'name' => 'B'],
        ['status' => 'active', 'name' => 'C'],
    ]);
    expect($c->where('status', 'active')->count())->toBe(2);
});

test('sortBy sorts collection by key', function () {
    $c = Collection::make([
        ['name' => 'Zeynep'],
        ['name' => 'Ali'],
        ['name' => 'Eymen'],
    ])->sortBy('name');
    expect($c->pluck('name')->all())->toBe(['Ali', 'Eymen', 'Zeynep']);
});

test('groupBy groups by key', function () {
    $c = Collection::make([
        ['type' => 'a', 'val' => 1],
        ['type' => 'b', 'val' => 2],
        ['type' => 'a', 'val' => 3],
    ])->groupBy('type');
    expect($c->keys()->all())->toBe(['a', 'b']);
});

test('unique removes duplicates', function () {
    expect(Collection::make([1, 2, 2, 3, 3])->unique()->all())->toBe([1, 2, 3]);
});

test('flatten flattens nested arrays', function () {
    $c = Collection::make([[1, 2], [3, [4, 5]]])->flatten();
    expect($c->all())->toBe([1, 2, 3, 4, 5]);
});

// First / Last
test('first returns first item', function () {
    expect(Collection::make([10, 20, 30])->first())->toBe(10);
    expect(Collection::make([10, 20, 30])->first(fn ($v) => $v > 15))->toBe(20);
});

test('last returns last item', function () {
    expect(Collection::make([10, 20, 30])->last())->toBe(30);
});

// Aggregation
test('sum calculates total', function () {
    expect(Collection::make([1, 2, 3])->sum())->toBe(6);
});

test('sum with key calculates total of key', function () {
    $sum = Collection::make([
        ['price' => 10],
        ['price' => 20],
    ])->sum('price');
    expect($sum)->toBe(30);
});

test('avg calculates average', function () {
    $avg = Collection::make([10, 20, 30])->avg();
    expect($avg)->toBe(20);
});

test('min returns minimum', function () {
    expect(Collection::make([3, 1, 2])->min())->toBe(1);
});

test('max returns maximum', function () {
    expect(Collection::make([3, 1, 2])->max())->toBe(3);
});

test('isEmpty and isNotEmpty check emptiness', function () {
    expect(Collection::make([])->isEmpty())->toBeTrue();
    expect(Collection::make([])->isNotEmpty())->toBeFalse();
    expect(Collection::make([1])->isEmpty())->toBeFalse();
    expect(Collection::make([1])->isNotEmpty())->toBeTrue();
});

// Subset & manipulation
test('slice extracts portion', function () {
    expect(Collection::make([1, 2, 3, 4, 5])->slice(2)->values()->all())->toBe([3, 4, 5]);
    expect(Collection::make([1, 2, 3, 4, 5])->slice(1, 2)->values()->all())->toBe([2, 3]);
});

test('chunk splits into smaller collections', function () {
    $chunks = Collection::make([1, 2, 3, 4, 5])->chunk(2);
    expect($chunks->count())->toBe(3);
});

test('take returns first n items', function () {
    expect(Collection::make([1, 2, 3, 4])->take(2)->all())->toBe([1, 2]);
});

test('skip skips n items', function () {
    expect(Collection::make([1, 2, 3, 4])->skip(2)->values()->all())->toBe([3, 4]);
});

test('contains checks for value', function () {
    expect(Collection::make([1, 2, 3])->contains(2))->toBeTrue();
    expect(Collection::make([1, 2, 3])->contains(5))->toBeFalse();
});

test('merge combines collections', function () {
    $c = Collection::make([1, 2])->merge([3, 4]);
    expect($c->all())->toBe([1, 2, 3, 4]);
});

test('push adds items', function () {
    $c = Collection::make([1, 2])->push(3, 4);
    expect($c->all())->toBe([1, 2, 3, 4]);
});

test('pop removes and returns last item', function () {
    $c = Collection::make([1, 2, 3]);
    expect($c->pop())->toBe(3);
    expect($c->all())->toBe([1, 2]);
});

test('shift removes and returns first item', function () {
    $c = Collection::make([1, 2, 3]);
    expect($c->shift())->toBe(1);
    expect($c->all())->toBe([2, 3]);
});

test('reverse reverses order', function () {
    expect(Collection::make([1, 2, 3])->reverse()->values()->all())->toBe([3, 2, 1]);
});

test('search finds value index', function () {
    expect(Collection::make([1, 2, 3])->search(2))->toBe(1);
    expect(Collection::make([1, 2, 3])->search(5))->toBeFalse();
});

// Higher-order
test('pipe passes collection to callback', function () {
    $result = Collection::make([1, 2, 3])->pipe(fn ($c) => $c->sum());
    expect($result)->toBe(6);
});

test('tap calls callback without affecting collection', function () {
    $tapped = null;
    $c = Collection::make([1, 2, 3])->tap(function ($c) use (&$tapped) {
        $tapped = $c->sum();
    });
    expect($tapped)->toBe(6);
    expect($c->all())->toBe([1, 2, 3]);
});

// Set operations
test('diff returns items not in other', function () {
    expect(Collection::make([1, 2, 3, 4])->diff([2, 4])->values()->all())->toBe([1, 3]);
});

test('intersect returns common items', function () {
    expect(Collection::make([1, 2, 3, 4])->intersect([2, 4, 6])->values()->all())->toBe([2, 4]);
});

test('flip swaps keys and values', function () {
    expect(Collection::make(['a' => 1, 'b' => 2])->flip()->all())->toBe([1 => 'a', 2 => 'b']);
});

// Serialization
test('toJson returns JSON string', function () {
    expect(Collection::make([1, 2, 3])->toJson())->toBe('[1,2,3]');
});

test('toArray returns plain array', function () {
    expect(Collection::make([1, 2, 3])->toArray())->toBe([1, 2, 3]);
});

// Interface compliance
test('collection is countable', function () {
    expect(count(Collection::make([1, 2, 3])))->toBe(3);
});

test('collection is iterable', function () {
    $items = [];
    foreach (Collection::make([1, 2, 3]) as $item) {
        $items[] = $item;
    }
    expect($items)->toBe([1, 2, 3]);
});

test('collection supports array access', function () {
    $c = Collection::make([1, 2, 3]);
    expect(isset($c[0]))->toBeTrue();
    expect($c[1])->toBe(2);
    $c[3] = 4;
    expect($c[3])->toBe(4);
    unset($c[0]);
    expect(isset($c[0]))->toBeFalse();
});

test('collection is json serializable', function () {
    $c = Collection::make(['a' => 1]);
    expect(json_encode($c))->toBe('{"a":1}');
});

// implode
test('implode joins items', function () {
    expect(Collection::make(['a', 'b', 'c'])->implode(','))->toBe('a,b,c');
});

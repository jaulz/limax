<?php

namespace Jaulz\Limax\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::statement('CREATE EXTENSION IF NOT EXISTS hstore');
    DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

    $migration = include __DIR__ . '/../database/migrations/create_limax_extension.php.stub';
    $migration->up();
});

test('creates correct slugs', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
    });

    collect([
        'test' => 'test',
        'Test-Story-4' => 'test-story-4',
        'äöü' => 'aeoeue',
        'èô' => 'eo'
    ])->each(function ($key, $value) {
        $post = DB::table('posts')->insertReturning([
            'title' => $value
        ])->first();

        expect($post->slug)->toBe($key);
    });
});

test('updates correct slugs', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
    });

    collect([
        'test' => 'test',
        'Test-Story-4' => 'test-story-4',
        'äöü' => 'aeoeue',
        'èô' => 'eo'
    ])->each(function ($key, $value) {
         DB::table('posts')->insert([
            'title' => '---'
        ]);

        $post = DB::table('posts')->updateReturning([
            'title' => $value
        ])->first();

        expect($post->slug)->toBe($key);
    });
});

test('increments suffix when same slug is used multiple times', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
    });

    collect([null,null,null,null])->keys()->each(function($index) {
        $post = DB::table('posts')->insertReturning([
            'title' => 'test'
        ])->first();

        $suffix = $index > 0 ? '_' . ($index + 1) : '';
        expect($post->slug)->toBe('test' . $suffix);
    });
});

test('remembers slugs once assigned', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
    });

    $initialTitle = 'test';
    $initialSlug = 'test';

    $initialPost = DB::table('posts')->insertReturning([
        'title' => $initialTitle
    ])->first();
    expect($initialPost->slug)->toBe($initialSlug);

    $initialPost = DB::table('posts')->updateReturning([
        'title' => 'not a test anymore'
    ])->first();
    expect($initialPost->slug)->toBe('not-a-test-anymore');

    $secondPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($secondPost->slug)->toBe('test_2');

    $initialPost = DB::table('posts')->where([
        'id' => $initialPost->id,
    ])->updateReturning([
        'title' => 'test'
    ])->first();
    expect($initialPost->slug)->toBe('test');
});

test('respects groups', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->integer('category_id');
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
        $table->limax('category_slug', 'title', ['category_id']);
    });

    for ($index = 0; $index < 100; $index++) {
        $categoryId = $index % 2;
        $post = DB::table('posts')->insertReturning([
            'title' => 'test',
            'category_id' => $categoryId,
        ])->first();

        $suffix = $index > 0 ? '_' . ($index + 1) : '';
        expect($post->slug)->toBe('test' . $suffix);

        $categorySuffix = intdiv($index, 2) > 0 ? '_' . (intdiv($index, 2) + 1) : '';
        expect($post->category_slug)->toBe('test' . $categorySuffix);
    }
});
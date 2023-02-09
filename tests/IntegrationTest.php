<?php

namespace Jaulz\Limax\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Jaulz\Limax\Traits\IsSluggableTrait;
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
    ])->each(function ($value, $key) {
        $post = DB::table('posts')->insertReturning([
            'title' => '---'
        ])->first();

        $post = DB::table('posts')->where('id', $post->id)->updateReturning([
            'title' => $key
        ])->first();

        expect($post->slug)->toBe($value);
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

    for ($index = 0; $index < 4; $index++) {
        $post = DB::table('posts')->insertReturning([
            'title' => 'test'
        ])->first();

        $suffix = $index > 0 ? '_' . ($index + 1) : '';
        expect($post->slug)->toBe('test' . $suffix);
    }
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

    $firstPost = DB::table('posts')->insertReturning([
        'title' => $initialTitle
    ])->first();
    expect($firstPost->slug)->toBe($initialSlug);

    $firstPost = DB::table('posts')->updateReturning([
        'title' => 'not a test anymore'
    ])->first();
    expect($firstPost->slug)->toBe('not-a-test-anymore');

    $secondPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($secondPost->slug)->toBe('test_2');

    $firstPost = DB::table('posts')->where([
        'id' => $firstPost->id,
    ])->updateReturning([
        'title' => 'test'
    ])->first();
    expect($firstPost->slug)->toBe('test');
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

test('filters correctly', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title');
    });

    class Post extends Model {
        use IsSluggableTrait;
    }

    $firstPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();

    expect(Post::slugged('test')->first()->id)->toBe($firstPost->id);

    $secondPost = DB::table('posts')->insertReturning([
        'title' => 'second test'
    ])->first();

    expect(Post::slugged('second-test')->first()->id)->toBe($secondPost->id);

    $firstPost = DB::table('posts')->updateReturning([
        'title' => 'no test'
    ])->where('id', $firstPost->id)->first();

    expect(Post::slugged('test')->first()->id)->toBe($firstPost->id);
    expect(Post::slugged('no-test')->first()->id)->toBe($firstPost->id);

    expect(Post::slugged('not-existant')->first())->toBe(null);
});

test('removes slugs if requested', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->limax('slug', 'title', [], false);
    });

    class SecondPost extends Model {
        use IsSluggableTrait;

        protected $table = 'posts';
    }

    $firstPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($firstPost->slug)->toBe('test');

    dump(DB::table('limax.slugs')->get());

    $secondPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($secondPost->slug)->toBe('test_2');

    $thirdPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($thirdPost->slug)->toBe('test_3');

    $fourthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($fourthPost->slug)->toBe('test_4');

    DB::table('posts')->delete($thirdPost->id);

    $fifthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($fifthPost->slug)->toBe('test_3');

    DB::table('posts')->delete($firstPost->id);
    expect(SecondPost::slugged('test')->first())->toBe(null);

    DB::table('posts')->delete($secondPost->id);
    expect(SecondPost::slugged('test-2')->first())->toBe(null);

    $sixthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($sixthPost->slug)->toBe('test');

    $seventhPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($seventhPost->slug)->toBe('test_2');

    DB::table('posts')->delete($fourthPost->id);
    expect(SecondPost::slugged('test-4')->first())->toBe(null);

    $eigthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($eigthPost->slug)->toBe('test_4');

    $ninthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($ninthPost->slug)->toBe('test_5');

    $tenthPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($tenthPost->slug)->toBe('test_6');

    DB::table('posts')->where('id', $tenthPost->id)->update([
        'title' => 'no test'
    ]);
    expect(SecondPost::slugged('test_6')->first())->toBe(null);

    $eleventhPost = DB::table('posts')->insertReturning([
        'title' => 'test'
    ])->first();
    expect($eleventhPost->slug)->toBe('test_6');
});
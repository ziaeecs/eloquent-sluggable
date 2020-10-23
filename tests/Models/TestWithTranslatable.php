<?php namespace Cviebrock\EloquentSluggable\Tests\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


class TestWithTranslatable extends Model
{
    protected $casts = [
        'name' => 'json',
        'slug' => 'json',
    ];

    use HasTranslations;
    public $translatable = ['name', 'slug'];


    use Sluggable;
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
}

/*
$test = new Test();
$test->name = [
        'en' => 'english name',
        'fa' => 'نام فارسی'];
$test->save();
 */

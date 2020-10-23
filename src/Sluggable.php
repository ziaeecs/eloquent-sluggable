<?php namespace Cviebrock\EloquentSluggable;

use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Sluggable
 *
 * @package Cviebrock\EloquentSluggable
 */
trait Sluggable
{
    /**
     * @var bool
     */
    protected $hasTranslatableSlug = false;

    /**
     * Hook into the Eloquent model events to create or
     * update the slug as required.
     */
    public static function bootSluggable()
    {
        if (config('sluggable.translatable') && !config('app.supported_locales')) {
            throw new \Exception('The supported_locales in app config file is required.');
        }

        if (config('sluggable.translatable') && !in_array(\Spatie\Translatable\HasTranslations::class, class_uses(self::class))) {
            throw new \Exception('The Spatie Translatable package must be used.');
        }

        static::observe(app(SluggableObserver::class));
    }

    /**
     * Register a slugging model event with the dispatcher.
     *
     * @param \Closure|string $callback
     */
    public static function slugging($callback)
    {
        static::registerModelEvent('slugging', $callback);
    }

    /**
     * Register a slugged model event with the dispatcher.
     *
     * @param \Closure|string $callback
     */
    public static function slugged($callback)
    {
        static::registerModelEvent('slugged', $callback);
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $instance = parent::replicate($except);
        (new SlugService())->slug($instance, true);

        return $instance;
    }

    /**
     * Query scope for finding "similar" slugs, used to determine uniqueness.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $attribute
     * @param array $config
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindSimilarSlugs(Builder $query, string $attribute, array $config, string $slug): Builder
    {
        $separator = $config['separator'];

        return $query->where(function(Builder $q) use ($attribute, $slug, $separator) {
            $q->where($attribute, '=', $slug)
                ->orWhere($attribute, 'LIKE', $slug . $separator . '%');
        });
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (!$key) {
            return;
        }

        if ($this->hasTranslatableSlug) {
            foreach (config('app.supported_locales', []) as $locale) {
                if (strpos($key, '___'.$locale) !== false) {
                    return $this->getTranslation(str_replace('___'.$locale, '', $key), $locale);
                }
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggableConfig(): array
    {
        $config = $this->sluggable();

        if (method_exists($this, 'isTranslatableAttribute')) {
            foreach ($config as $key => $value) {
                if (!is_numeric($key) && $this->isTranslatableAttribute($key)) {
                    $this->hasTranslatableSlug = true;

                    foreach (config('app.supported_locales', []) as $locale) {
                        $config[$key.'->'.$locale] = array_merge($value, [
                            'source' => $value['source'].'___'.$locale,
                        ]);
                    }

                    unset($config[$key]);
                }
            }
        }

        return $config;
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    abstract public function sluggable(): array;
}

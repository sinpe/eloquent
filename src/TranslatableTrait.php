<?php

namespace Sinpe\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sinpe\Eloquent\Collection;
use Sinpe\Eloquent\Model;

/**
 * Class TranslatableTrait.
 */
trait TranslatableTrait
{
    /**
     * The translatable attributes.
     *
     * @var array
     */
    protected $translatedAttributes = [];

    /**
     * Restrict results where
     * in the provided locale.
     *
     * @param Builder $query
     * @param $locale
     *
     * @return Builder
     */
    public function scopeTranslatedIn(Builder $query, $locale)
    {
        return $query->whereHas(
            'translations',
            function (Builder $q) use ($locale) {
                $q->where($this->getLocaleKey(), '=', $locale);
            }
        );
    }

    /**
     * Restrict results where
     * translated entries only.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeTranslated(Builder $query)
    {
        return $query->has('translations');
    }

    /**
     * Return translated attributes.
     *
     * @return array
     */
    public function getTranslatedAttributes()
    {
        return $this->translatedAttributes;
    }

    /**
     * Return if the attribute is
     * translatable or not.
     *
     * @param $key
     *
     * @return bool
     */
    public function isTranslatedAttribute($key)
    {
        return in_array($key, $this->translatedAttributes);
    }

    /**
     * Alias for isTranslatedAttribute().
     *
     * @deprecated 1.3 remove in 1.4
     *
     * @param $key
     *
     * @return bool
     */
    protected function isTranslationAttribute($key)
    {
        return $this->isTranslatedAttribute($key);
    }

    /**
     * Return the translatable flag.
     *
     * @return bool
     */
    public function isTranslatable()
    {
        return isset($this->translationModel);
    }

    /**
     * Set the translatable flag.
     *
     * @param $translatable
     *
     * @return $this
     */
    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;

        return $this;
    }

    /*
     * Alias for getTranslation()
     *
     * @return Model|null
     */
    public function translate($locale = null, $withFallback = false)
    {
        return $this->getTranslation($locale, $withFallback);
    }

    /*
     * Alias for getTranslation()
     *
     */

    /**
     * @param null $locale
     *
     * @return Translatable
     */
    public function translateOrDefault($locale = null)
    {
        if (!$locale) {
            $locale = $this->getDefaultLocale();
        }

        return $this->getTranslation($locale, true) ?: $this;
    }

    /*
     * Alias for getTranslationOrNew()
     *
     * @return Model|null
     */
    public function translateOrNew($locale)
    {
        return $this->getTranslationOrNew($locale);
    }

    /**
     * Get related translations.
     *
     * @return Collection
     */
    public function getTranslations()
    {
        /* @var Model $translation */
        foreach ($translations = $this->translations as $translation) {
            $translation->setRelation('parent', $this);
        }

        return $translations;
    }

    /**
     * Return the translations relation.
     *
     * @return HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    /**
     * Get a translation.
     *
     * @param null      $locale
     * @param bool|null $withFallback
     *
     * @return Model|null
     */
    public function getTranslation($locale = null, $withFallback = true)
    {
        // Default to the current locale.
        $locale = $locale ?: app()->getLocale();

        /*
         * If we have a desired locale and
         * it exists then just use that locale.
         */
        if ($translation = $this->getTranslationByLocaleKey($locale)) {
            return $translation;
        }

        /*
         * If we don't have a locale or it does not exist
         * then go ahead and try using a fallback in using
         * the system's designated DEFAULT (not active) locale.
         */
        if ($withFallback
            && $translation = $this->getTranslationByLocaleKey($this->getDefaultLocale())
        ) {
            return $translation;
        }

        /*
         * If we still don't have a translation then
         * try looking up the FALLBACK translation.
         */
        if ($withFallback
            && $this->getFallbackLocale()
            && $this->getTranslationByLocaleKey($this->getFallbackLocale())
            && $translation = $this->getTranslationByLocaleKey($this->getFallbackLocale())
        ) {
            return $translation;
        }

        return null;
    }

    /**
     * Return if a translation exists or not.
     *
     * @param null $locale
     *
     * @return bool
     */
    public function hasTranslation($locale = null)
    {
        $locale = $locale ?: $this->getFallbackLocale();

        foreach ($this->getTranslations() as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the translation model.
     *
     * @return Model
     */
    public function getTranslationModel()
    {
        return new $this->translationModel();
    }

    /**
     * Get the translation model name.
     *
     * @return string
     */
    public function getTranslationModelName()
    {
        return $this->translationModel;
    }

    /**
     * Return if the model IS the
     * default translation or not.
     *
     * @return bool
     */
    public function isDefaultTranslation()
    {
        return $this->getAttribute('locale') === $this->getDefaultLocale();
    }

    /**
     * Get the translation table name.
     *
     * @return string
     */
    public function getTranslationTableName()
    {
        $model = $this->getTranslationModel();

        return $model->getTableName();
    }

    /**
     * Get the default translation model name.
     *
     * @return string
     */
    public function getTranslationModelNameDefault()
    {
        return get_class($this).'Translation';
    }

    /**
     * Save translations to the database.
     *
     * @return bool
     */
    protected function saveTranslations()
    {
        $saved = true;

        $translations = $this->getTranslations();

        foreach ($this->getTranslations() as $translation) {
            /* @var Model $translation */
            if ($saved && $this->isTranslationDirty($translation)) {
                $translation->setAttribute($this->getRelationKey(), $this->getKey());

                $saved = $translation->save();
            }
        }

        if ($translations->isEmpty()) {
            $translation = $this->translateOrNew(config('streams::locales.default'));

            $translation->save();
        }

        $this->finishSave([]);

        return $saved;
    }

    /**
     * Get a translation or new instance.
     *
     * @param $locale
     *
     * @return Model|null
     */
    protected function getTranslationOrNew($locale)
    {
        if (($translation = $this->getTranslation($locale, false)) === null) {
            $translation = $this->getNewTranslation($locale);
        }

        return $translation;
    }

    /**
     * Get a translation by locale key.
     *
     * @param $key
     *
     * @return Model|null
     */
    protected function getTranslationByLocaleKey($key)
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $key) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Return if the translation is dirty or not.
     *
     * @param Model $translation
     *
     * @return bool
     */
    protected function isTranslationDirty(Model $translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);

        return count($dirtyAttributes) > 0;
    }

    /**
     * Get a new translation model.
     *
     * @param $locale
     *
     * @return Model
     */
    public function getNewTranslation($locale)
    {
        $modelName = $this->getTranslationModelName();

        /* @var Model $translation */
        $translation = new $modelName();

        $translation->setRelation('parent', $this);

        $translation->setAttribute($this->getLocaleKey(), $locale);
        $translation->setAttribute($this->getRelationKey(), $this->getKey());

        $this
            ->getTranslations()
            ->add($translation);

        return $translation;
    }

    /**
     * Get the translation foreign key.
     *
     * @return string
     */
    public function getRelationKey()
    {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

    /**
     * Get the locale key.
     *
     * @return string
     */
    public function getLocaleKey()
    {
        return $this->localeKey ?: 'locale';
    }

    /**
     * Get an attribute.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getAttribute($key)
    {
        if ($this->isTranslatedAttribute($key)) {
            if (($translation = $this->getTranslation()) === null) {
                return null;
            }

            $translation->setRelation('parent', $this);

            return $translation->$key;
        }

        return parent::getAttribute($key);
    }

    /**
     * Set an attribute.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->translatedAttributes)) {
            $this->getTranslationOrNew(config('app.locale'))->$key = $value;
        } else {
            parent::setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Fill the model attributes.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $values) {
            if (is_array($values) && $this->isKeyALocale($key)) {
                foreach ($values as $translationAttribute => $translationValue) {
                    if ($this->alwaysFillable() || $this->isFillable($translationAttribute)) {
                        $this->getTranslationOrNew($key)->$translationAttribute = $translationValue;
                    }
                }
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Return the model as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributesToArray();

        foreach ($this->translatedAttributes as $field) {
            if ($translation = $this->getTranslation()) {
                $attributes[$field] = $translation->$field;
            }
        }

        return $attributes;
    }

    /**
     * Get the default locale.
     *
     * @return string
     */
    protected function getDefaultLocale()
    {
        if (isset($this->cache['default_locale'])) {
            return $this->cache['default_locale'];
        }

        return $this->cache['default_locale'] = config('streams::locales.default');
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    protected function getFallbackLocale()
    {
        if (isset($this->cache['fallback_locale'])) {
            return $this->cache['fallback_locale'];
        }

        return $this->cache['fallback_locale'] = config('app.fallback_locale');
    }

    /**
     * Save the model.
     *
     * We have some customization here to
     * accommodate translations. First sa
     * then save translations is translatable.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if (!$this->getTranslationModelName()) {
            return $this->saveModel($options);
        }

        if ($this->exists) {
            if (count($this->getDirty()) > 0) {
                // If $this->exists and dirty, $this->saveModel() has to return true. If not,
                // an error has occurred. Therefore we shouldn't save the translations.
                if ($this->saveModel($options)) {
                    return $this->saveTranslations();
                }

                return false;
            } else {
                // If $this->exists and not dirty, $this->saveModel() skips saving and returns
                // false. So we have to save the translations
                return $this->saveTranslations();
            }
        } elseif ($this->saveModel($options)) {
            // We save the translations only if the instance is saved in the database.
            return $this->saveTranslations();
        }

        return false;
    }

    /**
     * Save the model to the database.
     *
     * This is a direct port from Eloquent
     * with the only exception being that if
     * the model is translatable it will NOT
     * fire the saved event. The saveTranslations
     * method will do that instead.
     *
     * @param array $options
     *
     * @return bool
     */
    public function saveModel(array $options = [])
    {
        $query = $this->newQueryWithoutScopes();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query, $options);
        }

        if ($saved && !$this->isTranslatable()) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return in_array($key, $this->translatedAttributes) || parent::__isset($key);
    }
}

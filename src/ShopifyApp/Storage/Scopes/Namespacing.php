<?php

namespace OhMyBrew\ShopifyApp\Storage\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Config;

/**
 * Responsible for scoping to the current namesapce.
 */
class Namespacing implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('shopify_namespace', Config::get('shopify-app.namespace'));
    }
}

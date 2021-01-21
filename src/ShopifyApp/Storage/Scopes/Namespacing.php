<?php

namespace Osiset\ShopifyApp\Storage\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use function Osiset\ShopifyApp\getShopifyConfig;

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
        $builder->where('shopify_namespace', getShopifyConfig('namespace'));
    }
}

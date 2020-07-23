<?php

namespace Osiset\ShopifyApp\Storage\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Responsible for scoping to the current namesapce.
 */
class Namespacing implements Scope
{
    use ConfigAccessible;

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
        $builder->where('shopify_namespace', $this->getConfig('namespace'));
    }
}

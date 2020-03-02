<?php

namespace OhMyBrew\ShopifyApp\Test\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Traits\ShopModel;

class User extends Authenticatable implements IShopModel
{
    use Notifiable;
    use ShopModel;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}

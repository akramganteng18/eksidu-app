<?php

namespace App\Policies;

use App\Models\PanduanMedia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PanduanMediaPolicy
{
    public function view(User $user, PanduanMedia $panduan): Response
    {
        return $panduan->bisaDilihatOleh($user) ? Response::allow() : Response::denyAsNotFound();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Posts;

class SellersController extends Controller
{
    public function index()
    {
        return response()->json(
            Posts::sellers()->select([
                'ID as id',
                'post_title as name',
            ])->get()
        );
    }
}

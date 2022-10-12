<?php

namespace App\Models\Wordpress;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMetadata extends Model
{
    use HasFactory;

    protected $connection = 'wordpress';

    protected $table = 'wp_postmeta';

    protected $primaryKey = 'meta_id';

    protected $fillable = [
        'meta_id',
        'post_id',
        'meta_key',
        'meta_value',
    ];

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id', 'ID');
    }
}

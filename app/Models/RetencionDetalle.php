<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetencionDetalle extends Model
{
    use HasFactory;

    protected $table = 'detalle_retenciones';
    protected $guarded = ['_token'];
    public $timestamps = false;
}

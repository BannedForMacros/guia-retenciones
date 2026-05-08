<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Retencion extends Model
{
    use HasFactory;

    protected $table = 'retenciones';
    protected $guarded = ['_token'];
    public $timestamps = false;

    public function detalles(): HasMany
    {
        return $this->hasMany(RetencionDetalle::class, 'rucempresa', 'rucempresa')
                    ->where('serienumero', $this->serienumero);
    }
}

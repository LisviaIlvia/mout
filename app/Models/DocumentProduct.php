<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentProduct extends Model
{
    protected $table = 'documents_products';
    protected $fillable = ['quantita', 'prezzo', 'aliquota_iva_id', 'order', 'product_id', 'document_id', 'fornitore_id', 'riferimento', 'note'];

	public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function aliquotaIva()
    {
        return $this->belongsTo(AliquotaIva::class, 'aliquota_iva_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function fornitore()
    {
        return $this->belongsTo(Entity::class, 'fornitore_id');
    }
}

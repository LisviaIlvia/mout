<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAltro extends Model
{
    protected $table = 'documents_altro';
    protected $fillable = ['nome', 'quantita', 'unita_misura', 'prezzo', 'aliquota_iva_id', 'order', 'document_id'];

    public function aliquotaIva()
    {
        return $this->belongsTo(AliquotaIva::class, 'aliquota_iva_id');
    }
	
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
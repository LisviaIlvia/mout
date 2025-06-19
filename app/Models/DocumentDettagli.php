<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDettagli extends Model
{
    protected $table = 'documents_dettagli';
    protected $fillable = [
        'document_id',
        'data_evasione',
        'mod_poltrona',
        'quantita',
        'fianchi_finali',
        'interasse_cm',
        'largh_bracciolo_cm',
        'rivestimento',
        'ricamo_logo',
        'pendenza',
        'fissaggio_pavimento',
        'montaggio'
    ];

    protected $casts = [
        'ricamo_logo' => 'boolean',
        'pendenza' => 'boolean',
        'fissaggio_pavimento' => 'boolean',
        'montaggio' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}

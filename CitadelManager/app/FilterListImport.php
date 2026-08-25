<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * A record of an export object that has been imported from a filesystem disk.
 *
 * The bucket itself is never modified - the export pipeline owns it, and
 * CloudVeilManager reads the same objects - so import state is kept here.
 */
class FilterListImport extends Model
{
    protected $fillable = [
        'disk',
        'file',
        'category',
        'etag',
        'size',
        'object_last_modified',
        'imported_at',
    ];

    protected $dates = [
        'object_last_modified',
        'imported_at',
    ];
}

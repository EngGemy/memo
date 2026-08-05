<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadSession extends Model
{
    protected $fillable = [
        'uuid','user_id','filename','size_bytes','chunk_size',
        'total_chunks','received_chunks','sha256','status',
    ];
    protected $casts = ['received_chunks' => 'array'];

    public function isComplete(): bool
    {
        return count($this->received_chunks ?? []) === $this->total_chunks;
    }

    public function missingChunks(): array
    {
        return array_values(array_diff(range(0, $this->total_chunks - 1), $this->received_chunks ?? []));
    }
}
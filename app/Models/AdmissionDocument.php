<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDocument extends Model
{
    // Document types
    public const TYPE_TRANSFER_CERTIFICATE = 'transfer_certificate';
    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';
    public const TYPE_PHOTO = 'photo';
    public const TYPE_MARK_SHEET = 'mark_sheet';
    public const TYPE_CHARACTER_CERTIFICATE = 'character_certificate';
    public const TYPE_MIGRATION_CERTIFICATE = 'migration_certificate';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'admission_id',
        'type',
        'name',
        'file_path',
        'file_size',
        'file_type',
        'description',
        'is_approved',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'file_size' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the admission that owns the document.
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the user who reviewed the document.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the document type label.
     */
    public function getTypeLabelAttribute(): string
    {
        $types = [
            self::TYPE_TRANSFER_CERTIFICATE => 'Transfer Certificate',
            self::TYPE_BIRTH_CERTIFICATE => 'Birth Certificate',
            self::TYPE_PHOTO => 'Photograph',
            self::TYPE_MARK_SHEET => 'Mark Sheet',
            self::TYPE_CHARACTER_CERTIFICATE => 'Character Certificate',
            self::TYPE_MIGRATION_CERTIFICATE => 'Migration Certificate',
            self::TYPE_OTHER => 'Other Document',
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 2) . ' MB';
        }

        return number_format($this->file_size / 1024, 2) . ' KB';
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Approve the document.
     */
    public function approve(string $notes = null, int $reviewerId = null): bool
    {
        $this->is_approved = true;
        $this->review_notes = $notes;
        $this->reviewed_by = $reviewerId ?? auth()->id();
        $this->reviewed_at = now();
        
        return $this->save();
    }

    /**
     * Reject the document.
     */
    public function reject(string $notes, int $reviewerId = null): bool
    {
        $this->is_approved = false;
        $this->review_notes = $notes;
        $this->reviewed_by = $reviewerId ?? auth()->id();
        $this->reviewed_at = now();
        
        return $this->save();
    }
}

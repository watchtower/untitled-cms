<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlock extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'sort_order',
        'content',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods for different block types
    public function isTextBlock(): bool
    {
        return $this->type === 'text';
    }

    public function isImageBlock(): bool
    {
        return $this->type === 'image';
    }

    public function isCodeBlock(): bool
    {
        return $this->type === 'code';
    }

    // Content getters for different block types
    public function getTextContent(): string
    {
        return $this->content['text'] ?? '';
    }

    public function getImageUrl(): string
    {
        return $this->content['url'] ?? '';
    }

    public function getImageAlt(): string
    {
        return $this->content['alt'] ?? '';
    }

    public function getImageCaption(): string
    {
        return $this->content['caption'] ?? '';
    }

    public function getCodeContent(): string
    {
        return $this->content['code'] ?? '';
    }

    public function getCodeLanguage(): string
    {
        return $this->content['language'] ?? 'text';
    }

    public function getCodeCaption(): string
    {
        return $this->content['caption'] ?? '';
    }

    // Settings getters
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}

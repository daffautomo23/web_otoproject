<?php

namespace App\Models\DocumentManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_folder_id',
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
        'is_private',
        'department',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_private' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Use slug as the route key for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the parent folder
     */
    public function parent()
    {
        return $this->belongsTo(DocumentFolder::class, 'parent_folder_id');
    }

    /**
     * Get all direct subfolders
     */
    public function subfolders()
    {
        return $this->hasMany(DocumentFolder::class, 'parent_folder_id')->where('is_active', true)->orderBy('order', 'asc');
    }

    /**
     * Recursively get the root (top-level) folder
     */
    public function getRootFolder()
    {
        if (!$this->parent_folder_id) {
            return $this;
        }
        return $this->parent->getRootFolder();
    }

    /**
     * Get all documents in this folder
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    /**
     * Get active documents only
     */
    public function activeDocuments()
    {
        return $this->hasMany(Document::class, 'folder_id')->whereNull('deleted_at');
    }

    /**
     * Scope to get only active folders
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order folders by their order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get document count for this folder
     */
    public function getDocumentCountAttribute()
    {
        return $this->documents()->count();
    }

    /**
     * Check if user can manage this folder (CRUD operations)
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    public function canUserManage($user)
    {
        // If this is a subfolder, delegate to root folder's settings
        if ($this->parent_folder_id) {
            return $this->getRootFolder()->canUserManage($user);
        }

        // Admin always can manage
        if ($user->hasAccess('dokumen_manajemen_admin')) {
            return true;
        }
        
        // If has specific department
        if ($this->department && $this->department !== 'all') {
            // User must be from that department AND be a Manager
            $isSameDepartment = $user->divisi && $user->divisi === $this->department;
            $isManager = $user->level && strtolower($user->level) === 'manager';
            
            return $isSameDepartment && $isManager;
        }

        // If department is 'all' or no department set, only admin can manage (already checked above)
        return false;
    }

    /**
     * Check if user can view this folder
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    public function canUserView($user)
    {
        // If this is a subfolder, delegate to root folder's settings
        if ($this->parent_folder_id) {
            return $this->getRootFolder()->canUserView($user);
        }

        // Admin always can view
        if ($user->hasAccess('dokumen_manajemen_admin')) {
            return true;
        }

        // If folder has no department set, everyone can view
        if (!$this->department || $this->department === '') {
            return true;
        }

        // If department is 'all', everyone can view
        if ($this->department === 'all') {
            return true;
        }

        // If private and has specific department
        if ($this->is_private && $this->department) {
            // Only users from that department can view
            return $user->divisi && $user->divisi === $this->department;
        }

        // If not private but has specific department
        // Everyone can view, but only that department can CRUD (handled in canUserManage)
        return true;
    }
}
